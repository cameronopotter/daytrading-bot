<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fill;
use App\Models\Order;
use App\Models\Position;
use App\Trading\Adapters\AlpacaAdapter;

class TradingReadController extends Controller
{
    public function account()
    {
        try {
            $user = auth()->user();

            \Log::info('Account endpoint called', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'has_credentials' => $user?->hasAlpacaCredentials(),
            ]);

            if (!$user) {
                \Log::error('No authenticated user found');
                return response()->json([
                    'error' => 'Not authenticated',
                ], 401);
            }

            if (!$user->hasAlpacaCredentials()) {
                \Log::warning('User has no Alpaca credentials', ['user_id' => $user->id]);
                return response()->json([
                    'error' => 'No Alpaca credentials configured. Please add your credentials in your profile.',
                ], 400);
            }

            \Log::info('Creating Alpaca adapter for user', ['user_id' => $user->id]);
            $adapter = AlpacaAdapter::fromUser($user);

            \Log::info('Fetching account data from Alpaca');
            $account = $adapter->getAccount();

            \Log::info('Account data fetched successfully', [
                'equity' => $account['equity'] ?? 'N/A',
                'buying_power' => $account['buying_power'] ?? 'N/A',
            ]);

            return response()->json($account);
        } catch (\Exception $e) {
            \Log::error('Account endpoint error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function positions()
    {
        $user = auth()->user();

        // Use user's preference if available, otherwise fall back to config
        $mode = 'paper';
        if ($user && $user->hasAlpacaCredentials()) {
            $mode = $user->alpaca_is_paper ? 'paper' : 'live';
        } else {
            $mode = config('trading.mode', 'paper');
        }

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
