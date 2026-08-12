<?php

namespace App\Services;

/**
 * 30-Day Forward Outlook Engine
 *
 * Unlike PredictionService (day-by-day compounding forecast tuned for 7 days),
 * this projects a single 30-calendar-day target directly from a log-linear
 * regression fit over recent closes, so error doesn't compound step by step.
 *
 * Method:
 *  - Fit a linear regression on log(close) over the last N trading days.
 *  - Extrapolate the fitted daily log-return 30 calendar days forward to get
 *    a target price, plus an upper/lower band from the regression's residual
 *    volatility (~80% interval).
 *  - R² of the fit measures how reliable the trend line actually is — a
 *    trend with low R² is downweighted heavily in the confidence score.
 *  - RSI/MACD/SMA/volume are used only to sanity-check and damp the trend
 *    (e.g. an uptrend stock that's already deeply overbought gets its
 *    upside capped), not to independently move the price target.
 */
class Outlook30Service
{
    private const HORIZON_DAYS = 30;
    private const LOOKBACK     = 60; // trading days used for the regression fit

    /**
     * @param  array  $priceRows  Ascending-sorted rows (oldest first) with
     *                            keys: date, open, high, low, close, volume
     * @return array|null  null if there isn't enough history to trust a fit
     */
    public static function project(array $priceRows): ?array
    {
        if (count($priceRows) < 50) {
            return null;
        }

        $closes  = array_map('floatval', array_column($priceRows, 'close'));
        $volumes = array_map('floatval', array_column($priceRows, 'volume'));

        $currentClose = end($closes);
        if ($currentClose <= 0) {
            return null;
        }

        $lookback = min(self::LOOKBACK, count($closes));
        $logCloses = array_map(fn($c) => log(max($c, 0.01)), array_slice($closes, -$lookback));

        $fit = self::regressionFit($logCloses);
        if ($fit === null) {
            return null;
        }
        ['slope' => $slope, 'r2' => $r2, 'residualStd' => $residualStd] = $fit;

        // ── Extrapolate 30 calendar days (proportional scaling from trading-day slope) ──
        $tradingDaysIn30Cal = self::HORIZON_DAYS * (5 / 7);
        $projectedLogChange = $slope * $tradingDaysIn30Cal;

        // ── Supporting indicators used only to sanity-check/damp the raw trend ──
        $rsi   = IndicatorService::rsi($closes, 14);
        $macd  = IndicatorService::macd($closes);
        $sma20 = IndicatorService::sma($closes, 20);
        $sma50 = IndicatorService::sma($closes, 50);
        $sma200 = IndicatorService::sma($closes, count($closes) >= 200 ? 200 : count($closes));

        $dampFactor = 1.0;
        $reasons = [];

        if ($rsi !== null && $rsi >= 75 && $projectedLogChange > 0) {
            $dampFactor *= 0.55;
            $reasons[] = ['icon' => '⚠️', 'text' => "RSI at " . round($rsi, 1) . " — already overbought, upside capped"];
        } elseif ($rsi !== null && $rsi <= 25 && $projectedLogChange < 0) {
            $dampFactor *= 0.55;
            $reasons[] = ['icon' => '⚠️', 'text' => "RSI at " . round($rsi, 1) . " — already oversold, downside capped"];
        }

        if ($sma20 !== null && $sma50 !== null) {
            if ($currentClose > $sma20 && $sma20 > $sma50 && $projectedLogChange > 0) {
                $reasons[] = ['icon' => '📐', 'text' => "Price above rising SMA20/SMA50 — trend supported by moving averages"];
            } elseif ($currentClose < $sma20 && $sma20 < $sma50 && $projectedLogChange < 0) {
                $reasons[] = ['icon' => '📐', 'text' => "Price below falling SMA20/SMA50 — downtrend supported by moving averages"];
            } else {
                $dampFactor *= 0.75;
            }
        }

        if ($sma200 !== null) {
            if ($currentClose > $sma200 && $projectedLogChange > 0) {
                $reasons[] = ['icon' => '🏔️', 'text' => "Price above SMA200 — long-term uptrend backdrop"];
            } elseif ($currentClose < $sma200 && $projectedLogChange > 0) {
                $dampFactor *= 0.7;
            }
        }

        if ($macd !== null) {
            if ($macd['histogram'] > 0 && $projectedLogChange > 0) {
                $reasons[] = ['icon' => '🚀', 'text' => "MACD histogram positive — bullish momentum confirms trend"];
            } elseif ($macd['histogram'] < 0 && $projectedLogChange < 0) {
                $reasons[] = ['icon' => '🔻', 'text' => "MACD histogram negative — bearish momentum confirms trend"];
            } elseif (($macd['histogram'] > 0 && $projectedLogChange < 0) || ($macd['histogram'] < 0 && $projectedLogChange > 0)) {
                $dampFactor *= 0.8;
            }
        }

        // Liquidity: need consistent trading volume for the trend to be tradeable
        $avgVol20 = count($volumes) >= 20 ? array_sum(array_slice($volumes, -20)) / 20 : array_sum($volumes) / max(count($volumes), 1);

        $dampedLogChange = $projectedLogChange * $dampFactor;

        $targetPrice = round($currentClose * exp($dampedLogChange), 2);

        // ── Confidence interval from residual volatility, scaled to the horizon ──
        $horizonStd = $residualStd * sqrt($tradingDaysIn30Cal);
        $targetLow  = round($currentClose * exp($dampedLogChange - 1.28 * $horizonStd), 2);
        $targetHigh = round($currentClose * exp($dampedLogChange + 1.28 * $horizonStd), 2);

        $expectedReturnPct = round(($targetPrice - $currentClose) / $currentClose * 100, 2);

        // ── Confidence score: trend reliability (R²) discounted by band width and damping ──
        $bandWidthPct = $currentClose > 0 ? (($targetHigh - $targetLow) / $currentClose * 100) : 100;
        $confidence = 90 * $r2 * $dampFactor;
        $confidence -= min(30, $bandWidthPct * 0.5); // wider band = less confident
        $confidence = (int) round(max(10, min(95, $confidence)));

        $direction = $expectedReturnPct > 1.5 ? 'up' : ($expectedReturnPct < -1.5 ? 'down' : 'neutral');

        if (empty($reasons)) {
            $reasons[] = ['icon' => '📊', 'text' => "Trend fit is weak (R²=" . round($r2, 2) . ") — low-conviction projection"];
        }

        return [
            'current_price'        => round($currentClose, 2),
            'target_price'         => $targetPrice,
            'target_low'           => $targetLow,
            'target_high'          => $targetHigh,
            'expected_return_pct'  => $expectedReturnPct,
            'confidence'           => $confidence,
            'r_squared'            => round($r2, 3),
            'direction'            => $direction,
            'avg_volume_20d'       => (int) round($avgVol20),
            'rsi'                  => $rsi !== null ? round($rsi, 1) : null,
            'reasons'              => $reasons,
        ];
    }

