<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\WatchlistController;

// ── Public ──────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

Route::get('/markets', [MarketController::class, 'index']);
Route::get('/markets/search', [MarketController::class, 'search']);
Route::get('/dashboard/indices', [MarketController::class, 'indices']);
Route::get('/dashboard/movers', [MarketController::class, 'movers']);
Route::get('/stocks/{symbol}', [MarketController::class, 'show']);
Route::get('/stocks/{symbol}/chart', [MarketController::class, 'chartData']);

// ── Authenticated (Sanctum bearer token) ─────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/watchlist', [WatchlistController::class, 'index']);
    Route::post('/watchlist', [WatchlistController::class, 'store']);
    Route::delete('/watchlist/{stock}', [WatchlistController::class, 'destroy']);

    Route::prefix('portfolio')->group(function () {
        Route::get('/overview', [PortfolioController::class, 'overview']);
        Route::get('/holdings', [PortfolioController::class, 'holdings']);
        Route::get('/realized', [PortfolioController::class, 'realized']);
        Route::get('/transactions', [PortfolioController::class, 'transactions']);
        Route::post('/adjust', [PortfolioController::class, 'adjustStore']);
        Route::get('/stocks/search', [PortfolioController::class, 'searchStocks']);
    });
});
