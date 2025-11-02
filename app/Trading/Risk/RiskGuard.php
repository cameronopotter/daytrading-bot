<?php

namespace App\Trading\Risk;

use App\Models\Order;
use App\Models\RiskLimit;
use App\Trading\DTO\OrderRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RiskGuard
{
    public function allows(OrderRequest $orderRequest, array $state = []): bool
    {
        $mode = config('trading.mode', 'paper');

        // Get risk limits for current mode
        $riskLimit = RiskLimit::where('mode', $mode)->first();

        if (!$riskLimit) {
            // No risk limits configured - allow (for MVP)
            return true;
        }

        // Check daily P&L limit
        if (!$this->checkDailyLoss($riskLimit, $state)) {
            return false;
        }

        // Check max position size
        if (!$this->checkPositionSize($orderRequest, $riskLimit)) {
            return false;
        }

        // Check order rate limit
        if (!$this->checkOrderRate($riskLimit)) {
            return false;
        }

        return true;
    }

    private function checkDailyLoss(RiskLimit $riskLimit, array $state): bool
    {
        // Get today's P&L (simplified - from account or computed)
        $dayPnL = $state['day_pl'] ?? 0;

        if ($dayPnL < -$riskLimit->daily_max_loss) {
            \Log::warning('[RISK GUARD] Daily loss limit exceeded', [
                'day_pl' => $dayPnL,
                'limit' => $riskLimit->daily_max_loss,
            ]);
            return false;
        }

        return true;
    }

    private function checkPositionSize(OrderRequest $orderRequest, RiskLimit $riskLimit): bool
    {
        if ($orderRequest->qty > $riskLimit->max_position_qty) {
            \Log::warning('[RISK GUARD] Order quantity exceeds max position size', [
                'qty' => $orderRequest->qty,
                'limit' => $riskLimit->max_position_qty,
            ]);
            return false;
        }

        return true;
    }

    private function checkOrderRate(RiskLimit $riskLimit): bool
    {
        $key = 'risk:order_rate:' . now()->format('Y-m-d-H-i');

        $count = Cache::get($key, 0);

        if ($count >= $riskLimit->max_orders_per_min) {
            \Log::warning('[RISK GUARD] Order rate limit exceeded', [
                'count' => $count,
                'limit' => $riskLimit->max_orders_per_min,
            ]);
            return false;
        }

        Cache::put($key, $count + 1, now()->addMinutes(2));

        return true;
    }

    public function getDailyPnL(): float
    {
        // Calculate daily P&L from filled orders
        $today = now()->startOfDay();

        $orders = Order::where('status', 'filled')
            ->where('placed_at', '>=', $today)
            ->get();

        $pnl = 0;

        foreach ($orders as $order) {
            if ($order->side === 'sell') {
                $pnl += ($order->avg_fill_price * $order->filled_qty);
            } else { // buy
                $pnl -= ($order->avg_fill_price * $order->filled_qty);
            }
        }

        return $pnl;
    }
}
