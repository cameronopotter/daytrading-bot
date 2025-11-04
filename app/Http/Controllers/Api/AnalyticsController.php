<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fill;

class AnalyticsController extends Controller
{
    public function dailyPnL()
    {
        $startDate = now()->subDays(90)->startOfDay();

        $fills = Fill::where('fill_at', '>=', $startDate)
            ->orderBy('fill_at', 'asc')
            ->get()
            ->groupBy(function ($fill) {
                return $fill->fill_at->format('Y-m-d');
            });

        $dailyPnL = [];

        foreach ($fills as $date => $dayFills) {
            $buyTotal = 0;
            $sellTotal = 0;
            $trades = 0;
            $wins = 0;

            $symbolFills = $dayFills->groupBy('symbol');

            foreach ($symbolFills as $symbol => $fills) {
                $buys = $fills->where('side', 'buy');
                $sells = $fills->where('side', 'sell');

                $buyTotal += $buys->sum(fn ($f) => $f->price * $f->qty);
                $sellTotal += $sells->sum(fn ($f) => $f->price * $f->qty);

                $trades += $sells->count();

                foreach ($sells as $sell) {
                    $avgBuyPrice = $buys->isEmpty() ? 0 : $buys->avg('price');
                    if ($sell->price > $avgBuyPrice) {
                        $wins++;
                    }
                }
            }

            $pnl = $sellTotal - $buyTotal;

            $dailyPnL[] = [
                'date' => $date,
                'pnl' => round($pnl, 2),
                'trades' => $trades,
                'wins' => $wins,
            ];
        }

        return response()->json([
            'daily_pnl' => $dailyPnL,
        ]);
    }
}
