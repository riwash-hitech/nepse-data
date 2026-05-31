<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Services\NepseScraperService;
use App\Services\SignalEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchHistoricalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(
        private readonly string $symbol,
        private readonly int    $days = 365
    ) {}

    public function handle(NepseScraperService $scraper, SignalEngine $signalEngine): void
    {
        Log::info("FetchHistoricalDataJob: {$this->symbol} last {$this->days} days");

        $stock = Stock::where('symbol', $this->symbol)->first();
        if (!$stock) {
            Log::warning("FetchHistoricalDataJob: stock {$this->symbol} not found");
            return;
        }

        $prices = $scraper->fetchHistoricalPrices($this->symbol, $this->days);
        if (empty($prices)) {
            Log::warning("FetchHistoricalDataJob: no historical data for {$this->symbol}");
            return;
        }

        $saved = $scraper->persistHistoricalPrices($stock, $prices);
        Log::info("FetchHistoricalDataJob: saved {$saved} rows for {$this->symbol}");

        // Signal engine
        try {
            $signalEngine->analyze($stock);
        } catch (\Throwable $e) {
            Log::warning("SignalEngine failed for {$this->symbol}: {$e->getMessage()}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("FetchHistoricalDataJob failed for {$this->symbol}: " . $exception->getMessage());
    }
}

