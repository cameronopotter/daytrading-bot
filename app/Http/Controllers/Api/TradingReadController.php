<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fill;
use App\Models\Order;
use App\Models\Position;
use App\Trading\Adapters\AlpacaAdapter;

class TradingReadController extends Controller
{
    public function account(AlpacaAdapter $adapter)
    {
        try {
            $account = $adapter->getAccount();

            return response()->json($account);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function positions()
    {
        $mode = config('trading.mode', 'paper');

        $positions = Position::where('mode', $mode)
            ->where('qty', '>', 0)
            ->get();

        return response()->json($positions);
    }

    public function orders()
    {
        $orders = Order::with('strategyRun.strategy')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($orders);
    }

    public function fills()
    {
        $fills = Fill::with('order')
            ->orderBy('fill_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($fills);
    }
}
