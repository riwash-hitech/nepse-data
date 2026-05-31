<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\SignalEngine;
use Illuminate\Console\Command;

class GenerateSignalsCommand extends Command
{
    protected $signature = 'nepse:signals
                            {--symbol= : Generate for a single symbol}
                            {--force : Re-generate even if signal exists today}';

    protected $description = 'Generate buy/sell signals for all active stocks';

    public function handle(SignalEngine $engine): int
    {
        $symbol = $this->option('symbol');
        $stocks = $symbol
            ? Stock::where('symbol', strtoupper($symbol))->get()
            : Stock::active()->get();

        $bar = $this->output->createProgressBar($stocks->count());
        $bar->start();

        $generated = 0;
        foreach ($stocks as $stock) {
            try {
                $signal = $engine->analyze($stock);
                if ($signal) {
                    $generated++;
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Failed for {$stock->symbol}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Generated {$generated} signals.");

        return self::SUCCESS;
    }
}
