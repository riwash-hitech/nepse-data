<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchLiveMarketDataJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────
//  NEPSE Scheduler (Nepal Stock Exchange)
//  Trading hours: ~11:00 AM – 3:00 PM NPT (UTC+5:45)
//
//  Every entry below logs to storage/logs/schedule.log so you can see
//  exactly what ran (and its output/errors) instead of guessing —
//  tail that file after a cron tick to check.
// ──────────────────────────────────────────────

// NEPSE trades Sunday–Thursday, NOT the Mon–Fri work-week — ->weekdays()
// was wrong here: it silently skipped Sunday (a real trading day) and ran
// on Friday (not a trading day, harmless but pointless). 0=Sun..4=Thu.
$nepseTradingDays = [0, 1, 2, 3, 4];
$scheduleLog = storage_path('logs/schedule.log');

// Live price fetch: every 5 minutes on trading days
Schedule::job(new FetchLiveMarketDataJob)
    ->everyFiveMinutes()
    ->days($nepseTradingDays)
    ->between('5:15', '9:15') // 11:00–15:00 NPT = 05:15–09:15 UTC
    ->name('fetch-live-market-data')
    ->withoutOverlapping()
    ->before(fn () => Log::info('[schedule] fetch-live-market-data: dispatching'))
    ->after(fn () => Log::info('[schedule] fetch-live-market-data: dispatched'));

// Historical data refresh: every hour on trading days
Schedule::command('nepse:scrape --type=historical --days=30')
    ->hourly()
    ->days($nepseTradingDays)
    ->name('scrape-historical')
    ->appendOutputTo($scheduleLog);

// Signal generation: every 15 minutes during trading
Schedule::command('nepse:signals')
    ->everyFifteenMinutes()
    ->days($nepseTradingDays)
    ->between('5:15', '9:30')
    ->name('generate-signals')
    ->withoutOverlapping()
    ->appendOutputTo($scheduleLog);

// Floorsheet: once daily after market close
Schedule::command('nepse:scrape --type=floorsheet')
    ->dailyAt('10:00') // ~15:45 NPT
    ->days($nepseTradingDays)
    ->name('scrape-floorsheet')
    ->appendOutputTo($scheduleLog);

// Every single schedule:run tick, whether or not anything was due, so you
// can tell "cron isn't hitting the server" apart from "cron hits fine but
// nothing was due yet" — the timestamp updates every minute either way.
Schedule::call(fn () => Log::info('[schedule] tick — schedule:run invoked'))
    ->everyMinute()
    ->name('heartbeat');
