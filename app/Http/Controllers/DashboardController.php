<?php

namespace App\Http\Controllers;

use App\Jobs\FetchLiveMarketDataJob;
use App\Services\NepseScraperService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        // Stock list from Chukul — cached 1 hr, no DB
        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());
        $sectors   = Cache::remember('chukul_sector_list', 3600, fn() => $this->scraper->fetchSectorList());

        $active     = collect($stockList)->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false));
        $totalStocks = $active->count();

        // Sector breakdown
        $sectorStats = $active
            ->groupBy('sector')
            ->map(fn($g, $name) => ['name' => $name ?: 'Other', 'count' => $g->count()])
            ->sortByDesc('count')
            ->values();

        return view('dashboard.index', compact(
            'stockList', 'sectors', 'totalStocks', 'sectorStats'
        ));
    }

    // ── Public landing page: live indices + top movers ────────────────────────
    private const LANDING_INDEX_SYMBOLS = [
        'NEPSE'      => 'NEPSE',
        'SENSIND'    => 'Sensitive',
        'FLOATIND'   => 'Float',
        'SENSFLTIND' => 'Sen. Float',
    ];

    public function landing()
    {
        $indices = collect(self::LANDING_INDEX_SYMBOLS)->map(function ($label, $symbol) {
            $q = Cache::remember("chukul_index_{$symbol}", 300, fn() => $this->scraper->fetchIndexQuote($symbol));
            return $q ? array_merge($q, ['label' => $label]) : null;
        })->filter()->values();

        $bulk = Cache::remember('chukul_bulk_summary', 180, fn() => $this->scraper->fetchBulkMarketSummary());

        $rows = collect($bulk)->map(fn($s) => [
            'symbol'         => $s['symbol'] ?? null,
            'ltp'            => (float) ($s['close'] ?? 0),
            'change_percent' => (float) ($s['percentage_change'] ?? 0),
            'turnover'       => (float) ($s['amount'] ?? 0),
            'volume'         => (float) ($s['volume'] ?? 0),
        ])->filter(fn($s) => $s['symbol']);

        $gainers       = $rows->sortByDesc('change_percent')->values()->take(3);
        $losers        = $rows->sortBy('change_percent')->values()->take(3);
        $volumeLeaders = $rows->sortByDesc('volume')->values()->take(3);
        $totalTurnover = $this->formatCompactRupees($rows->sum('turnover'));

        return view('landing', compact('indices', 'gainers', 'losers', 'volumeLeaders', 'totalTurnover'));
    }

    private function formatCompactRupees(float $amount): string
    {
        return match (true) {
            $amount >= 1_000_000_000 => 'Rs. ' . round($amount / 1_000_000_000, 1) . 'B',
            $amount >= 1_000_000     => 'Rs. ' . round($amount / 1_000_000, 1) . 'M',
            $amount >= 1_000         => 'Rs. ' . round($amount / 1_000, 1) . 'K',
            default                  => 'Rs. ' . number_format($amount, 0),
        };
    }

    public function syncLive()
    {
        FetchLiveMarketDataJob::dispatch();
        Cache::forget('chukul_stock_list');
        Cache::forget('chukul_sector_list');

        return redirect()->route('dashboard')
            ->with('success', 'Stock list refreshed from Chukul.');
    }
}