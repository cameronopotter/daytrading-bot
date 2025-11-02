<?php

namespace App\Jobs;

use App\Models\DecisionLog;
use App\Models\Order;
use App\Models\Position;
use App\Models\StrategyRun;
use App\Trading\Adapters\AlpacaAdapter;
use App\Trading\DTO\OrderRequest;
use App\Trading\Risk\RiskGuard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExecuteOrder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $strategyRunId,
        public OrderRequest $orderRequest,
        public ?float $stopLoss = null,
        public ?float $takeProfit = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RiskGuard $riskGuard, AlpacaAdapter $adapter): void
    {
        Log::info('[EXECUTE ORDER] Job started', [
            'run_id' => $this->strategyRunId,
            'symbol' => $this->orderRequest->symbol,
            'side' => $this->orderRequest->side,
            'qty' => $this->orderRequest->qty,
            'type' => $this->orderRequest->type,
        ]);

        $run = StrategyRun::find($this->strategyRunId);

        if (!$run || $run->status !== 'running') {
            Log::warning('[EXECUTE ORDER] Strategy run not active', [
                'run_id' => $this->strategyRunId,
                'run_status' => $run?->status ?? 'not found',
            ]);
            return;
        }

        // Generate client order ID if not provided
        if (!$this->orderRequest->clientId) {
            $this->orderRequest->clientId = (string) Str::uuid();
        }

        Log::info('[EXECUTE ORDER] Client order ID generated', [
            'client_order_id' => $this->orderRequest->clientId,
        ]);

        // Check risk limits
        $state = [
            'day_pl' => $riskGuard->getDailyPnL(),
        ];

        Log::info('[EXECUTE ORDER] Checking risk limits', [
            'day_pl' => $state['day_pl'],
            'order_qty' => $this->orderRequest->qty,
        ]);

        if (!$riskGuard->allows($this->orderRequest, $state)) {
            Log::warning('[EXECUTE ORDER] ⛔ RISK GUARD DENIED', [
                'order' => [
                    'symbol' => $this->orderRequest->symbol,
                    'side' => $this->orderRequest->side,
                    'qty' => $this->orderRequest->qty,
                ],
                'state' => $state,
            ]);

            DecisionLog::create([
                'strategy_run_id' => $this->strategyRunId,
                'level' => 'warn',
                'context' => 'risk_denied',
                'message' => 'Order denied by risk guard',
                'payload' => [
                    'symbol' => $this->orderRequest->symbol,
                    'side' => $this->orderRequest->side,
                    'qty' => $this->orderRequest->qty,
                ],
                'created_at' => now(),
            ]);

            return;
        }

        try {
            Log::info('[EXECUTE ORDER] 📡 Sending order to Alpaca...', [
                'symbol' => $this->orderRequest->symbol,
                'side' => $this->orderRequest->side,
                'qty' => $this->orderRequest->qty,
                'type' => $this->orderRequest->type,
            ]);

            // Place order via broker adapter
            $result = $adapter->placeOrder($this->orderRequest);

            Log::info('[EXECUTE ORDER] ✅ Alpaca response received', [
                'broker_order_id' => $result['broker_order_id'],
                'status' => $result['status'],
            ]);

            // Persist order in database
            $order = Order::create([
                'strategy_run_id' => $this->strategyRunId,
                'client_order_id' => $result['client_order_id'],
                'broker_order_id' => $result['broker_order_id'],
                'broker' => 'alpaca',
                'symbol' => $result['symbol'],
                'side' => $result['side'],
                'type' => $result['type'],
                'qty' => $result['qty'],
                'limit_price' => $result['limit_price'],
                'stop_price' => $result['stop_price'],
                'time_in_force' => $result['time_in_force'],
                'status' => $result['status'],
                'placed_at' => $result['placed_at'],
                'filled_qty' => $result['filled_qty'] ?? 0,
                'avg_fill_price' => null,
                'raw' => $result['raw'] ?? null,
            ]);

            Log::info('[EXECUTE ORDER] 💾 Order saved to database', [
                'order_id' => $order->id,
                'broker_order_id' => $result['broker_order_id'],
                'symbol' => $result['symbol'],
                'side' => $result['side'],
                'qty' => $result['qty'],
            ]);

            Log::info('[EXECUTE ORDER] 🎉 ORDER PLACED SUCCESSFULLY!', [
                'order_id' => $order->id,
                'broker_order_id' => $result['broker_order_id'],
                'symbol' => $result['symbol'],
                'side' => $result['side'],
                'qty' => $result['qty'],
                'status' => $result['status'],
            ]);

            // If this is a buy order and we have stop-loss/take-profit, store them in position
            if ($this->orderRequest->side === 'buy' && ($this->stopLoss !== null || $this->takeProfit !== null)) {
                $mode = config('trading.mode', 'paper');

                // Find or create position
                $position = Position::where('symbol', $this->orderRequest->symbol)
                    ->where('mode', $mode)
                    ->first();

                if ($position) {
                    $position->update([
                        'stop_loss' => $this->stopLoss,
                        'take_profit' => $this->takeProfit,
                    ]);

                    Log::info('[EXECUTE ORDER] 🎯 Stop-loss and take-profit set', [
                        'symbol' => $this->orderRequest->symbol,
                        'stop_loss' => $this->stopLoss,
                        'take_profit' => $this->takeProfit,
                    ]);
                }
            }

            // Log decision
            DecisionLog::create([
                'strategy_run_id' => $this->strategyRunId,
                'level' => 'info',
                'context' => 'order_placed',
                'message' => "Order placed: {$result['side']} {$result['qty']} {$result['symbol']}",
                'payload' => array_merge($result, [
                    'stop_loss' => $this->stopLoss,
                    'take_profit' => $this->takeProfit,
                ]),
                'created_at' => now(),
            ]);

            // TODO: Broadcast event to UI
            // event(new OrderPlaced($order));

        } catch (\Exception $e) {
            Log::error('[EXECUTE ORDER ERROR]', [
                'error' => $e->getMessage(),
                'order' => [
                    'symbol' => $this->orderRequest->symbol,
                    'side' => $this->orderRequest->side,
                    'qty' => $this->orderRequest->qty,
                ],
            ]);

            DecisionLog::create([
                'strategy_run_id' => $this->strategyRunId,
                'level' => 'error',
                'context' => 'order_failed',
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'payload' => [
                    'symbol' => $this->orderRequest->symbol,
                    'side' => $this->orderRequest->side,
                    'qty' => $this->orderRequest->qty,
                    'error' => $e->getMessage(),
                ],
                'created_at' => now(),
            ]);

            throw $e;
        }
    }
}
