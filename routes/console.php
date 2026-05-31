<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchLiveMarketDataJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────
//  NEPSE Scheduler (Nepal Stock Exchange)
//  Trading hours: ~11:00 AM – 3:00 PM NPT (UTC+5:45)
// ──────────────────────────────────────────────

// Live price fetch: every 5 minutes on weekdays
Schedule::job(new FetchLiveMarketDataJob)
    ->everyFiveMinutes()
    ->weekdays()
    ->between('5:15', '9:15') // 11:00–15:00 NPT = 05:15–09:15 UTC
    ->name('fetch-live-market-data')
    ->withoutOverlapping();

// Historical data refresh: every hour on weekdays
Schedule::command('nepse:scrape --type=historical --days=30')
    ->hourly()
    ->weekdays()
    ->name('scrape-historical');

// Signal generation: every 15 minutes during trading
Schedule::command('nepse:signals')
    ->everyFifteenMinutes()
    ->weekdays()
    ->between('5:15', '9:30')
    ->name('generate-signals')
    ->withoutOverlapping();

// Floorsheet: once daily after market close
Schedule::command('nepse:scrape --type=floorsheet')
    ->dailyAt('10:00') // ~15:45 NPT
    ->weekdays()
    ->name('scrape-floorsheet');
