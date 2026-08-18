<?php

namespace App\Http\Controllers;

use App\Jobs\FetchLiveMarketDataJob;
use App\Services\NepseScraperService;
use App\Services\PortfolioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly NepseScraperService $scraper,
        private readonly PortfolioService $portfolio,
    ) {}

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

        // Portfolio P&L snapshot for logged-in users only
        $portfolioOverview = null;
        if (Auth::check()) {
            try {
                $portfolioOverview = $this->portfolio->overview(Auth::user());
            } catch (\Throwable $e) {
                $portfolioOverview = null;
            }
        }

        // NEPSE index quote for the hero card
        $nepseIndex = Cache::remember('chukul_index_NEPSE', 300, fn() => $this->scraper->fetchIndexQuote('NEPSE'));
        $marketStatus = $this->marketStatus();

        // Live market-wide summary (turnover / shares traded) + per-sector
        // performance + top-volume list, all from the same bulk quote pull.
        $bulk = Cache::remember('chukul_bulk_summary', 180, fn() => $this->scraper->fetchBulkMarketSummary());
        $sectorBySymbol = $active->pluck('sector', 'symbol');

        $bulkRows = collect($bulk)->map(fn($s) => [
            'symbol'         => $s['symbol'] ?? null,
            'ltp'            => (float) ($s['close'] ?? 0),
            'change_percent' => (float) ($s['percentage_change'] ?? 0),
            'turnover'       => (float) ($s['amount'] ?? 0),
            'volume'         => (float) ($s['volume'] ?? 0),
            'sector'         => $sectorBySymbol[$s['symbol'] ?? ''] ?? null,
        ])->filter(fn($s) => $s['symbol']);

        $marketSummary = [
            'turnover' => $this->formatCompactRupees($bulkRows->sum('turnover')),
            'volume'   => $this->formatCompactNumber($bulkRows->sum('volume')),
        ];

        $sectorPerformance = $bulkRows
            ->filter(fn($r) => $r['sector'])
            ->groupBy('sector')
            ->map(fn($g, $name) => [
                'name'   => $name,
                'count'  => $g->count(),
                'weight' => $totalStocks > 0 ? round($g->count() / $totalStocks * 100, 1) : 0,
                'change' => round($g->avg('change_percent'), 2),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(3);

        $topVolume = $bulkRows->sortByDesc('volume')->values()->take(4);

        return view('dashboard.index', compact(
            'stockList', 'sectors', 'totalStocks', 'sectorStats', 'portfolioOverview',
            'nepseIndex', 'marketStatus', 'marketSummary', 'sectorPerformance', 'topVolume'
        ));
    }

    /**
     * Heuristic only (Sun–Thu, 11:00–15:00 Nepal time) — NEPSE doesn't expose
     * a live session-status API, so this is a documented approximation,
     * mirrored from the mobile app's market_hours.dart.
     */
    private function marketStatus(): array
    {
        $now = Carbon::now('Asia/Kathmandu');
        $isTradingDay = $now->dayOfWeekIso !== Carbon::FRIDAY && $now->dayOfWeekIso !== Carbon::SATURDAY;
        $open = $now->copy()->setTime(11, 0);
        $close = $now->copy()->setTime(15, 0);
        $isOpen = $isTradingDay && $now->between($open, $close);

        return [
            'open'    => $isOpen,
            'closesIn' => $isOpen ? $now->diff($close)->format('%hh %im') : null,
        ];
    }

    private function formatCompactNumber(float $amount): string
    {
        return match (true) {
            $amount >= 1_000_000_000 => round($amount / 1_000_000_000, 1) . 'B',
            $amount >= 1_000_000     => round($amount / 1_000_000, 1) . 'M',
            $amount >= 1_000         => round($amount / 1_000, 1) . 'K',
            default                  => number_format($amount, 0),
        };
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