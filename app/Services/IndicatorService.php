<?php

namespace App\Services;

/**
 * Technical Indicator Calculator
 * Pure math computations — no DB dependency.
 */
class IndicatorService
{
    /**
     * Simple Moving Average
     */
    public static function sma(array $closes, int $period): ?float
    {
        if (count($closes) < $period) {
            return null;
        }
        $slice = array_slice($closes, -$period);
        return array_sum($slice) / $period;
    }

    /**
     * Exponential Moving Average — recursive / multiplier approach
     * $closes must be chronologically ordered (oldest first).
     */
    public static function ema(array $closes, int $period): ?float
    {
        if (count($closes) < $period) {
            return null;
        }

        $k = 2 / ($period + 1);
        // Seed EMA with first SMA
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        foreach (array_slice($closes, $period) as $close) {
            $ema = $close * $k + $ema * (1 - $k);
        }

        return $ema;
    }

    /**
     * Full EMA series (oldest first)
     */
    public static function emaSeries(array $closes, int $period): array
    {
        if (count($closes) < $period) {
            return [];
        }

        $k = 2 / ($period + 1);
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;
        $series = [$ema];

        foreach (array_slice($closes, $period) as $close) {
            $ema = $close * $k + $ema * (1 - $k);
            $series[] = $ema;
        }

        return $series;
    }

    /**
     * RSI (14-period) — Wilder's smoothed method
     */
    public static function rsi(array $closes, int $period = 14): ?float
    {
        if (count($closes) < $period + 1) {
            return null;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($closes); $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            $gains[] = max(0, $diff);
            $losses[] = max(0, -$diff);
        }

        // Initial average
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // Wilder's smoothing for remaining values
        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = ($avgGain * ($period - 1) + $gains[$i]) / $period;
            $avgLoss = ($avgLoss * ($period - 1) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    /**
     * MACD (12, 26, 9)
     * Returns ['macd' => float, 'signal' => float, 'histogram' => float]
     */
    public static function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): ?array
    {
        if (count($closes) < $slow + $signal) {
            return null;
        }

        $emaFastSeries = self::emaSeries($closes, $fast);
        $emaSlowSeries = self::emaSeries($closes, $slow);

        // Align by taking only last N values where both series overlap
        $diff = count($emaFastSeries) - count($emaSlowSeries);
        if ($diff > 0) {
            $emaFastSeries = array_slice($emaFastSeries, $diff);
        }

        $macdLine = array_map(fn($f, $s) => $f - $s, $emaFastSeries, $emaSlowSeries);

        if (count($macdLine) < $signal) {
            return null;
        }

        $signalLine = self::emaSeries($macdLine, $signal);
        $lastMacd = end($macdLine);
        $lastSignal = end($signalLine);

        return [
            'macd'      => $lastMacd,
            'signal'    => $lastSignal,
            'histogram' => $lastMacd - $lastSignal,
        ];
    }

    /**
     * Bollinger Bands (20, 2)
     */
    public static function bollingerBands(array $closes, int $period = 20, float $multiplier = 2.0): ?array
    {
        if (count($closes) < $period) {
            return null;
        }

        $slice = array_slice($closes, -$period);
        $mean = array_sum($slice) / $period;
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $slice)) / $period;
        $stdDev = sqrt($variance);

        return [
            'upper'  => $mean + $multiplier * $stdDev,
            'middle' => $mean,
            'lower'  => $mean - $multiplier * $stdDev,
        ];
    }

    /**
     * ATR (Average True Range) — 14 period
     */
    public static function atr(array $highs, array $lows, array $closes, int $period = 14): ?float
    {
        $n = min(count($highs), count($lows), count($closes));
        if ($n < $period + 1) {
            return null;
        }

        $trValues = [];
        for ($i = 1; $i < $n; $i++) {
            $tr = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );
            $trValues[] = $tr;
        }

        if (count($trValues) < $period) {
            return null;
        }

        // Wilder's smoothing
        $atr = array_sum(array_slice($trValues, 0, $period)) / $period;
        foreach (array_slice($trValues, $period) as $tr) {
            $atr = ($atr * ($period - 1) + $tr) / $period;
        }

        return $atr;
    }

    /**
     * Standard Pivot Points (Classic)
     */
    public static function pivotPoints(float $high, float $low, float $close): array
    {
        $pivot = ($high + $low + $close) / 3;
        return [
            'pivot'        => $pivot,
            'resistance_1' => 2 * $pivot - $low,
            'resistance_2' => $pivot + ($high - $low),
            'support_1'    => 2 * $pivot - $high,
            'support_2'    => $pivot - ($high - $low),
        ];
    }

    /**
     * Swing High/Low support and resistance from recent N candles
     */
    public static function swingSupportResistance(array $highs, array $lows, int $lookback = 20): array
    {
        $recentHighs = array_slice($highs, -$lookback);
        $recentLows = array_slice($lows, -$lookback);

        rsort($recentHighs);
        sort($recentLows);

        return [
            'resistance_1' => $recentHighs[0] ?? null,
            'resistance_2' => $recentHighs[1] ?? null,
            'support_1'    => $recentLows[0] ?? null,
            'support_2'    => $recentLows[1] ?? null,
        ];
    }
}
