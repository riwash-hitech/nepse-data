<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NepseScraperService;
use Illuminate\Support\Facades\Cache;

class SyncController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper)
    {
    }

    public function run()
    {
        $sectors = $this->scraper->syncSectors();
        $stocks  = $this->scraper->syncStocks();

        Cache::forget('chukul_stock_list');

        return back()->with('success', "Synced {$sectors} sectors and {$stocks} stocks to the database.");
    }
}
