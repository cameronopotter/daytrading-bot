<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        // If user is authenticated and has credentials, use their preference
        // Otherwise fall back to config file
        $mode = 'paper'; // default
        if ($user && $user->hasAlpacaCredentials()) {
            $mode = $user->alpaca_is_paper ? 'paper' : 'live';
        } else {
            $mode = config('trading.mode', 'paper');
        }

        return response()->json([
            'status' => 'ok',
            'mode' => $mode,
        ]);
    }
}
