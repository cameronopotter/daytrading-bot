<?php

namespace App\Trading\Strategies;

use App\Trading\Signals\Signal;
use App\Trading\Indicators\TechnicalIndicators;
use Illuminate\Support\Facades\Log;

class EnhancedSMA implements Strategy
{
    private array $config;
    private array $bars = []; // Store full bar data (OHLCV)
    private ?string $position = null;
    private ?float $entryPrice = null;
    private ?float $stopLoss = null;
    private ?float $takeProfit = null;
    private ?float $trailingStop = null;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            // Core SMA parameters
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 9,
            'slow' => 21,
            'bar_interval' => '1Min',

            // Risk management
            'atr_period' => 14,
            'stop_loss_atr_multiplier' => 2.0,
            'take_profit_atr_multiplier' => 3.0,
            'use_trailing_stop' => true,
            'trailing_stop_atr_multiplier' => 2.0,

            // Market regime detection
            'use_regime_filter' => true,
            'adx_period' => 14,
            'adx_trending_threshold' => 25,
            'adx_ranging_threshold' => 20,
            'bb_period' => 20,
            'bb_std_dev' => 2.0,

            // RSI filter
            'use_rsi_filter' => true,
            'rsi_period' => 14,
            'rsi_overbought' => 70,
            'rsi_oversold' => 30,

            // MACD confirmation
            'use_macd_confirmation' => true,
            'macd_fast' => 12,
            'macd_slow' => 26,
            'macd_signal' => 9,

            // Volume confirmation
            'use_volume_filter' => true,
            'volume_period' => 20,
            'volume_multiplier' => 1.5, // 1.5x average volume

            // Time-of-day filter
            'use_time_filter' => true,
            'trading_hours' => [
                ['start' => '09:30', 'end' => '10:30'], // Morning session
                ['start' => '15:00', 'end' => '16:00'], // Afternoon session
            ],

            // Position sizing
            'use_dynamic_sizing' => true,
            'risk_per_trade' => 0.01, // 1% of account
            'max_position_size' => 100,
            'account_balance' => 100000, // Will be updated from state

