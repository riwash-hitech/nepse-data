<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * ShareHubScraperService — supplements NepseScraperService (Chukul.com) with
 * fundamentals data Chukul only exposes behind a paywalled endpoint we don't
 * have access to (/api/fundamental-scan/). ShareHubNepal.com publishes the
 * same kind of data (EPS, P/E, Book Value, shareholding structure) openly,
 * embedded server-side in its /company/{symbol} page — there is no separate
 * public JSON endpoint for it, so fetchFundamentals() scrapes the rendered
 * HTML directly. fetchPeers() uses a genuine public JSON endpoint instead.
 *
 * Same resilience contract as NepseScraperService: every method catches
 * \Throwable, logs via Log::warning, and returns [] on any failure — never
 * throws, so a ShareHub outage never breaks the stock page.
 */
class ShareHubScraperService
{
    private const BASE = 'https://sharehubnepal.com';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => self::BASE,
            'timeout'         => 15,
            'connect_timeout' => 8,
            'verify'          => false,
            'headers'         => [
                'Accept'          => 'text/html,application/json',
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer'         => 'https://sharehubnepal.com/',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ]);
    }

    /**
     * Fundamentals (EPS, P/E, Book Value, shareholding, etc.) scraped from
     * the company profile page. Every field is nullable — any one missing
     * doesn't invalidate the rest.
     */
    public function fetchFundamentals(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        try {
            $response = $this->client->get("/company/{$symbol}");
            $html = $response->getBody()->getContents();
        } catch (\Throwable $e) {
            Log::warning("ShareHubScraper: fundamentals fetch failed for {$symbol}", ['error' => $e->getMessage()]);
            return [];
        }

        $num = fn (string $key) => $this->matchFloat($html, $key);
        $int = fn (string $key) => $this->matchInt($html, $key);
        $str = fn (string $key) => $this->matchString($html, $key);

        $result = [
            'eps'                 => $num('eps'),
            'pe_ratio'            => $num('peRatio'),
            'book_value'          => $num('bookValue'),
            'pbv'                 => $num('pricePerBookValue'),
            'market_cap'          => $num('marketCap'),
            'float_cap'           => $num('floatCap'),
            'paid_up_capital'     => $num('paidUpCapital'),
            'face_value'          => $num('faceValue'),
            'listed_shares'       => $int('listedShares'),
            'public_shares'       => $int('publicShares'),
            'promoter_shares'     => $int('promoterShares'),
            'fifty_two_week_high' => $num('fiftyTwoWeekHigh'),
            'fifty_two_week_low'  => $num('fiftyTwoWeekLow'),
            'fiscal_year'         => $str('year'),
            'quarter'             => $str('quarter'),
        ];

        // If nothing at all was found, treat it as a failed scrape (page
        // structure changed, symbol not on ShareHub, etc.) rather than
        // returning a bag of nulls.
        if (empty(array_filter($result, fn ($v) => $v !== null))) {
            return [];
        }

        if ($result['listed_shares'] && $result['promoter_shares'] !== null) {
            $result['promoter_pct'] = round($result['promoter_shares'] / $result['listed_shares'] * 100, 1);
        }
        if ($result['listed_shares'] && $result['public_shares'] !== null) {
            $result['public_pct'] = round($result['public_shares'] / $result['listed_shares'] * 100, 1);
        }

        return $result;
    }

    /**
     * Sector-peer comparison — a genuine public JSON endpoint (unlike
     * fetchFundamentals(), which has to scrape HTML).
     */
    public function fetchPeers(string $symbol): array
    {
        try {
            $response = $this->client->get('/data/api/v1/security/peers/' . strtoupper($symbol));
            $decoded = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($decoded['data'])) {
                return [];
            }

            return $decoded['data'];
        } catch (\Throwable $e) {
            Log::warning("ShareHubScraper: peers fetch failed for {$symbol}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ShareHub's Next.js page embeds its data as a JSON string nested inside
     * the server-rendered React Server Component payload, so the quotes
     * around each key/value come through backslash-escaped in the raw HTML
     * (literally `\"eps\":110.66`, not `"eps":110.66`) — every pattern here
     * matches an optional literal backslash before each quote to handle both.
     */
    private function matchFloat(string $html, string $key): ?float
    {
        if (preg_match('/\\\\?"' . preg_quote($key, '/') . '\\\\?":(-?[\d.]+)/', $html, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    private function matchInt(string $html, string $key): ?int
    {
        $value = $this->matchFloat($html, $key);
        return $value !== null ? (int) $value : null;
    }

    private function matchString(string $html, string $key): ?string
    {
        if (preg_match('/\\\\?"' . preg_quote($key, '/') . '\\\\?":\\\\?"([^"\\\\]*)/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
