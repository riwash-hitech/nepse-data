<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{DashboardController, IpoController, OutlookController, PortfolioController, ProfileController, ScreenerController, SignalController, StockController, TopPicksController, WatchlistController};
use App\Http\Controllers\Admin\UserManagementController;

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

// ── Top Picks (public, auth gates details in view) ──────────────────────────
Route::get('/top-picks', [TopPicksController::class, 'index'])->name('top-picks.index');
Route::post('/top-picks/refresh', [TopPicksController::class, 'refresh'])->name('top-picks.refresh');

// ── IPO Result Checker ────────────────────────────────────────────────────────
Route::get('/ipo', [IpoController::class, 'index'])->name('ipo.index');
Route::post('/ipo/refresh', [IpoController::class, 'refreshCompanies'])->name('ipo.refresh');

// ── Screener ──────────────────────────────────────────────────────────────────
Route::get('/screener', [ScreenerController::class, 'index'])->name('screener.index');

// ── 30-Day Outlook (public) ──────────────────────────────────────────────────
Route::get('/outlook', [OutlookController::class, 'index'])->name('outlook.index');
Route::post('/outlook/refresh', [OutlookController::class, 'refresh'])->name('outlook.refresh');

// ── Auth-protected ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
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

    Route::middleware('admin')->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
