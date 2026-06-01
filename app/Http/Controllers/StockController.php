<?php

namespace App\Http\Controllers;

use App\Services\NepseScraperService;
use App\Services\PredictionService;
use App\Services\SignalEngine;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class StockController extends Controller
{
    public function __construct(
        private readonly NepseScraperService $scraper,
        private readonly SignalEngine $signalEngine
    ) {}

    // ── Stock listing (Chukul stock list, no DB) ──────────────────────────────

    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $sector = $request->get('sector', '');

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
            ->when($sector, fn($c) => $c->filter(fn($s) => ($s['sector'] ?? '') === $sector))
            ->sortBy('symbol')
            ->values()
            ->map(fn($s) => (object)[
                'symbol'       => $s['symbol'],
                'name'         => $s['name'],
                'sector'       => $s['sector'] ? (object)['name' => $s['sector']] : null,
                'latestPrice'  => null,
                'latestSignal' => null,
            ]);

        $page   = max(1, (int)$request->get('page', 1));
        $perPage = 50;
        $stocks = new LengthAwarePaginator(
            $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $sectors = collect($all)
            ->pluck('sector')->filter()->unique()->sort()->values();

        return view('stocks.index', compact('stocks', 'sectors', 'search', 'sector'));
    }

    // ── Stock analytics page (Chukul live data + in-memory indicators) ────────

    public function show(string $symbol)
    {
        $symbol = strtoupper(trim($symbol));

        // ── Fetch all Chukul data in parallel via cache ──
        $priceRows = Cache::remember("chukul_adj_{$symbol}", 300, fn() =>
            $this->scraper->fetchHistoricalPrices($symbol)
        );

        if (empty($priceRows)) {
            abort(404, "No market data found for {$symbol}. Market may be closed or symbol invalid.");
        }

        // Current-day market summary (live: open, close, change%, weekly/monthly stats)
        $marketSummary = Cache::remember("chukul_summary_{$symbol}", 300, fn() =>
            $this->scraper->fetchMarketSummary($symbol)
        );

        // 52-week high/low & volume stats
        $highLowStats = Cache::remember("chukul_hl_{$symbol}", 1800, fn() =>
            $this->scraper->fetchHighLowStats($symbol)
        );

        // Live RSI from Chukul
        $liveIndicators = Cache::remember("chukul_ind_{$symbol}", 1800, fn() =>
            $this->scraper->fetchLiveIndicators($symbol)
        );

        // Support & resistance levels from Chukul
        $supportLevels    = Cache::remember("chukul_sup_{$symbol}", 1800, fn() =>
            $this->scraper->fetchSupportLevels($symbol)
        );
        $resistanceLevels = Cache::remember("chukul_res_{$symbol}", 1800, fn() =>
            $this->scraper->fetchResistanceLevels($symbol)
        );

        // Alpha/Beta & VaR
        $alphaBeta  = Cache::remember("chukul_ab_{$symbol}",  3600, fn() =>
            $this->scraper->fetchAlphaBeta($symbol)
        );
        $varMonthly = Cache::remember("chukul_var_{$symbol}", 3600, fn() =>
            $this->scraper->fetchVarMonthly($symbol)
        );

        // ── Run full in-memory analytics ──
        $analytics = $this->signalEngine->analyzeFromData($priceRows);

        // Stock info from cached Chukul stock list
        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());
        $info      = collect($stockList)->firstWhere('symbol', $symbol) ?? [];

        // Build view-compatible objects
        $stock     = (object)[
            'symbol' => $symbol,
            'name'   => $info['name'] ?? $symbol,
            'sector' => isset($info['sector']) ? (object)['name' => $info['sector']] : null,
        ];
        $indicator = !empty($analytics['indicator']) ? (object)$analytics['indicator'] : null;

        // Override RSI with Chukul's live value if available
        if ($indicator && !empty($liveIndicators['rsi14'])) {
            $indicator->rsi_14 = (float)$liveIndicators['rsi14'];
        }

        // Extract trend — returned as top-level key from analyzeFromData
        $trendArr  = $analytics['trend'] ?? null;
        if (!empty($analytics['signal'])) {
            $signal = (object)$analytics['signal'];
        } else {
            $signal = null;
        }

        // Prices collection — most-recent first, dates as Carbon
        $prices = collect($priceRows)
            ->reverse()->values()
            ->map(fn($p) => (object)[
                'date'           => \Carbon\Carbon::parse($p['date']),
                'open'           => (float)$p['open'],
                'high'           => (float)$p['high'],
                'low'            => (float)$p['low'],
                'close'          => (float)$p['close'],
                'volume'         => (int)$p['volume'],
                'turnover'       => (float)($p['turnover'] ?? 0),
                'change'         => (float)($p['change'] ?? 0),
                'change_percent' => (float)($p['change_percent'] ?? 0),
                'vwap'           => (float)($p['vwap'] ?? 0),
            ]);

        // Chart data — ascending order, plain arrays
        $chartData = array_values(array_map(fn($p) => [
            'date'   => $p['date'],
            'open'   => (float)$p['open'],
            'high'   => (float)$p['high'],
            'low'    => (float)$p['low'],
            'close'  => (float)$p['close'],
            'volume' => (int)$p['volume'],
        ], $priceRows));

        $floorsheetSummary = ['buy' => [], 'sell' => [], 'total_buy' => 0, 'total_sell' => 0];

        // ── Volume analytics from last 20 candles (buy/sell pressure) ──
        $last20   = array_slice($priceRows, -20);
        $totalVol = array_sum(array_column($last20, 'volume')) ?: 1;
        $bullRows = array_values(array_filter($last20, fn($p) => (float)$p['close'] > (float)$p['open']));
        $bearRows = array_values(array_filter($last20, fn($p) => (float)$p['close'] < (float)$p['open']));
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
            'avg_volume'   => (int)round(array_sum(array_column($last20, 'volume')) / max(count($last20), 1)),
            'last_volume'  => (int)end($priceRows)['volume'],
        ];

        // ── Trend — keep as plain array for Blade (nested structure with consensus) ──
        $trend = $trendArr ?? null;

        // ── Broker list (cached 24 hr) ──
        $brokers = Cache::remember('chukul_broker_list', 86400, fn() => $this->scraper->fetchBrokerList());

        // Build broker name lookup: broker_no => broker_name
        $brokerMap = [];
        foreach ($brokers as $b) {
            $no = (string)($b['broker_no'] ?? '');
            if ($no !== '') $brokerMap[$no] = $b['broker_name'] ?? "Broker #{$no}";
        }

        // ── Floorsheet broker activity (use latest trading date from market summary or history) ──
        $floorDate = $marketSummary['date'] ?? (end($priceRows)['date'] ?? date('Y-m-d'));
        $rawFloor  = Cache::remember("chukul_floor_{$symbol}_{$floorDate}", 300, fn() =>
            $this->scraper->fetchFloorsheet($symbol, $floorDate)
        );

        // Enrich top 15 buyers + sellers with names and percentages
        $brokerActivity = null;
        if (!empty($rawFloor['buys'])) {
            $totalBQ = (float)($rawFloor['total_buy_qty']    ?: 1);
            $totalSQ = (float)($rawFloor['total_sell_qty']   ?: 1);
            $totalBA = (float)($rawFloor['total_buy_amount'] ?: 1);
            $totalSA = (float)($rawFloor['total_sell_amount']?: 1);

            $enrichedBuys = [];
            foreach (array_slice($rawFloor['buys'], 0, 15, true) as $bno => $data) {
                $enrichedBuys[] = [
                    'broker_no'   => $bno,
                    'broker_name' => $brokerMap[(string)$bno] ?? "Broker #{$bno}",
                    'qty'         => (float)$data['qty'],
                    'amount'      => (float)$data['amount'],
                    'qty_pct'     => round($data['qty'] / $totalBQ * 100, 2),
                    'amt_pct'     => round($data['amount'] / $totalBA * 100, 2),
                ];
            }

            $enrichedSells = [];
            foreach (array_slice($rawFloor['sells'], 0, 15, true) as $bno => $data) {
                $enrichedSells[] = [
                    'broker_no'   => $bno,
                    'broker_name' => $brokerMap[(string)$bno] ?? "Broker #{$bno}",
                    'qty'         => (float)$data['qty'],
                    'amount'      => (float)$data['amount'],
                    'qty_pct'     => round($data['qty'] / $totalSQ * 100, 2),
                    'amt_pct'     => round($data['amount'] / $totalSA * 100, 2),
                ];
            }

            $brokerActivity = [
                'date'              => $rawFloor['date'],
                'rows'              => (int)$rawFloor['rows'],
                'total_buy_qty'     => $rawFloor['total_buy_qty'],
                'total_sell_qty'    => $rawFloor['total_sell_qty'],
                'total_buy_amount'  => $rawFloor['total_buy_amount'],
                'total_sell_amount' => $rawFloor['total_sell_amount'],
                'buys'              => $enrichedBuys,
                'sells'             => $enrichedSells,
            ];
        }

        // ── 7-Day Price Prediction ────────────────────────────────────────────
        $prediction7d = Cache::remember("chukul_pred_{$symbol}", 1800, fn() =>
            PredictionService::forecast($priceRows)
        );

        return view('stocks.show', compact(
            'stock', 'prices', 'indicator', 'signal', 'chartData',
            'floorsheetSummary', 'volumeAnalytics', 'trend', 'brokers',
            'marketSummary', 'highLowStats', 'supportLevels', 'resistanceLevels',
            'alphaBeta', 'varMonthly', 'brokerActivity', 'prediction7d'
        ));
    }

    // ── Autocomplete search (Chukul stock list, no DB) ────────────────────────

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
            ->map(fn($s) => [
                'symbol'         => $s['symbol'],
                'name'           => $s['name'],
                'close'          => null,
                'change_percent' => null,
                'url'            => route('stocks.show', $s['symbol']),
            ])
            ->values();

        return response()->json($results);
    }

    // ── Chart data JSON (Chukul live, filtered by period) ─────────────────────

    public function chartData(Request $request, string $symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $period = $request->get('period', '3M');
        $days   = match($period) {
            '1D'  => 1,
            '1W'  => 7,
            '1M'  => 30,
            '3M'  => 90,
            '1Y'  => 365,
            'ALL' => 3650,
            default => 90,
        };

        $priceRows = Cache::remember("chukul_adj_{$symbol}", 300, fn() =>
            $this->scraper->fetchHistoricalPrices($symbol)
        );

        $cutoff  = now()->subDays($days)->toDateString();
        $filtered = collect($priceRows)
            ->filter(fn($p) => $p['date'] >= $cutoff)
            ->map(fn($p) => [
                'date'   => $p['date'],
                'open'   => (float)$p['open'],
                'high'   => (float)$p['high'],
                'low'    => (float)$p['low'],
                'close'  => (float)$p['close'],
                'volume' => (int)$p['volume'],
            ])
            ->values();

        return response()->json($filtered);
    }
}

