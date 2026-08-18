<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{DashboardController, IpoController, OutlookController, PortfolioController, ProfileController, ScreenerController, SignalController, StockController, TopPicksController, WatchlistController};
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\LogViewerController;

// ── URL-triggered cron (for hosts without SSH/real cron access) ─────────────
// Point an external "hit this URL every minute" service (cron-job.org,
// EasyCron, cPanel's own cron-via-URL, etc.) at this exact URL — it runs
// the same Laravel scheduler that `php artisan schedule:run` would, so the
// nepse:scrape / nepse:signals / live-price jobs in routes/console.php stay
// on their configured cadence. The token comes from CRON_SECRET in .env —
// anyone who doesn't know it just gets a 404.
Route::get('/cron/{token}', function (string $token) {
    if (!config('app.cron_secret') || !hash_equals(config('app.cron_secret'), $token)) {
        abort(404);
    }

    Artisan::call('schedule:run');

    return response(Artisan::output() ?: "Scheduler ran at " . now()->toDateTimeString() . ".\n", 200)
        ->header('Content-Type', 'text/plain');
})->where('token', '[A-Za-z0-9]+');

// ── Public marketing page ──────────────────────────────────────────────────────
// Shown to everyone, logged in or not — logged-in users reach the dashboard
// via the nav link, not an automatic redirect.
Route::get('/', [DashboardController::class, 'landing'])->name('landing');

// ── Main Dashboard ────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/sync-market', [DashboardController::class, 'syncLive'])->name('dashboard.sync');

// ── Stocks / Markets ──────────────────────────────────────────────────────────
Route::get('/markets', [StockController::class, 'index'])->name('stocks.index');
Route::get('/stocks/{symbol}', [StockController::class, 'show'])->name('stocks.show');
Route::get('/api/search', [StockController::class, 'search'])->name('stocks.search');
Route::get('/api/stocks/{symbol}/chart', [StockController::class, 'chartData'])->name('stocks.chart');

// ── Signals ───────────────────────────────────────────────────────────────────
Route::get('/signals', [SignalController::class, 'index'])->name('signals.index');
Route::get('/signals/{id}', [SignalController::class, 'show'])->name('signals.show');

// ── Top Picks (public, auth gates details in view) ──────────────────────────
Route::get('/top-picks', [TopPicksController::class, 'index'])->name('top-picks.index');
Route::post('/top-picks/refresh', [TopPicksController::class, 'refresh'])->name('top-picks.refresh');

// ── IPO Result Checker ────────────────────────────────────────────────────────
Route::get('/ipo', [IpoController::class, 'index'])->name('ipo.index');
Route::post('/ipo/refresh', [IpoController::class, 'refreshCompanies'])->name('ipo.refresh');

// ── Screener ──────────────────────────────────────────────────────────────────
Route::get('/screener', [ScreenerController::class, 'index'])->name('screener.index');

// ── Auth-protected ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'no-cache'])->group(function () {
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{stock}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    Route::get('/api/portfolio/stocks', [PortfolioController::class, 'searchStocks'])->name('portfolio.search');
    Route::prefix('portfolio')->name('portfolio.')->group(function () {
        Route::get('/', [PortfolioController::class, 'overview'])->name('overview');
        Route::get('/holdings', [PortfolioController::class, 'holdings'])->name('holdings');
        Route::get('/profit-loss', [PortfolioController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/realized', [PortfolioController::class, 'realized'])->name('realized');
        Route::get('/adjust', [PortfolioController::class, 'adjustForm'])->name('adjust');
        Route::post('/adjust', [PortfolioController::class, 'adjustStore'])->name('adjust.store');
        Route::get('/transactions', [PortfolioController::class, 'transactions'])->name('transactions');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->group(function () {
        Route::prefix('admin/users')->name('admin.users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-block', [UserManagementController::class, 'toggleBlock'])->name('toggle-block');
            Route::post('/{user}/force-logout', [UserManagementController::class, 'forceLogout'])->name('force-logout');
        });

        Route::post('/admin/sync-stocks', [SyncController::class, 'run'])->name('admin.sync-stocks');
        Route::get('/admin/logs', [LogViewerController::class, 'index'])->name('admin.logs');

        Route::get('/outlook', [OutlookController::class, 'index'])->name('outlook.index');
        Route::post('/outlook/generate', [OutlookController::class, 'generate'])->name('outlook.generate');
        Route::get('/outlook/export', [OutlookController::class, 'export'])->name('outlook.export');
    });
});

require __DIR__.'/auth.php';
