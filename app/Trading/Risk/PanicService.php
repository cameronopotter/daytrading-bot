<?php

namespace App\Trading\Risk;

use App\Models\DecisionLog;
use App\Models\Order;
use App\Models\StrategyRun;
use App\Trading\BrokerAdapter;
use Illuminate\Support\Facades\Log;

class PanicService
{
    public function __construct(
        private BrokerAdapter $adapter
    ) {}

    public function flattenAll(?int $strategyRunId = null): array
    {
        Log::warning('[PANIC] Flatten all triggered', ['strategy_run_id' => $strategyRunId]);

        $results = [
            'positions_closed' => false,
            'orders_canceled' => 0,
            'run_stopped' => false,
            'errors' => [],
        ];

        try {
            $this->adapter->closeAllPositions();
            $results['positions_closed'] = true;
            Log::info('[PANIC] All positions closed');
        } catch (\Exception $e) {
            $results['errors'][] = 'Failed to close positions: '.$e->getMessage();
            Log::error('[PANIC ERROR] Failed to close positions', ['error' => $e->getMessage()]);
        }

        try {
            $openOrders = Order::whereIn('status', ['new', 'partially_filled'])->get();

            foreach ($openOrders as $order) {
                try {
                    if ($order->broker_order_id) {
                        $this->adapter->cancelOrder($order->broker_order_id);
                        $order->update(['status' => 'canceled']);
                        $results['orders_canceled']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to cancel order {$order->id}: ".$e->getMessage();
                    Log::error('[PANIC ERROR] Failed to cancel order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[PANIC] Canceled orders', ['count' => $results['orders_canceled']]);
        } catch (\Exception $e) {
            $results['errors'][] = 'Failed to cancel orders: '.$e->getMessage();
            Log::error('[PANIC ERROR] Failed to cancel orders', ['error' => $e->getMessage()]);
        }

        try {
            if ($strategyRunId) {
                $run = StrategyRun::find($strategyRunId);
                if ($run && $run->status === 'running') {
                    $run->update([
                        'status' => 'stopped',
                        'stopped_at' => now(),
                        'notes' => 'Stopped by panic button',
                    ]);
                    $results['run_stopped'] = true;
                    Log::info('[PANIC] Strategy run stopped', ['run_id' => $strategyRunId]);
                }
            } else {
                StrategyRun::where('status', 'running')->update([
                    'status' => 'stopped',
                    'stopped_at' => now(),
                    'notes' => 'Stopped by panic button',
                ]);
                $results['run_stopped'] = true;
                Log::info('[PANIC] All running strategy runs stopped');
            }
        } catch (\Exception $e) {
            $results['errors'][] = 'Failed to stop runs: '.$e->getMessage();
            Log::error('[PANIC ERROR] Failed to stop runs', ['error' => $e->getMessage()]);
        }

        DecisionLog::create([
            'strategy_run_id' => $strategyRunId,
            'level' => 'warn',
            'context' => 'panic',
            'message' => 'PANIC: Flatten all positions and cancel orders triggered',
            'payload' => $results,
            'created_at' => now(),
        ]);

        return $results;
    }
}
