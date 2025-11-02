<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Strategy;
use App\Models\StrategyRun;
use Illuminate\Http\Request;

class StrategyController extends Controller
{
    public function show()
    {
        $strategy = Strategy::with('runs')->first();

        if (!$strategy) {
            // Create default strategy if none exists
            $strategy = Strategy::create([
                'name' => 'SMA Cross',
                'class' => \App\Trading\Strategies\SMA::class,
                'config' => [
                    'symbol' => 'AAPL',
                    'qty' => 10,
                    'fast' => 9,
                    'slow' => 21,
                    'bar_interval' => '1Min',
                ],
                'is_enabled' => false,
            ]);
        }

        return response()->json($strategy);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'config' => 'required|array',
            'config.symbol' => 'required|string',
            'config.qty' => 'required|numeric|min:1',
            'config.fast' => 'required|integer|min:1',
            'config.slow' => 'required|integer|min:1',
        ]);

        $strategy = Strategy::firstOrFail();
        $strategy->update(['config' => $validated['config']]);

        return response()->json($strategy);
    }

    public function start()
    {
        $strategy = Strategy::firstOrFail();
        $mode = config('trading.mode', 'paper');

        // Stop any running strategy runs
        StrategyRun::where('status', 'running')->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);

        // Create new run
        $run = StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => $mode,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Strategy started',
            'run' => $run,
        ]);
    }

    public function stop()
    {
        $runs = StrategyRun::where('status', 'running')->get();

        foreach ($runs as $run) {
            $run->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Strategy stopped',
            'count' => $runs->count(),
        ]);
    }
}
