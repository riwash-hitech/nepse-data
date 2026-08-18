<?php

namespace App\Services;

/**
 * Compact number formatting shared by the dashboard, markets and landing
 * pages (e.g. "Rs. 4.2B" for turnover, "12.5M" for shares traded).
 */
class MarketFormatter
{
    public static function compactRupees(float $amount): string
    {
        return 'Rs. ' . self::compactNumber($amount);
    }

    public static function compactNumber(float $amount): string
    {
        return match (true) {
            $amount >= 1_000_000_000 => round($amount / 1_000_000_000, 1) . 'B',
            $amount >= 1_000_000     => round($amount / 1_000_000, 1) . 'M',
            $amount >= 1_000         => round($amount / 1_000, 1) . 'K',
            default                  => number_format($amount, 0),
        };
    }
}
