<?php

namespace App\Trading\Strategies;

use App\Trading\Signals\Signal;

class SMA implements Strategy
{
    private array $config;

    private array $closePrices = [];

    private ?string $position = null; // null, 'long'

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 9,
            'slow' => 21,
            'bar_interval' => '1Min',
        ], $config);
    }

    public function onBar(array $bar, array $state): ?Signal
    {
        // Add close price to our rolling array
        $this->closePrices[] = $bar['close'];

        // Keep only the prices we need (slow period + buffer)
        $maxLength = $this->config['slow'] + 10;
        if (count($this->closePrices) > $maxLength) {
            array_shift($this->closePrices);
        }

        // Need enough data for both SMAs
        if (count($this->closePrices) < $this->config['slow']) {
            return Signal::noAction('Warming up: '.count($this->closePrices).'/'.$this->config['slow']);
        }

        // Calculate SMAs
        $fastSMA = $this->calculateSMA($this->config['fast']);
        $slowSMA = $this->calculateSMA($this->config['slow']);

        // Previous SMAs for cross detection
        $prevFastSMA = $this->calculateSMA($this->config['fast'], 1);
        $prevSlowSMA = $this->calculateSMA($this->config['slow'], 1);

        // Track current position from state
        $this->position = $state['position'] ?? null;

        // Detect crosses
        $crossUp = $prevFastSMA <= $prevSlowSMA && $fastSMA > $slowSMA;
        $crossDown = $prevFastSMA >= $prevSlowSMA && $fastSMA < $slowSMA;

        \Log::info('[SMA] Bar processed', [
            'symbol' => $bar['symbol'],
            'close' => $bar['close'],
            'fast_sma' => round($fastSMA, 2),
            'slow_sma' => round($slowSMA, 2),
            'position' => $this->position,
            'cross_up' => $crossUp,
            'cross_down' => $crossDown,
        ]);

        // Signal generation (flat-to-long only for MVP)
        if ($crossUp && $this->position !== 'long') {
            // Buy signal
            return Signal::buy(
                symbol: $this->config['symbol'],
                qty: $this->config['qty'],
                type: 'market'
            );
        }

        if ($crossDown && $this->position === 'long') {
            // Sell signal (close position)
            return Signal::sell(
                symbol: $this->config['symbol'],
                qty: $this->config['qty'],
                type: 'market'
            );
        }

        return Signal::noAction("No signal (Fast: $fastSMA, Slow: $slowSMA)");
    }

    public function name(): string
    {
        return "SMA({$this->config['fast']}/{$this->config['slow']})";
    }

    private function calculateSMA(int $period, int $offset = 0): float
    {
        $slice = array_slice($this->closePrices, -($period + $offset), $period);

        return array_sum($slice) / count($slice);
    }
}
