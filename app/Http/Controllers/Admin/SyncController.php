<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NepseScraperService;
use App\Support\Activity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SyncController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper)
    {
    }

    public function run()
    {
        $check = $this->scraper->testConnection();
        if (!$check['ok']) {
            return back()->with('error', "Sync failed — {$check['message']}");
        }

        $sectors = $this->scraper->syncSectors();
        $stocks  = $this->scraper->syncStocks();

        Cache::forget('chukul_stock_list');

        Activity::log(Auth::user(), 'admin_sync', "Synced {$sectors} sectors and {$stocks} stocks.");

        return back()->with('success', "Synced {$sectors} sectors and {$stocks} stocks to the database.");
    }

    /**
     * "Scrape Now" — the full price-history + signal refresh, run inline
     * (no queue) so it works immediately from a single button click. This
     * can take a couple of minutes for ~650 stocks; raised here so a slow
     * host/proxy timeout doesn't cut it off mid-way (safe to just click
     * again if it does — everything it does is an upsert).
     */
    public function scrapeNow()
    {
        set_time_limit(0);

        Artisan::call('nepse:scrape-now');

        // The command's output includes a live progress bar (carriage
        // returns, etc.) — pull out just the final "Done — ..." summary
        // line rather than dumping the raw console output into a flash box.
        $lines = array_filter(array_map('trim', explode("\n", Artisan::output())));
        $summary = collect($lines)->last(fn ($line) => str_starts_with($line, 'Done —'));

        Activity::log(Auth::user(), 'admin_scrape_now', $summary ?: 'Ran Scrape Now.');

        return back()->with('success', $summary ?: 'Scrape finished.');
    }
}
