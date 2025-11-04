<?php

namespace App\Providers;

use App\Trading\Adapters\AlpacaAdapter;
use App\Trading\BrokerAdapter;
use Illuminate\Support\ServiceProvider;

class TradingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BrokerAdapter::class, AlpacaAdapter::class);
        $this->app->singleton(AlpacaAdapter::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
