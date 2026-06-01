<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * IPO Data Service
 *
 * Company list: fetched from Chukul (reliable, no WAF blocking).
 * Result check: proxied to CDSC via browser-side JavaScript (WAF blocks server-side).
 */
class IpoService
{
    private const CHUKUL_IPO = 'https://chukul.com/api/ipo/';
    private const TIMEOUT    = 12;

    private static function chukulHeaders(): array
    {
        return [
            'Accept'     => 'application/json',
            'Referer'    => 'https://chukul.com/',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ];
    }

    /**
     * Fetch the full IPO list from Chukul.
     * Returns [{symbol, company, units, opening_date, closing_date, listing_date,
     *           issue_manager, status}]
     * Cached for 1 hour.
     */
    public static function getIpoList(): array
    {
        return Cache::remember('chukul_ipo_list', 3600, function () {
            try {
                $res = Http::withHeaders(self::chukulHeaders())
                    ->timeout(self::TIMEOUT)
                    ->withOptions(['verify' => false])
                    ->get(self::CHUKUL_IPO);

                if ($res->successful() && is_array($res->json())) {
                    return $res->json();
                }
                return [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /**
     * Validate a BOID string: must be exactly 16 digits.
     */
    public static function validateBoid(string $boid): bool
    {
        return (bool) preg_match('/^\d{16}$/', $boid);
    }
}
