<?php

namespace App\Services;

/**
 * Finds past oversold episodes (RSI dropping below a threshold) in a stock's
 * own closing-price history and measures what actually happened afterward —
 * how far price fell before bottoming, and how far/fast it recovered. Used
 * to ground the Oversold screener in "here's what happened last time this
 * stock was this oversold" instead of a bare RSI number with no context.
 *
 * Pure math over an already-fetched price series — no DB/HTTP calls here.
 */
class BounceAnalysisService
{
    /**
     * @param array $priceRows ordered oldest→newest, each ['date' => 'Y-m-d'|Carbon-ish, 'close' => float]
     * @return array{
     *   episodes_count:int, is_ongoing:bool,
     *   last_low_price:float, last_low_date:string,
     *   last_bounce_price:float, last_bounce_date:string, last_bounce_pct:float, last_bounce_days:int,
     *   avg_bounce_pct:float, max_bounce_pct:float,
     *   projected_target_low:float, projected_target_high:float
     * }|null
     */
    public static function analyze(array $priceRows, float $currentPrice, int $period = 14, float $oversoldThreshold = 30.0, int $forwardWindow = 20): ?array
    {
        $closes = array_values(array_map(fn($r) => (float) $r['close'], $priceRows));
        $dates  = array_values(array_map(fn($r) => (string) $r['date'], $priceRows));
        $n = count($closes);

        if ($n < $period + 5) {
            return null;
        }

        $rsiSeries = IndicatorService::rsiSeries($closes, $period);

        // ── Group consecutive oversold days into episodes ──────────────────
        $episodes = [];
        $start = null;
        for ($i = 0; $i < $n; $i++) {
            $oversold = $rsiSeries[$i] !== null && $rsiSeries[$i] < $oversoldThreshold;
            if ($oversold && $start === null) {
                $start = $i;
            } elseif (!$oversold && $start !== null) {
                $episodes[] = [$start, $i - 1];
                $start = null;
            }
        }
        if ($start !== null) {
            $episodes[] = [$start, $n - 1];
        }

        if (empty($episodes)) {
            return null;
        }

        // ── Resolve each episode: bottom price, then the peak reached within
        //    $forwardWindow sessions after the bottom ───────────────────────
        $resolved = [];
        $lastEpisodeIsOngoing = false;

        foreach ($episodes as $idx => [$epStart, $epEnd]) {
            $isLast = $idx === count($episodes) - 1;

            // Lowest close within the episode itself is the "bottom".
            $lowIdx = $epStart;
            for ($i = $epStart; $i <= $epEnd; $i++) {
                if ($closes[$i] < $closes[$lowIdx]) {
                    $lowIdx = $i;
                }
            }

            $forwardEnd = min($n - 1, $epEnd + $forwardWindow);

            // If this is the most recent episode and it either still reaches
            // today (ongoing) or doesn't have enough forward days yet to call
            // a bounce resolved, mark it ongoing and skip it from the stats —
            // we don't yet know how this one turns out.
            if ($isLast && $forwardEnd - $epEnd < (int) ($forwardWindow / 2)) {
                $lastEpisodeIsOngoing = true;
                continue;
            }

            $peakIdx = $epEnd;
            for ($i = $epEnd; $i <= $forwardEnd; $i++) {
                if ($closes[$i] > $closes[$peakIdx]) {
                    $peakIdx = $i;
                }
            }

            if ($closes[$lowIdx] <= 0) {
                continue;
            }

            $bouncePct = ($closes[$peakIdx] - $closes[$lowIdx]) / $closes[$lowIdx] * 100;

            $resolved[] = [
                'low_price'    => $closes[$lowIdx],
                'low_date'     => $dates[$lowIdx],
                'bounce_price' => $closes[$peakIdx],
                'bounce_date'  => $dates[$peakIdx],
                'bounce_pct'   => $bouncePct,
                'bounce_days'  => $peakIdx - $lowIdx,
            ];
        }

        if (empty($resolved)) {
            return null;
        }

        $last = end($resolved);
        $pcts = array_column($resolved, 'bounce_pct');
        $avgPct = array_sum($pcts) / count($pcts);
        $maxPct = max($pcts);

        return [
            'episodes_count'       => count($resolved),
            'is_ongoing'           => $lastEpisodeIsOngoing,
            'last_low_price'       => round($last['low_price'], 2),
            'last_low_date'        => $last['low_date'],
            'last_bounce_price'    => round($last['bounce_price'], 2),
            'last_bounce_date'     => $last['bounce_date'],
            'last_bounce_pct'      => round($last['bounce_pct'], 1),
            'last_bounce_days'     => $last['bounce_days'],
            'avg_bounce_pct'       => round($avgPct, 1),
            'max_bounce_pct'       => round($maxPct, 1),
            'projected_target_low'  => round($currentPrice * (1 + max(0, $avgPct) / 100), 2),
            'projected_target_high' => round($currentPrice * (1 + max(0, $maxPct) / 100), 2),
        ];
    }
}
