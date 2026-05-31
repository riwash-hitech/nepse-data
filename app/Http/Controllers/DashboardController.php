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

    public function syncLive()
    {
        FetchLiveMarketDataJob::dispatch();
        Cache::forget('chukul_stock_list');
        Cache::forget('chukul_sector_list');

        return redirect()->route('dashboard')
            ->with('success', 'Stock list refreshed from Chukul.');
    }
}