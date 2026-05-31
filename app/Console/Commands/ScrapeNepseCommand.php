<?php

namespace App\Console\Commands;

use App\Jobs\FetchLiveMarketDataJob;
use App\Jobs\FetchHistoricalDataJob;
use App\Jobs\FetchFloorsheetJob;
use App\Models\Stock;
use Illuminate\Console\Command;

class ScrapeNepseCommand extends Command
{
    protected $signature = 'nepse:scrape
                            {--type=live : Type: live|historical|floorsheet|all}
                            {--symbol= : Single symbol to scrape}
                            {--days=365 : Days for historical data}
                            {--sync : Run synchronously instead of queuing}';

    protected $description = 'Scrape NEPSE market data';

    public function handle(): int
    {
        $type   = $this->option('type');
        $symbol = $this->option('symbol');
        $days   = (int)$this->option('days');
        $sync   = $this->option('sync');

        if ($type === 'live' || $type === 'all') {
            $scraper = app(\App\Services\NepseScraperService::class);
            if ($sync) {
                $this->info('Syncing sectors...');
                $s = $scraper->syncSectors();
                $this->info("  → {$s} sectors");

                $this->info('Syncing stock list...');
                $st = $scraper->syncStocks();
                $this->info("  → {$st} stocks");

                $stocks = \App\Models\Stock::active()->pluck('symbol');
                $bar = $this->output->createProgressBar($stocks->count());
                $bar->start();
                $saved = 0;
                foreach ($stocks as $sym) {
                    $prices = $scraper->fetchHistoricalPrices($sym, 30);
                    $stock  = \App\Models\Stock::where('symbol', $sym)->first();
                    if ($stock && $prices) {
                        $saved += $scraper->persistHistoricalPrices($stock, $prices);
                    }
                    $bar->advance();
                }
                $bar->finish();
                $this->newLine();
                $this->info("Saved {$saved} price records.");

                $summary = $scraper->computeMarketSummary();
                if ($summary) {
                    $scraper->persistMarketSummary($summary);
                    $this->info('Market summary updated.');
                }
            } else {
                FetchLiveMarketDataJob::dispatch();
                $this->info('Live data job queued.');
            }
        }

        if ($type === 'historical' || $type === 'all') {
            $stocks = $symbol
                ? Stock::where('symbol', strtoupper($symbol))->get()
                : Stock::active()->get();

            $this->info("Dispatching historical jobs for {$stocks->count()} stocks...");
            foreach ($stocks as $stock) {
                FetchHistoricalDataJob::dispatch($stock->symbol, $days);
            }
            $this->info('Historical jobs queued.');
        }

        if ($type === 'floorsheet' || $type === 'all') {
            $stocks = $symbol
                ? Stock::where('symbol', strtoupper($symbol))->get()
                : Stock::active()->limit(50)->get();

            $date = now()->toDateString();
            $this->info("Dispatching floorsheet jobs for {$stocks->count()} stocks...");
            foreach ($stocks as $stock) {
                FetchFloorsheetJob::dispatch($stock->symbol, $date);
            }
            $this->info('Floorsheet jobs queued.');
        }

        return self::SUCCESS;
    }
}
