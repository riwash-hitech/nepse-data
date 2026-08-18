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

// NEPSE trades Sunday–Thursday, NOT the Mon–Fri work-week — ->weekdays()
// was wrong here: it silently skipped Sunday (a real trading day) and ran
// on Friday (not a trading day, harmless but pointless). 0=Sun..4=Thu.
$nepseTradingDays = [0, 1, 2, 3, 4];

// Live price fetch: every 5 minutes on trading days
Schedule::job(new FetchLiveMarketDataJob)
    ->everyFiveMinutes()
    ->days($nepseTradingDays)
    ->between('5:15', '9:15') // 11:00–15:00 NPT = 05:15–09:15 UTC
    ->name('fetch-live-market-data')
    ->withoutOverlapping();

// Historical data refresh: every hour on trading days
Schedule::command('nepse:scrape --type=historical --days=30')
    ->hourly()
    ->days($nepseTradingDays)
    ->name('scrape-historical');

// Signal generation: every 15 minutes during trading
Schedule::command('nepse:signals')
    ->everyFifteenMinutes()
    ->days($nepseTradingDays)
    ->between('5:15', '9:30')
    ->name('generate-signals')
    ->withoutOverlapping();

// Floorsheet: once daily after market close
Schedule::command('nepse:scrape --type=floorsheet')
    ->dailyAt('10:00') // ~15:45 NPT
    ->days($nepseTradingDays)
    ->name('scrape-floorsheet');
