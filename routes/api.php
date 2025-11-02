<?php

use App\Http\Controllers\Api\AlpacaStreamController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PanicController;
use App\Http\Controllers\Api\StrategyController;
use App\Http\Controllers\Api\TradingReadController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/health', [HealthController::class, 'show']);
Route::post('/stream/alpaca', [AlpacaStreamController::class, 'ingest']);

// Trading read routes
Route::get('/account', [TradingReadController::class, 'account']);
Route::get('/positions', [TradingReadController::class, 'positions']);
Route::get('/orders', [TradingReadController::class, 'orders']);
Route::get('/fills', [TradingReadController::class, 'fills']);

// Strategy control routes
Route::get('/strategy/config', [StrategyController::class, 'show']);
Route::post('/strategy/config', [StrategyController::class, 'update']);
Route::post('/strategy/start', [StrategyController::class, 'start']);
Route::post('/strategy/stop', [StrategyController::class, 'stop']);

// Panic route
Route::post('/panic', [PanicController::class, 'flatten']);
