<?php

namespace App\Jobs;

use App\Models\Stock;
use App\Models\Floorsheet;
use App\Services\NepseScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchFloorsheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly string $symbol,
        private readonly string $date
    ) {}

    public function handle(NepseScraperService $scraper): void
    {
        Log::info("FetchFloorsheetJob: {$this->symbol} for {$this->date}");

        $stock = Stock::where('symbol', $this->symbol)->first();
        if (!$stock) {
            return;
        }

        $rows = $scraper->fetchFloorsheet($this->symbol, $this->date);
        if (empty($rows)) {
            return;
        }

        $saved = $scraper->persistFloorsheet($stock, $rows);
        Log::info("FetchFloorsheetJob: saved {$saved} floorsheet rows for {$this->symbol}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("FetchFloorsheetJob failed for {$this->symbol}: " . $exception->getMessage());
    }
}
