<?php

namespace App\Support;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * IP → country/city/lat-lng lookup, via ip-api.com's free endpoint (no API
 * key required). Results are cached 30 days per IP — an IP's geolocation
 * doesn't change request-to-request, so there is no reason to re-fetch it
 * on every single page view/activity log entry.
 *
 * Never throws: local/private/reserved IPs (localhost, LAN, etc.) are
 * skipped outright since they can't be geolocated, and any network/API
 * failure just returns nulls so activity logging is never blocked by this.
 */
class GeoLookup
{
    public static function lookup(?string $ip): array
    {
        $empty = ['country' => null, 'city' => null, 'latitude' => null, 'longitude' => null];

        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $empty;
        }

        return Cache::remember("geo_ip_{$ip}", now()->addDays(30), function () use ($ip, $empty) {
            try {
                $client = new Client(['timeout' => 3, 'connect_timeout' => 2]);
                $response = $client->get("http://ip-api.com/json/{$ip}", [
                    'query' => ['fields' => 'status,country,city,lat,lon'],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (($data['status'] ?? null) !== 'success') {
                    return $empty;
                }

                return [
                    'country'   => $data['country'] ?? null,
                    'city'      => $data['city'] ?? null,
                    'latitude'  => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::debug("[geo-lookup] failed for {$ip}: {$e->getMessage()}");
                return $empty;
            }
        });
    }
}
