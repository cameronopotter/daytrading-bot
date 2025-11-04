<?php

namespace App\Trading\Indicators;

class TechnicalIndicators
{
    public static function calculateATR(array $bars, int $period = 14): ?float
    {
        if (count($bars) < $period + 1) {
            return null;
        }

        $trueRanges = [];

        for ($i = 1; $i < count($bars); $i++) {
            $high = $bars[$i]['high'];
            $low = $bars[$i]['low'];
            $prevClose = $bars[$i - 1]['close'];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );

            $trueRanges[] = $tr;
        }

        $recentTR = array_slice($trueRanges, -$period);

        return array_sum($recentTR) / count($recentTR);
    }

    public static function calculateRSI(array $prices, int $period = 14): ?float
    {
        if (count($prices) < $period + 1) {
            return null;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];

            if ($change > 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        $recentGains = array_slice($gains, -$period);
        $recentLosses = array_slice($losses, -$period);

        $avgGain = array_sum($recentGains) / $period;
        $avgLoss = array_sum($recentLosses) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return $rsi;
    }

    public static function calculateMACD(array $prices, int $fastPeriod = 12, int $slowPeriod = 26, int $signalPeriod = 9): ?array
    {
        if (count($prices) < $slowPeriod + $signalPeriod) {
            return null;
        }

        $fastEMA = self::calculateEMA($prices, $fastPeriod);
        $slowEMA = self::calculateEMA($prices, $slowPeriod);

        if ($fastEMA === null || $slowEMA === null) {
            return null;
        }

        $macdLine = $fastEMA - $slowEMA;

        return [
            'macd' => $macdLine,
            'signal' => $macdLine,
            'histogram' => 0,
        ];
    }

    public static function calculateEMA(array $prices, int $period): ?float
    {
        if (count($prices) < $period) {
            return null;
        }

        $k = 2 / ($period + 1);

        $sma = array_sum(array_slice($prices, -$period, $period)) / $period;
        $ema = $sma;

        $currentPrice = end($prices);
        $ema = ($currentPrice * $k) + ($ema * (1 - $k));

        return $ema;
    }

    public static function calculateSMA(array $prices, int $period): ?float
    {
        if (count($prices) < $period) {
            return null;
        }

        $slice = array_slice($prices, -$period);

        return array_sum($slice) / count($slice);
    }

    public static function calculateADX(array $bars, int $period = 14): ?float
    {
        if (count($bars) < $period + 1) {
            return null;
        }

        $plusDM = [];
        $minusDM = [];
        $trueRanges = [];

        for ($i = 1; $i < count($bars); $i++) {
            $high = $bars[$i]['high'];
            $low = $bars[$i]['low'];
            $prevHigh = $bars[$i - 1]['high'];
            $prevLow = $bars[$i - 1]['low'];
            $prevClose = $bars[$i - 1]['close'];

            $upMove = $high - $prevHigh;
            $downMove = $prevLow - $low;

            if ($upMove > $downMove && $upMove > 0) {
                $plusDM[] = $upMove;
            } else {
                $plusDM[] = 0;
            }

            if ($downMove > $upMove && $downMove > 0) {
                $minusDM[] = $downMove;
            } else {
                $minusDM[] = 0;
            }

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            $trueRanges[] = $tr;
        }

        $recentPlusDM = array_slice($plusDM, -$period);
        $recentMinusDM = array_slice($minusDM, -$period);
        $recentTR = array_slice($trueRanges, -$period);

        $avgPlusDM = array_sum($recentPlusDM) / $period;
        $avgMinusDM = array_sum($recentMinusDM) / $period;
        $avgTR = array_sum($recentTR) / $period;

        if ($avgTR == 0) {
            return 0;
        }

        $plusDI = 100 * ($avgPlusDM / $avgTR);
        $minusDI = 100 * ($avgMinusDM / $avgTR);

        $diSum = $plusDI + $minusDI;
        if ($diSum == 0) {
            return 0;
        }

        $dx = 100 * abs($plusDI - $minusDI) / $diSum;

        return $dx;
    }

    public static function calculateBollingerBands(array $prices, int $period = 20, float $stdDev = 2): ?array
    {
        if (count($prices) < $period) {
            return null;
        }

        $sma = self::calculateSMA($prices, $period);

        if ($sma === null) {
            return null;
        }

        $slice = array_slice($prices, -$period);
        $variance = 0;

        foreach ($slice as $price) {
            $variance += pow($price - $sma, 2);
        }

        $variance = $variance / $period;
        $standardDeviation = sqrt($variance);

        return [
            'upper' => $sma + ($stdDev * $standardDeviation),
            'middle' => $sma,
            'lower' => $sma - ($stdDev * $standardDeviation),
            'width' => ($stdDev * $standardDeviation * 2),
        ];
    }

    public static function calculateBBWidth(array $prices, int $period = 20, float $stdDev = 2): ?float
    {
        $bb = self::calculateBollingerBands($prices, $period, $stdDev);

        if ($bb === null || $bb['middle'] == 0) {
            return null;
        }

        return ($bb['upper'] - $bb['lower']) / $bb['middle'];
    }

    public static function calculateAverageVolume(array $bars, int $period = 20): ?float
    {
        if (count($bars) < $period) {
            return null;
        }

        $volumes = array_map(fn ($bar) => $bar['volume'] ?? 0, $bars);
        $recentVolumes = array_slice($volumes, -$period);

        return array_sum($recentVolumes) / count($recentVolumes);
    }
}
