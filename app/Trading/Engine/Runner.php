<?php

namespace App\Trading\Engine;

use App\Jobs\ExecuteOrder;
use App\Models\DecisionLog;
use App\Models\Position;
use App\Models\StrategyRun;
use App\Trading\Strategies\Strategy;
use Illuminate\Support\Facades\Log;

class Runner
{
    public function processBar(array $barData): void
    {
        $symbol = $barData['symbol'];

        Log::info('[ENGINE] 📊 Processing bar', [
            'symbol' => $symbol,
            'close' => $barData['close'] ?? null,
            'timestamp' => $barData['timestamp'] ?? null,
        ]);

        // Find active strategy runs for this symbol
        $runs = StrategyRun::where('status', 'running')
            ->whereHas('strategy', function ($q) use ($symbol) {
                $q->where('is_enabled', true)
                    ->where('config->symbol', $symbol);
            })
            ->with('strategy')
            ->get();

        if ($runs->isEmpty()) {
            Log::debug("[ENGINE] ⏭️  No active runs for {$symbol}");
            return;
        }

        Log::info("[ENGINE] Found {$runs->count()} active run(s) for {$symbol}");

        foreach ($runs as $run) {
            try {
                $this->processBarForRun($run, $barData);
            } catch (\Exception $e) {
                Log::error('[ENGINE ERROR] ❌ Failed to process bar for run', [
                    'run_id' => $run->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                DecisionLog::create([
                    'strategy_run_id' => $run->id,
                    'level' => 'error',
                    'context' => 'engine_error',
                    'message' => 'Failed to process bar: ' . $e->getMessage(),
                    'payload' => ['bar' => $barData],
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function processBarForRun(StrategyRun $run, array $barData): void
    {
        $strategyClass = $run->strategy->class;
        $config = $run->strategy->config;

        // Instantiate strategy
        if (!class_exists($strategyClass)) {
            Log::warning("[ENGINE] Strategy class not found: {$strategyClass}");
            return;
        }

        /** @var Strategy $strategy */
        $strategy = new $strategyClass($config);

        // Build state (current position, etc.)
        $state = $this->buildState($run, $config['symbol']);

        // Call strategy
        Log::info('[ENGINE] 🧮 Calling strategy', [
            'strategy' => $strategy->name(),
            'symbol' => $config['symbol'],
            'current_position' => $state['position'] ?? 'flat',
        ]);

        $signal = $strategy->onBar($barData, $state);

        if (!$signal) {
            Log::debug('[ENGINE] Strategy returned null signal');
            return;
        }

        Log::info('[ENGINE] 📡 Signal received', [
            'has_order' => $signal->order !== null,
            'note' => $signal->note,
        ]);

        // Log the signal
        DecisionLog::create([
            'strategy_run_id' => $run->id,
            'level' => 'info',
            'context' => 'signal',
            'message' => $signal->note ?? 'Signal generated',
            'payload' => [
                'bar' => $barData,
                'has_order' => $signal->order !== null,
            ],
            'created_at' => now(),
        ]);

        // If signal has an order, dispatch job
        if ($signal->order) {
            Log::info('[ENGINE] 🚀 DISPATCHING ORDER TO QUEUE!', [
                'run_id' => $run->id,
                'symbol' => $signal->order->symbol,
                'side' => $signal->order->side,
                'qty' => $signal->order->qty,
                'type' => $signal->order->type,
            ]);

            ExecuteOrder::dispatch($run->id, $signal->order);

            Log::info('[ENGINE] ✅ Order dispatched successfully');
        } else {
            Log::debug('[ENGINE] No order in signal (no action)');
        }
    }

    private function buildState(StrategyRun $run, string $symbol): array
    {
        $mode = config('trading.mode', 'paper');

        // Get current position for this symbol
        $position = Position::where('symbol', $symbol)
            ->where('mode', $mode)
            ->first();

        $state = [
            'position' => null,
            'qty' => 0,
            'avg_entry_price' => 0,
        ];

        if ($position && $position->qty > 0) {
            $state['position'] = 'long';
            $state['qty'] = $position->qty;
            $state['avg_entry_price'] = $position->avg_entry_price;
        }

        return $state;
    }
}