            // Multi-timeframe
            'use_mtf_filter' => true,
            'higher_timeframe' => '1Hour', // Not implemented in this version
        ], $config);
    }

    public function onBar(array $bar, array $state): ?Signal
    {
        // Add bar to history
        $this->bars[] = $bar;

        // Keep rolling window
        $maxBars = max(200, $this->config['slow'] + 50); // Keep enough for all calculations
        if (count($this->bars) > $maxBars) {
            array_shift($this->bars);
        }

        // Update position state
        $this->position = $state['position'] ?? null;

        // Update account balance for position sizing
        if (isset($state['account_balance'])) {
            $this->config['account_balance'] = $state['account_balance'];
        }

        // Check if position has active stops
        if ($this->position === 'long' && isset($state['entry_price'])) {
            $this->entryPrice = $state['entry_price'];
            $this->stopLoss = $state['stop_loss'] ?? null;
            $this->takeProfit = $state['take_profit'] ?? null;
            $this->trailingStop = $state['trailing_stop'] ?? null;
        }

        // Need enough data
        if (count($this->bars) < $this->config['slow'] + $this->config['atr_period']) {
            return Signal::noAction('Warming up: ' . count($this->bars) . ' bars collected');
        }

        // Check if we have an open position with stops
        if ($this->position === 'long') {
            return $this->manageOpenPosition($bar);
        }

        // Look for entry signals
        return $this->lookForEntry($bar);
    }

    private function lookForEntry(array $bar): ?Signal
    {
        // Time-of-day filter
        if ($this->config['use_time_filter'] && !$this->isWithinTradingHours($bar)) {
            return Signal::noAction('Outside trading hours');
        }

        // Market regime detection
        $regime = $this->detectMarketRegime();

        if ($this->config['use_regime_filter'] && $regime === 'RANGING') {
            return Signal::noAction("Market regime: $regime (skipping SMA strategy)");
        }

        if ($regime === 'UNCERTAIN') {
            Log::info('[STRATEGY] Uncertain market regime - would reduce position size if trading');
        }

        // Calculate SMAs
        $prices = array_column($this->bars, 'close');
        $fastSMA = TechnicalIndicators::calculateSMA($prices, $this->config['fast']);
        $slowSMA = TechnicalIndicators::calculateSMA($prices, $this->config['slow']);

        if ($fastSMA === null || $slowSMA === null) {
            return Signal::noAction('Insufficient data for SMA calculation');
        }

        // Previous SMAs for cross detection
        $prevPrices = array_slice($prices, 0, -1);
        $prevFastSMA = TechnicalIndicators::calculateSMA($prevPrices, $this->config['fast']);
        $prevSlowSMA = TechnicalIndicators::calculateSMA($prevPrices, $this->config['slow']);

        if ($prevFastSMA === null || $prevSlowSMA === null) {
            return Signal::noAction('Insufficient data for previous SMA');
        }

        // Detect crossover
        $crossUp = $prevFastSMA <= $prevSlowSMA && $fastSMA > $slowSMA;

        if (!$crossUp) {
            return Signal::noAction("No crossover (Fast: " . round($fastSMA, 2) . ", Slow: " . round($slowSMA, 2) . ")");
        }

        // We have a crossover - now apply filters

        // RSI Filter
        if ($this->config['use_rsi_filter']) {
            $rsi = TechnicalIndicators::calculateRSI($prices, $this->config['rsi_period']);

            if ($rsi === null) {
                return Signal::noAction('Insufficient data for RSI');
            }

            if ($rsi > $this->config['rsi_overbought']) {
                return Signal::noAction("RSI overbought: " . round($rsi, 2) . " > " . $this->config['rsi_overbought']);
            }

            Log::info('[STRATEGY] RSI check passed', ['rsi' => round($rsi, 2)]);
        }

        // MACD Confirmation
        if ($this->config['use_macd_confirmation']) {
            $macd = TechnicalIndicators::calculateMACD(
                $prices,
                $this->config['macd_fast'],
                $this->config['macd_slow'],
                $this->config['macd_signal']
            );

            if ($macd === null) {
                return Signal::noAction('Insufficient data for MACD');
            }

            if ($macd['histogram'] < 0) {
                return Signal::noAction("MACD histogram negative: " . round($macd['histogram'], 4));
            }

            Log::info('[STRATEGY] MACD check passed', ['histogram' => round($macd['histogram'], 4)]);
        }

        // Volume Confirmation
        if ($this->config['use_volume_filter']) {
            $avgVolume = TechnicalIndicators::calculateAverageVolume($this->bars, $this->config['volume_period']);
            $currentVolume = $bar['volume'] ?? 0;

            if ($avgVolume === null) {
                Log::warning('[STRATEGY] Cannot calculate average volume');
            } elseif ($currentVolume < $avgVolume * $this->config['volume_multiplier']) {
                return Signal::noAction("Low volume: " . $currentVolume . " < " . round($avgVolume * $this->config['volume_multiplier']));
            }

            Log::info('[STRATEGY] Volume check passed', [
                'current' => $currentVolume,
                'avg' => round($avgVolume ?? 0),
                'required' => round(($avgVolume ?? 0) * $this->config['volume_multiplier'])
            ]);
        }

        // All filters passed - calculate position size and stops
        $atr = TechnicalIndicators::calculateATR($this->bars, $this->config['atr_period']);

        if ($atr === null) {
            return Signal::noAction('Insufficient data for ATR');
        }

        $entryPrice = $bar['close'];
        $stopLoss = $entryPrice - ($atr * $this->config['stop_loss_atr_multiplier']);
        $takeProfit = $entryPrice + ($atr * $this->config['take_profit_atr_multiplier']);

        // Calculate position size
        $qty = $this->calculatePositionSize($entryPrice, $stopLoss, $regime);

        if ($qty <= 0) {
            return Signal::noAction('Calculated position size is zero');
        }

        Log::info('[STRATEGY] 🎯 BUY SIGNAL GENERATED', [
            'entry_price' => round($entryPrice, 2),
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
            'atr' => round($atr, 2),
            'qty' => $qty,
            'risk_per_share' => round($entryPrice - $stopLoss, 2),
            'regime' => $regime,
        ]);

        // Return buy signal with stops
        return Signal::buy(
            symbol: $this->config['symbol'],
            qty: $qty,
            type: 'market',
            stopLoss: $stopLoss,
            takeProfit: $takeProfit
        );
    }

    private function manageOpenPosition(array $bar): ?Signal
    {
        $currentPrice = $bar['close'];

        // Check stop-loss
        if ($this->stopLoss !== null && $currentPrice <= $this->stopLoss) {
            Log::warning('[STRATEGY] 🛑 STOP-LOSS HIT', [
                'current_price' => $currentPrice,
                'stop_loss' => $this->stopLoss,
                'loss' => round(($currentPrice - $this->entryPrice) * $this->config['qty'], 2)
            ]);

            return Signal::sell(
                symbol: $this->config['symbol'],
                qty: $this->config['qty'],
                type: 'market',
                reason: 'stop_loss'
            );
        }

        // Check take-profit
        if ($this->takeProfit !== null && $currentPrice >= $this->takeProfit) {
            Log::info('[STRATEGY] 🎯 TAKE-PROFIT HIT', [
                'current_price' => $currentPrice,
                'take_profit' => $this->takeProfit,
                'profit' => round(($currentPrice - $this->entryPrice) * $this->config['qty'], 2)
            ]);

            return Signal::sell(
                symbol: $this->config['symbol'],
                qty: $this->config['qty'],
                type: 'market',
                reason: 'take_profit'
            );
        }

        // Update trailing stop
        if ($this->config['use_trailing_stop'] && $this->entryPrice !== null) {
            $atr = TechnicalIndicators::calculateATR($this->bars, $this->config['atr_period']);

            if ($atr !== null) {
                $newTrailingStop = $currentPrice - ($atr * $this->config['trailing_stop_atr_multiplier']);

                if ($this->trailingStop === null || $newTrailingStop > $this->trailingStop) {
                    $oldTrailing = $this->trailingStop;
                    $this->trailingStop = $newTrailingStop;

                    // Also update the main stop-loss to the trailing stop
                    if ($this->trailingStop > $this->stopLoss) {
                        $this->stopLoss = $this->trailingStop;

                        Log::info('[STRATEGY] 📈 TRAILING STOP UPDATED', [
                            'old_trailing' => $oldTrailing,
                            'new_trailing' => round($this->trailingStop, 2),
                            'current_price' => $currentPrice,
                            'locked_profit' => round(($this->stopLoss - $this->entryPrice) * $this->config['qty'], 2)
                        ]);
                    }
                }
            }
        }

        // Check for exit signal (SMA cross down)
        $prices = array_column($this->bars, 'close');
        $fastSMA = TechnicalIndicators::calculateSMA($prices, $this->config['fast']);
        $slowSMA = TechnicalIndicators::calculateSMA($prices, $this->config['slow']);

        if ($fastSMA !== null && $slowSMA !== null) {
            $prevPrices = array_slice($prices, 0, -1);
            $prevFastSMA = TechnicalIndicators::calculateSMA($prevPrices, $this->config['fast']);
            $prevSlowSMA = TechnicalIndicators::calculateSMA($prevPrices, $this->config['slow']);

            if ($prevFastSMA !== null && $prevSlowSMA !== null) {
                $crossDown = $prevFastSMA >= $prevSlowSMA && $fastSMA < $slowSMA;

                if ($crossDown) {
                    Log::info('[STRATEGY] 📉 SMA CROSS DOWN - EXIT SIGNAL', [
                        'fast_sma' => round($fastSMA, 2),
                        'slow_sma' => round($slowSMA, 2),
                        'current_price' => $currentPrice,
                        'pnl' => round(($currentPrice - $this->entryPrice) * $this->config['qty'], 2)
                    ]);

                    return Signal::sell(
                        symbol: $this->config['symbol'],
                        qty: $this->config['qty'],
                        type: 'market',
                        reason: 'sma_cross_down'
                    );
                }
            }
        }

        return Signal::noAction("Holding position (Price: $currentPrice, Stop: " . round($this->stopLoss ?? 0, 2) . ", Target: " . round($this->takeProfit ?? 0, 2) . ")");
    }

    private function detectMarketRegime(): string
    {
        if (!$this->config['use_regime_filter']) {
            return 'UNKNOWN';
        }

        $adx = TechnicalIndicators::calculateADX($this->bars, $this->config['adx_period']);
        $prices = array_column($this->bars, 'close');
        $bbWidth = TechnicalIndicators::calculateBBWidth($prices, $this->config['bb_period'], $this->config['bb_std_dev']);

        if ($adx === null || $bbWidth === null) {
            return 'UNKNOWN';
        }

        // Calculate average BB width for comparison
        $recentPrices = array_slice($prices, -50);
        $avgBBWidth = 0;
        $count = 0;

        for ($i = $this->config['bb_period']; $i < count($recentPrices); $i++) {
            $slice = array_slice($recentPrices, $i - $this->config['bb_period'], $this->config['bb_period']);
            $width = TechnicalIndicators::calculateBBWidth($slice, $this->config['bb_period'], $this->config['bb_std_dev']);
            if ($width !== null) {
                $avgBBWidth += $width;
                $count++;
            }
        }

        $avgBBWidth = $count > 0 ? $avgBBWidth / $count : $bbWidth;

        // Determine regime
        if ($adx > $this->config['adx_trending_threshold'] && $bbWidth > $avgBBWidth * 1.2) {
            Log::info('[REGIME] TRENDING market detected', [
                'adx' => round($adx, 2),
                'bb_width' => round($bbWidth, 4),
                'avg_bb_width' => round($avgBBWidth, 4)
            ]);
            return 'TRENDING';
        } elseif ($adx < $this->config['adx_ranging_threshold'] && $bbWidth < $avgBBWidth * 0.8) {
            Log::info('[REGIME] RANGING market detected', [
                'adx' => round($adx, 2),
                'bb_width' => round($bbWidth, 4),
                'avg_bb_width' => round($avgBBWidth, 4)
            ]);
            return 'RANGING';
        } else {
            Log::info('[REGIME] UNCERTAIN market conditions', [
                'adx' => round($adx, 2),
                'bb_width' => round($bbWidth, 4),
                'avg_bb_width' => round($avgBBWidth, 4)
            ]);
            return 'UNCERTAIN';
        }
    }

    private function calculatePositionSize(float $entryPrice, float $stopLoss, string $regime): int
    {
        if (!$this->config['use_dynamic_sizing']) {
            // Use fixed size but respect regime
            $baseQty = $this->config['qty'];

            if ($regime === 'UNCERTAIN') {
                return (int)floor($baseQty * 0.5); // Half size in uncertain markets
            }

            return $baseQty;
        }

        $accountBalance = $this->config['account_balance'];
        $riskPerTrade = $this->config['risk_per_trade'];
        $maxPositionSize = $this->config['max_position_size'];

        // Calculate risk amount in dollars
        $riskAmount = $accountBalance * $riskPerTrade;

        // Calculate risk per share
        $riskPerShare = abs($entryPrice - $stopLoss);

        if ($riskPerShare <= 0) {
            Log::warning('[POSITION SIZING] Risk per share is zero or negative');
            return $this->config['qty'];
        }

        // Calculate shares
        $shares = floor($riskAmount / $riskPerShare);

        // Apply regime adjustment
        if ($regime === 'UNCERTAIN') {
            $shares = floor($shares * 0.5); // Half size in uncertain markets
        }

        // Cap at maximum
        $shares = min($shares, $maxPositionSize);

        Log::info('[POSITION SIZING] Calculated position', [
            'account_balance' => $accountBalance,
            'risk_per_trade' => $riskPerTrade * 100 . '%',
            'risk_amount' => round($riskAmount, 2),
            'risk_per_share' => round($riskPerShare, 2),
            'calculated_shares' => $shares,
            'regime_adjustment' => $regime,
            'max_allowed' => $maxPositionSize
        ]);

        return max(1, (int)$shares);
    }

    private function isWithinTradingHours(array $bar): bool
    {
        if (!isset($bar['timestamp'])) {
            return true; // If no timestamp, allow trading
        }

        $timestamp = is_numeric($bar['timestamp']) ? $bar['timestamp'] : strtotime($bar['timestamp']);
        $time = date('H:i', $timestamp);

        foreach ($this->config['trading_hours'] as $window) {
            if ($time >= $window['start'] && $time <= $window['end']) {
                return true;
            }
        }

        return false;
    }

    public function name(): string
    {
        return "Enhanced SMA({$this->config['fast']}/{$this->config['slow']}) with Regime Detection & Risk Management";
    }

    public function getStopLoss(): ?float
    {
        return $this->stopLoss;
    }

    public function getTakeProfit(): ?float
    {
        return $this->takeProfit;
    }

    public function getTrailingStop(): ?float
    {
        return $this->trailingStop;
    }
}
