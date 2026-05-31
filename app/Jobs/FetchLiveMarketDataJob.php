<?php

namespace App\Jobs;

use App\Services\NepseScraperService;
use App\Services\SignalEngine;
use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchLiveMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(NepseScraperService $scraper, SignalEngine $signalEngine): void
    {
        Log::info('FetchLiveMarketDataJob: starting');

        // 1. Sync stock list + sectors from Chukul
        $scraper->syncSectors();
        $scraper->syncStocks();

        // 2. Live prices (latest candle per active stock)
        $prices = $scraper->fetchLivePrices();
        if (empty($prices)) {
            Log::warning('FetchLiveMarketDataJob: no prices returned');
            return;
        }

        $saved = $scraper->persistLivePrices($prices);
        Log::info("FetchLiveMarketDataJob: saved {$saved} price records");

        // 3. Compute + persist market summary from DB
        $summary = $scraper->computeMarketSummary();
        if ($summary) {
            $scraper->persistMarketSummary($summary);
        }

        Cache::forget('latest_trading_date');

        // 4. Run signal engine for active stocks
        $stocks = Stock::active()->get();
        foreach ($stocks as $stock) {
            try {
                $signalEngine->analyze($stock);
            } catch (\Throwable $e) {
                Log::warning("SignalEngine failed for {$stock->symbol}: {$e->getMessage()}");
            }
        }

        Log::info('FetchLiveMarketDataJob: completed');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchLiveMarketDataJob failed: ' . $exception->getMessage());
    }
}
