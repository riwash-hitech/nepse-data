<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NepseScraperService;
use App\Services\Outlook30Service;
use App\Services\PredictionService;
use App\Services\SignalEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketController extends Controller
{
    public function __construct(
        private readonly NepseScraperService $scraper,
        private readonly SignalEngine $signalEngine
    ) {}

    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $sector = $request->get('sector', '');
        $page   = max(1, (int) $request->get('page', 1));
        $perPage = 50;

        $all = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());

        $filtered = collect($all)
            ->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false))
            ->when($search, function ($c) use ($search) {
                $up = strtoupper($search);
                return $c->filter(fn($s) =>
                    str_contains(strtoupper($s['symbol'] ?? ''), $up) ||
                    str_contains(strtoupper($s['name'] ?? ''), $up)
                );
            })
            ->when($sector, fn($c) => $c->filter(fn($s) => (string) ($s['sector'] ?? '') === (string) $sector))
            ->sortBy('symbol')
            ->values();

        $sectorList = Cache::remember('chukul_sector_list', 3600, fn() => $this->scraper->fetchSectorList());
        $sectorIds  = collect($all)->pluck('sector')->filter()->unique();
        $sectors    = collect($sectorList)
            ->filter(fn($s) => $sectorIds->contains($s['id']))
            ->map(fn($s) => ['id' => $s['id'], 'name' => $s['name']])
            ->sortBy('name')
            ->values();

        return response()->json([
            'data'       => $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
            'total'      => $filtered->count(),
            'page'       => $page,
            'per_page'   => $perPage,
            'sectors'    => $sectors,
        ]);
    }

    public function search(Request $request)
    {
        $term = trim($request->get('q', ''));
        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $all = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());
        $up  = strtoupper($term);

        $results = collect($all)
            ->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false))
            ->filter(fn($s) =>
                str_contains(strtoupper($s['symbol'] ?? ''), $up) ||
                str_contains(strtoupper($s['name'] ?? ''), $up)
            )
            ->take(10)
            ->map(fn($s) => ['symbol' => $s['symbol'], 'name' => $s['name'], 'sector' => $s['sector'] ?? null])
            ->values();

        return response()->json($results);
    }

    public function show(string $symbol)
    {
        $symbol = strtoupper(trim($symbol));

        $priceRows = Cache::remember("chukul_adj_{$symbol}", 300, fn() =>
            $this->scraper->fetchHistoricalPrices($symbol)
        );

        if (empty($priceRows)) {
            return response()->json(['message' => "No market data found for {$symbol}."], 404);
        }

        $marketSummary  = Cache::remember("chukul_summary_{$symbol}", 300, fn() => $this->scraper->fetchMarketSummary($symbol));
        $highLowStats   = Cache::remember("chukul_hl_{$symbol}", 1800, fn() => $this->scraper->fetchHighLowStats($symbol));
        $liveIndicators = Cache::remember("chukul_ind_{$symbol}", 1800, fn() => $this->scraper->fetchLiveIndicators($symbol));
        $supportLevels    = Cache::remember("chukul_sup_{$symbol}", 1800, fn() => $this->scraper->fetchSupportLevels($symbol));
        $resistanceLevels = Cache::remember("chukul_res_{$symbol}", 1800, fn() => $this->scraper->fetchResistanceLevels($symbol));
        $alphaBeta  = Cache::remember("chukul_ab_{$symbol}",  3600, fn() => $this->scraper->fetchAlphaBeta($symbol));
        $varMonthly = Cache::remember("chukul_var_{$symbol}", 3600, fn() => $this->scraper->fetchVarMonthly($symbol));

        $analytics = $this->signalEngine->analyzeFromData($priceRows);

        // ── Volume analytics from last 20 candles (buy/sell pressure) ──
        $last20   = array_slice($priceRows, -20);
        $totalVol = array_sum(array_column($last20, 'volume')) ?: 1;
        $bullRows = array_values(array_filter($last20, fn($p) => (float) $p['close'] > (float) $p['open']));
        $bearRows = array_values(array_filter($last20, fn($p) => (float) $p['close'] < (float) $p['open']));
        $buyVol   = array_sum(array_column($bullRows, 'volume'));
        $sellVol  = array_sum(array_column($bearRows, 'volume'));
        $volumeAnalytics = [
            'buy_candles'  => count($bullRows),
            'sell_candles' => count($bearRows),
            'neutral'      => count($last20) - count($bullRows) - count($bearRows),
            'buy_vol'      => $buyVol,
            'sell_vol'     => $sellVol,
            'buy_pct'      => round($buyVol / $totalVol * 100, 1),
            'sell_pct'     => round($sellVol / $totalVol * 100, 1),
            'avg_volume'   => (int) round(array_sum(array_column($last20, 'volume')) / max(count($last20), 1)),
            'last_volume'  => (int) end($priceRows)['volume'],
        ];

        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());
        $info      = collect($stockList)->firstWhere('symbol', $symbol) ?? [];

        $indicator = $analytics['indicator'] ?? null;
        if ($indicator && !empty($liveIndicators['rsi14'])) {
            $indicator['rsi_14'] = (float) $liveIndicators['rsi14'];
        }

        $prediction7d  = Cache::remember("chukul_pred_{$symbol}", 1800, fn() => PredictionService::forecast($priceRows));
        $prediction30d = Cache::remember("chukul_outlook30_{$symbol}", 1800, fn() => Outlook30Service::project($priceRows));

        return response()->json([
            'symbol'            => $symbol,
            'name'              => $info['name'] ?? $symbol,
            'sector'            => $info['sector'] ?? null,
            'market_summary'    => $marketSummary,
            'high_low'          => $highLowStats,
            'indicator'         => $indicator,
            'signal'            => $analytics['signal'] ?? null,
            'trend'             => $analytics['trend'] ?? null,
            'prediction_7d'     => $prediction7d,
            'prediction_30d'    => $prediction30d,
            'support_levels'    => $supportLevels,
            'resistance_levels' => $resistanceLevels,
            'alpha_beta'        => $alphaBeta,
            'var_monthly'       => $varMonthly,
            'volume_analytics'  => $volumeAnalytics,
        ]);
    }

    // ── Dashboard: NEPSE / Sensitive / Float / Sensitive Float indices ────────

    private const INDEX_SYMBOLS = [
        'NEPSE'      => 'NEPSE',
        'SENSIND'    => 'Sensitive',
        'FLOATIND'   => 'Float',
        'SENSFLTIND' => 'Sen. Float',
    ];

    public function indices()
    {
        $quotes = collect(self::INDEX_SYMBOLS)->map(function ($label, $symbol) {
            $q = Cache::remember("chukul_index_{$symbol}", 300, fn() => $this->scraper->fetchIndexQuote($symbol));
            return $q ? array_merge($q, ['label' => $label]) : null;
        })->filter()->values();

        return response()->json($quotes);
    }

    // ── Dashboard: live gainers / losers / turnover ───────────────────────────

    public function movers(Request $request)
    {
        $type  = $request->get('type', 'gainers');
        $limit = min(50, max(1, (int) $request->get('limit', 10)));

        $all = Cache::remember('chukul_bulk_summary', 180, fn() => $this->scraper->fetchBulkMarketSummary());

        $rows = collect($all)->map(fn($s) => [
            'symbol'         => $s['symbol'],
            'ltp'            => (float) ($s['close'] ?? 0),
            'change_percent' => (float) ($s['percentage_change'] ?? 0),
            'turnover'       => (float) ($s['amount'] ?? 0),
            'volume'         => (float) ($s['volume'] ?? 0),
        ]);

        $sorted = match ($type) {
            'losers'   => $rows->sortBy('change_percent'),
            'turnover' => $rows->sortByDesc('turnover'),
            default    => $rows->sortByDesc('change_percent'),
        };

        return response()->json($sorted->values()->take($limit));
    }

    public function chartData(Request $request, string $symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $period = $request->get('period', '3M');
        $days   = match ($period) {
            '1D'  => 1,
            '1W'  => 7,
            '1M'  => 30,
            '3M'  => 90,
            '1Y'  => 365,
            'ALL' => 3650,
            default => 90,
        };

        $priceRows = Cache::remember("chukul_adj_{$symbol}", 300, fn() => $this->scraper->fetchHistoricalPrices($symbol));

        $cutoff   = now()->subDays($days)->toDateString();
        $filtered = collect($priceRows)
            ->filter(fn($p) => $p['date'] >= $cutoff)
            ->map(fn($p) => [
                'date'   => $p['date'],
                'open'   => (float) $p['open'],
                'high'   => (float) $p['high'],
                'low'    => (float) $p['low'],
                'close'  => (float) $p['close'],
                'volume' => (int) $p['volume'],
            ])
            ->values();

        return response()->json($filtered);
    }
}
