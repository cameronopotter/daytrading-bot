<?php

use App\Http\Controllers\Api\AlpacaCredentialsController;
use App\Http\Controllers\Api\AlpacaStreamController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PanicController;
use App\Http\Controllers\Api\StrategyController;
use App\Http\Controllers\Api\TradingReadController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/stream/alpaca', [AlpacaStreamController::class, 'ingest']);

// Authenticated routes (require login)
Route::middleware(['web', 'auth'])->group(function () {
    // Health check
    Route::get('/health', [HealthController::class, 'show']);

    // Trading read routes
    Route::get('/account', [TradingReadController::class, 'account']);
    Route::get('/positions', [TradingReadController::class, 'positions']);
    Route::get('/orders', [TradingReadController::class, 'orders']);
    Route::get('/fills', [TradingReadController::class, 'fills']);

    // Analytics routes
    Route::get('/analytics/daily-pnl', [AnalyticsController::class, 'dailyPnL']);

    // Strategy control routes
    Route::get('/strategy/config', [StrategyController::class, 'show']);
    Route::post('/strategy/config', [StrategyController::class, 'update']);
    Route::put('/strategy/config', [StrategyController::class, 'update']);
    Route::post('/strategy/start', [StrategyController::class, 'start']);
    Route::post('/strategy/stop', [StrategyController::class, 'stop']);

    // Panic route
    Route::post('/panic', [PanicController::class, 'flatten']);

    // Alpaca credentials routes
    Route::post('/alpaca/test-connection', [AlpacaCredentialsController::class, 'testConnection']);
    Route::get('/alpaca/status', [AlpacaCredentialsController::class, 'status']);
});
