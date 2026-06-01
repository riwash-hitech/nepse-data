<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\ScreenerController;
use App\Http\Controllers\TopPicksController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

// ── Main Dashboard ────────────────────────────────────────────────────────────
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/sync-market', [DashboardController::class, 'syncLive'])->name('dashboard.sync');

// ── Stocks / Markets ──────────────────────────────────────────────────────────
Route::get('/markets', [StockController::class, 'index'])->name('stocks.index');
Route::get('/stocks/{symbol}', [StockController::class, 'show'])->name('stocks.show');
Route::get('/api/search', [StockController::class, 'search'])->name('stocks.search');
Route::get('/api/stocks/{symbol}/chart', [StockController::class, 'chartData'])->name('stocks.chart');

// ── Signals ───────────────────────────────────────────────────────────────────
Route::get('/signals', [SignalController::class, 'index'])->name('signals.index');
Route::get('/signals/{id}', [SignalController::class, 'show'])->name('signals.show');

// ── Top Picks ─────────────────────────────────────────────────────────────────
Route::get('/top-picks', [TopPicksController::class, 'index'])->name('top-picks.index');
Route::post('/top-picks/refresh', [TopPicksController::class, 'refresh'])->name('top-picks.refresh');

// ── Screener ──────────────────────────────────────────────────────────────────
Route::get('/screener', [ScreenerController::class, 'index'])->name('screener.index');

// ── Auth-protected ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{stock}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
