<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Historical return % across standard lookback windows (1M/3M/6M/YTD/1Y/3Y/5Y/
 * all-time), computed entirely from already-cached daily price history — no
 * extra API call needed. Chukul's fundamental data (EPS, P/E, ROE, etc.) only
 * exists behind an authenticated/paid endpoint (/api/fundamental-scan/), so
 * this sticks to what's derivable from OHLCV data alone.
 */
class ReturnsCalculator
{
    /**
     * @param array $priceRows Ascending oldest→newest, each ['date' => 'Y-m-d', 'close' => float, ...]
     * @return array<string, array{pct: float, from: float, from_date: string}|null>
     */
    public static function compute(array $priceRows): array
    {
        if (empty($priceRows)) {
            return [];
        }

        $latest      = end($priceRows);
        $latestClose = (float) $latest['close'];
        $latestDate  = Carbon::parse($latest['date']);

        $windows = [
            '1M'  => $latestDate->copy()->subMonth(),
            '3M'  => $latestDate->copy()->subMonths(3),
            '6M'  => $latestDate->copy()->subMonths(6),
            'YTD' => Carbon::create($latestDate->year, 1, 1),
            '1Y'  => $latestDate->copy()->subYear(),
            '3Y'  => $latestDate->copy()->subYears(3),
            '5Y'  => $latestDate->copy()->subYears(5),
        ];

        $results = [];
        foreach ($windows as $label => $targetDate) {
            $results[$label] = self::returnSince($priceRows, $targetDate, $latestClose);
        }

        // All-time — from the very first cached candle.
        $first = reset($priceRows);
        $firstClose = (float) $first['close'];
        $results['all_time'] = $firstClose > 0 ? [
            'pct'       => round((($latestClose - $firstClose) / $firstClose) * 100, 2),
            'from'      => $firstClose,
            'from_date' => $first['date'],
        ] : null;

        return $results;
    }

    private static function returnSince(array $priceRows, Carbon $targetDate, float $latestClose): ?array
    {
        $earliest = Carbon::parse(reset($priceRows)['date']);
        if ($targetDate->lt($earliest)) {
            return null; // not enough history for this window
        }

        // First candle on/after the target date (ascending order).
        foreach ($priceRows as $row) {
            if (Carbon::parse($row['date'])->gte($targetDate)) {
                $baseClose = (float) $row['close'];
                if ($baseClose <= 0) {
                    return null;
                }

                return [
                    'pct'       => round((($latestClose - $baseClose) / $baseClose) * 100, 2),
                    'from'      => $baseClose,
                    'from_date' => $row['date'],
                ];
            }
        }

        return null;
    }
}