    /**
     * Ordinary least squares fit of y = a + b*x over evenly-spaced x (0..n-1).
     * Returns slope, R², and residual standard deviation (in y units, i.e. log-price).
     */
    private static function regressionFit(array $y): ?array
    {
        $n = count($y);
        if ($n < 10) {
            return null;
        }

        $xMean = ($n - 1) / 2;
        $yMean = array_sum($y) / $n;

        $num = 0.0;
        $den = 0.0;
        foreach ($y as $i => $v) {
            $num += ($i - $xMean) * ($v - $yMean);
            $den += ($i - $xMean) ** 2;
        }

        if ($den == 0.0) {
            return null;
        }

        $slope = $num / $den;
        $intercept = $yMean - $slope * $xMean;

        $ssTot = 0.0;
        $ssRes = 0.0;
        foreach ($y as $i => $v) {
            $predicted = $intercept + $slope * $i;
            $ssRes += ($v - $predicted) ** 2;
            $ssTot += ($v - $yMean) ** 2;
        }

        $r2 = $ssTot > 0 ? max(0.0, 1 - $ssRes / $ssTot) : 0.0;
        $residualStd = sqrt($ssRes / max(1, $n - 2));

        return ['slope' => $slope, 'r2' => $r2, 'residualStd' => $residualStd];
    }
}
