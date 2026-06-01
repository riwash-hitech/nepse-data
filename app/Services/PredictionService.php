<?php

namespace App\Services;

/**
 * 7-Day Price Prediction Engine
 *
 * Uses:
 *  - Linear regression trend on recent prices
 *  - RSI momentum bias
 *  - Day-of-week historical pattern analysis
 *  - ATR-based range estimation
 *  - Bollinger Band position
 *  - MACD histogram direction
 *
 * Returns one forecast entry per next 7 trading days.
 */
class PredictionService
{
    /**
     * Generate 7 trading-day forecasts for the given symbol.
     *
     * @param  array  $priceRows  Ascending-sorted rows from NepseScraperService::fetchHistoricalPrices()
     * @return array  Array of 7 forecast objects, each with:
     *   date, day_name, direction ('up'|'down'|'neutral'),
     *   confidence (0-100), predicted_price, predicted_low, predicted_high,
     *   change_pct, reasons[]
     */
    public static function forecast(array $priceRows): array
    {
        if (count($priceRows) < 30) {
            return [];
        }

        $closes  = array_column($priceRows, 'close');
        $highs   = array_column($priceRows, 'high');
        $lows    = array_column($priceRows, 'low');
        $volumes = array_column($priceRows, 'volume');

        $closes  = array_map('floatval', $closes);
        $highs   = array_map('floatval', $highs);
        $lows    = array_map('floatval', $lows);
        $volumes = array_map('intval', $volumes);

        $lastClose = end($closes);
        $lastDate  = end($priceRows)['date'];

        // ── ATR (14-day) for range estimation ────────────────────────────────
        $atr = self::atr($highs, $lows, $closes, 14);
        $atr = $atr ?? ($lastClose * 0.02); // fallback 2%

        // ── RSI (14-day) ──────────────────────────────────────────────────────
        $rsi = self::rsi($closes, 14);

        // ── Linear regression slope on last 20 days ───────────────────────────
        $lookback  = min(20, count($closes));
        $recentC   = array_slice($closes, -$lookback);
        $slope     = self::linearRegressionSlope($recentC);
        $slopePct  = $lastClose > 0 ? ($slope / $lastClose * 100) : 0;

        // ── Short-term momentum (5-day avg direction) ─────────────────────────
        $last5 = array_slice($closes, -5);
        $mom5  = count($last5) > 1 ? (end($last5) - $last5[0]) / max($last5[0], 1) * 100 : 0;

        // ── MACD (12,26,9) for histogram direction ────────────────────────────
        $macd = self::macd($closes);

        // ── Bollinger Bands (20,2) ────────────────────────────────────────────
        $bb = self::bollingerBands($closes, 20, 2);

        // ── Day-of-week historical bias (last 60 sessions grouped by weekday) ──
        $dowBias = self::dayOfWeekBias($priceRows, 60);

        // ── Volume trend: last 5 vs 20-day average ────────────────────────────
        $vol20Avg  = count($volumes) >= 20 ? array_sum(array_slice($volumes, -20)) / 20 : 0;
        $vol5Avg   = count($volumes) >= 5  ? array_sum(array_slice($volumes, -5))  / 5  : 0;
        $volSurge  = ($vol20Avg > 0) ? ($vol5Avg / $vol20Avg) : 1.0;

        // ── Build 7 trading-day forecasts ─────────────────────────────────────
        $forecasts  = [];
        $runPrice   = $lastClose;
        $startDate  = new \DateTime($lastDate);
        $dayCount   = 0;

        while ($dayCount < 7) {
            $startDate->modify('+1 day');
            $dow = (int)$startDate->format('N'); // 1=Mon … 7=Sun
            if ($dow >= 6) continue; // skip weekends

            $dayName    = $startDate->format('l');
            $dateStr    = $startDate->format('Y-m-d');
            $dayDisplay = $startDate->format('D, d M');

            // ── Composite score: positive = bullish ──────────────────────────
            $score   = 0;
            $reasons = [];

            // 1. Trend from regression (+/-20 pts)
            $trendScore = min(20, max(-20, $slopePct * 5));
            $score += $trendScore;
            if ($slopePct > 0.1) {
                $reasons[] = 'Upward price trend over last 20 sessions';
            } elseif ($slopePct < -0.1) {
                $reasons[] = 'Downward price trend over last 20 sessions';
            }

            // 2. RSI bias (+/-15 pts)
            if ($rsi !== null) {
                if ($rsi < 35) {
                    $score += 15;
                    $reasons[] = 'RSI ' . round($rsi, 1) . ' — oversold, likely bounce';
                } elseif ($rsi > 70) {
                    $score -= 15;
                    $reasons[] = 'RSI ' . round($rsi, 1) . ' — overbought, possible pullback';
                } elseif ($rsi >= 50 && $rsi <= 65) {
                    $score += 8;
                    $reasons[] = 'RSI ' . round($rsi, 1) . ' — bullish momentum zone';
                } elseif ($rsi >= 40 && $rsi < 50) {
                    $score -= 5;
                }
            }

            // 3. Momentum (5-day, +/-10 pts)
            $momScore = min(10, max(-10, $mom5 * 2));
            $score   += $momScore;
            if ($mom5 > 1) {
                $reasons[] = '5-day momentum +' . round($mom5, 1) . '%';
            } elseif ($mom5 < -1) {
                $reasons[] = '5-day momentum ' . round($mom5, 1) . '%';
            }

            // 4. MACD histogram direction (+/-10 pts)
            if ($macd !== null) {
                if ($macd['histogram'] > 0 && $macd['histogram'] > ($macd['prev_histogram'] ?? 0)) {
                    $score += 10;
                    $reasons[] = 'MACD histogram rising (bullish)';
                } elseif ($macd['histogram'] < 0 && $macd['histogram'] < ($macd['prev_histogram'] ?? 0)) {
                    $score -= 10;
                    $reasons[] = 'MACD histogram falling (bearish)';
                }
            }

            // 5. Bollinger Band position (+/-8 pts)
            if ($bb !== null) {
                $bbPos = ($bb['upper'] - $bb['lower']) > 0
                    ? ($runPrice - $bb['lower']) / ($bb['upper'] - $bb['lower'])
                    : 0.5;
                if ($bbPos < 0.2) {
                    $score += 8;
                    $reasons[] = 'Price near lower Bollinger Band (buy zone)';
                } elseif ($bbPos > 0.85) {
                    $score -= 8;
                    $reasons[] = 'Price near upper Bollinger Band (resistance)';
                }
            }

            // 6. Day-of-week historical bias (+/-8 pts)
            $dowScore = $dowBias[$dayName] ?? 0;
            $score   += $dowScore;
            if ($dowScore > 3) {
                $reasons[] = $dayName . 's historically tend to be up days';
            } elseif ($dowScore < -3) {
                $reasons[] = $dayName . 's historically tend to be down days';
            }

            // 7. Volume surge (+5 pts if buying pressure)
            if ($volSurge > 1.3 && $slopePct > 0) {
                $score += 5;
                $reasons[] = 'High volume supporting price rise';
            } elseif ($volSurge > 1.3 && $slopePct < 0) {
                $score -= 5;
                $reasons[] = 'High volume supporting price drop';
            }

            // 8. Trend decay over days (confidence reduces for further days)
            $decayFactor = 1 - ($dayCount * 0.06); // -6% per day
            $score *= $decayFactor;

            // ── Determine direction ──────────────────────────────────────────
            $direction  = $score > 3 ? 'up' : ($score < -3 ? 'down' : 'neutral');
            $confidence = min(90, max(30, 50 + abs($score)));
            // Confidence also decays further out
            $confidence = (int)round($confidence * $decayFactor);
            $confidence = min(90, max(25, $confidence));

            // ── Price estimate ───────────────────────────────────────────────
            // Predicted change: proportional to score capped at ±ATR
            $rawChangePct = min(($atr / max($runPrice, 1) * 100),
                                max(-($atr / max($runPrice, 1) * 100),
                                    $score * 0.08));
            $predictedPrice = round($runPrice * (1 + $rawChangePct / 100), 2);
            $predictedHigh  = round($predictedPrice + $atr * 0.6, 2);
            $predictedLow   = round($predictedPrice - $atr * 0.6, 2);
            $changePct      = round($rawChangePct, 2);

            // Run price forward for next iteration
            $runPrice = $predictedPrice;

            if (empty($reasons)) {
                $reasons[] = 'Neutral signals, sideways movement expected';
            }

            $forecasts[] = [
                'date'            => $dateStr,
                'day_display'     => $dayDisplay,
                'day_name'        => $dayName,
                'direction'       => $direction,
                'confidence'      => $confidence,
                'predicted_price' => $predictedPrice,
                'predicted_high'  => $predictedHigh,
                'predicted_low'   => $predictedLow,
                'change_pct'      => $changePct,
                'reasons'         => array_values(array_unique($reasons)),
            ];

            $dayCount++;
        }

        return $forecasts;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private static function atr(array $highs, array $lows, array $closes, int $period = 14): ?float
    {
        $n = count($closes);
        if ($n < $period + 1) return null;

        $trs = [];
        for ($i = 1; $i < $n; $i++) {
            $trs[] = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i]  - $closes[$i - 1])
            );
        }

        $atr = array_sum(array_slice($trs, 0, $period)) / $period;
        foreach (array_slice($trs, $period) as $tr) {
            $atr = ($atr * ($period - 1) + $tr) / $period;
        }
        return $atr;
    }

    private static function rsi(array $closes, int $period = 14): ?float
    {
        if (count($closes) < $period + 1) return null;

        $gains = $losses = [];
        for ($i = 1; $i < count($closes); $i++) {
            $d = $closes[$i] - $closes[$i - 1];
            $gains[]  = max(0, $d);
            $losses[] = max(0, -$d);
        }

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        foreach (array_slice($gains, $period) as $i => $g) {
            $avgGain = ($avgGain * ($period - 1) + $g) / $period;
            $avgLoss = ($avgLoss * ($period - 1) + array_slice($losses, $period)[$i]) / $period;
        }

        if ($avgLoss == 0) return 100.0;
        return round(100 - (100 / (1 + $avgGain / $avgLoss)), 2);
    }

    private static function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): ?array
    {
        if (count($closes) < $slow + $signal) return null;

        $emaFast = self::emaSeries($closes, $fast);
        $emaSlow = self::emaSeries($closes, $slow);

        $offset    = count($emaFast) - count($emaSlow);
        $macdLine  = [];
        for ($i = 0; $i < count($emaSlow); $i++) {
            $macdLine[] = $emaFast[$i + $offset] - $emaSlow[$i];
        }

        if (count($macdLine) < $signal) return null;
        $signalLine  = self::emaSeries($macdLine, $signal);
        $lastMacd    = end($macdLine);
        $lastSignal  = end($signalLine);
        $histogram   = $lastMacd - $lastSignal;
        $prevHist    = count($signalLine) >= 2
            ? $macdLine[count($macdLine) - 2] - $signalLine[count($signalLine) - 2]
            : $histogram;

        return [
            'macd'           => round($lastMacd, 4),
            'signal'         => round($lastSignal, 4),
            'histogram'      => round($histogram, 4),
            'prev_histogram' => round($prevHist, 4),
        ];
    }

    private static function bollingerBands(array $closes, int $period = 20, float $mult = 2.0): ?array
    {
        if (count($closes) < $period) return null;

        $slice = array_slice($closes, -$period);
        $mean  = array_sum($slice) / $period;
        $var   = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $slice)) / $period;
        $std   = sqrt($var);

        return [
            'upper'  => round($mean + $mult * $std, 2),
            'middle' => round($mean, 2),
            'lower'  => round($mean - $mult * $std, 2),
        ];
    }

    private static function emaSeries(array $values, int $period): array
    {
        if (count($values) < $period) return [];
        $k   = 2 / ($period + 1);
        $ema = array_sum(array_slice($values, 0, $period)) / $period;
        $out = [$ema];
        foreach (array_slice($values, $period) as $v) {
            $ema   = $v * $k + $ema * (1 - $k);
            $out[] = $ema;
        }
        return $out;
    }

    private static function linearRegressionSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        $xMean = ($n - 1) / 2;
        $yMean = array_sum($values) / $n;
        $num   = 0;
        $den   = 0;
        foreach ($values as $i => $y) {
            $num += ($i - $xMean) * ($y - $yMean);
            $den += ($i - $xMean) ** 2;
        }
        return $den > 0 ? $num / $den : 0;
    }

    /**
     * Calculate historical up/down bias per weekday from last N rows.
     * Returns ['Monday' => score, 'Tuesday' => score, ...]
     * Score: positive = historically bullish, negative = historically bearish.
     */
    private static function dayOfWeekBias(array $priceRows, int $lookback = 60): array
    {
        $rows    = array_slice($priceRows, -$lookback);
        $dayData = [];

        foreach ($rows as $row) {
            try {
                $dt   = new \DateTime($row['date']);
                $name = $dt->format('l');
                $chg  = (float)$row['close'] - (float)$row['open'];
                $dayData[$name][] = $chg;
            } catch (\Exception $e) {
                continue;
            }
        }

        $bias = [];
        foreach ($dayData as $day => $changes) {
            $avg       = array_sum($changes) / count($changes);
            $ref       = array_sum(array_column($rows, 'close')) / max(count($rows), 1);
            $pct       = $ref > 0 ? ($avg / $ref * 100) : 0;
            $bias[$day] = round($pct * 40, 2); // scale to ±8 pts range
        }

        return $bias;
    }
}
