<?php

namespace App\Trading\Risk;

use App\Models\Order;
use App\Models\RiskLimit;
use App\Trading\DTO\OrderRequest;
use Illuminate\Support\Facades\Cache;

class RiskGuard
{
    public function allows(OrderRequest $orderRequest, array $state = []): bool
    {
        $mode = config('trading.mode', 'paper');

        $riskLimit = RiskLimit::where('mode', $mode)->first();

        if (! $riskLimit) {
            return true;
        }

        if (! $this->checkDailyLoss($riskLimit, $state)) {
            return false;
        }

        if (! $this->checkPositionSize($orderRequest, $riskLimit)) {
            return false;
        }

        if (! $this->checkOrderRate($riskLimit)) {
            return false;
        }

        return true;
    }

    private function checkDailyLoss(RiskLimit $riskLimit, array $state): bool
    {
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
        $key = 'risk:order_rate:'.now()->format('Y-m-d-H-i');

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
        $today = now()->startOfDay();

        $orders = Order::where('status', 'filled')
            ->where('placed_at', '>=', $today)
            ->get();

        $pnl = 0;

        foreach ($orders as $order) {
            if ($order->side === 'sell') {
                $pnl += ($order->avg_fill_price * $order->filled_qty);
            } else {
                $pnl -= ($order->avg_fill_price * $order->filled_qty);
            }
        }

        return $pnl;
    }
}
