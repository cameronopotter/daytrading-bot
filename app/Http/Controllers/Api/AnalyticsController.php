<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fill;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dailyPnL()
    {
        // Get fills from the last 90 days
        $startDate = now()->subDays(90)->startOfDay();

        // Get all fills grouped by date
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

            // Group fills by symbol to track round-trip trades
            $symbolFills = $dayFills->groupBy('symbol');

            foreach ($symbolFills as $symbol => $fills) {
                $buys = $fills->where('side', 'buy');
                $sells = $fills->where('side', 'sell');

                // Calculate totals
                $buyTotal += $buys->sum(fn($f) => $f->price * $f->qty);
                $sellTotal += $sells->sum(fn($f) => $f->price * $f->qty);

                // Count trades (each sell is a completed trade)
                $trades += $sells->count();

                // Count wins (sells where we made profit)
                foreach ($sells as $sell) {
                    // Find matching buy(s) - simplified: compare against average buy price
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
