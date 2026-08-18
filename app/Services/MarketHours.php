<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Heuristic only (Sun–Thu, 11:00–15:00 Nepal time) — NEPSE doesn't expose a
 * live session-status API, so this is a documented approximation, mirrored
 * from the mobile app's market_hours.dart.
 */
class MarketHours
{
    public static function status(): array
    {
        $now = Carbon::now('Asia/Kathmandu');
        $isTradingDay = $now->dayOfWeekIso !== Carbon::FRIDAY && $now->dayOfWeekIso !== Carbon::SATURDAY;
        $open = $now->copy()->setTime(11, 0);
        $close = $now->copy()->setTime(15, 0);
        $isOpen = $isTradingDay && $now->between($open, $close);

        return [
            'open'     => $isOpen,
            'closesIn' => $isOpen ? $now->diff($close)->format('%hh %im') : null,
        ];
    }
}
