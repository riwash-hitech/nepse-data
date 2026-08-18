<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\NepseScraperService;
use App\Services\SignalEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('nepse:scrape-now')]
#[Description('One-shot synchronous scrape + signal refresh — syncs stocks/sectors, backfills price history, and recomputes signals, all inline with no queue involved. Meant for the "Scrape Now" admin button and for manual runs; the scheduled nepse:scrape/nepse:signals commands (which do rely on the queue) are what keep things fresh automatically.')]
class ScrapeNow extends Command
{
    public function handle(NepseScraperService $scraper, SignalEngine $engine): int
    {
        $this->info('Syncing sectors and stocks…');
        $sectors = $scraper->syncSectors();
        $stocksSynced = $scraper->syncStocks();
        Cache::forget('chukul_stock_list');
        Cache::forget('chukul_sector_list');
        $this->info("Synced {$sectors} sectors, {$stocksSynced} stocks.");

        $stocks = Stock::active()->get();
        $this->info("Backfilling price history + signals for {$stocks->count()} active stocks…");
        $bar = $this->output->createProgressBar($stocks->count());
        $bar->start();

        $rowsSaved = 0;
        $signalsOk = 0;
        $errors = 0;

        foreach ($stocks as $stock) {
            try {
                $rows = $scraper->fetchHistoricalPrices($stock->symbol);
                if (!empty($rows)) {
                    $rowsSaved += $scraper->persistHistoricalPrices($stock, $rows);
                    if ($engine->analyze($stock)) {
                        $signalsOk++;
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        Cache::forget('chukul_bulk_summary');
        Cache::forget('latest_trading_date');

        $summary = "Done — {$stocks->count()} stocks processed, {$rowsSaved} price rows saved, {$signalsOk} signals computed, {$errors} errors.";
        $this->info($summary);

        return self::SUCCESS;
    }
}
