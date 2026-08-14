<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\NepseScraperService;
use App\Services\SignalEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WatchlistController extends Controller
{
    public function __construct(
        private readonly NepseScraperService $scraper,
        private readonly SignalEngine $signalEngine
    ) {}

    public function index()
    {
        $entries = Watchlist::with(['stock.sector'])
            ->where('user_id', Auth::id())
            ->orderBy('sort_order')
            ->get();

        $watchlist = $entries->map(function (Watchlist $w) {
            $stock = $w->stock;

            $priceRows = Cache::remember("chukul_adj_{$stock->symbol}", 300, fn() =>
                $this->scraper->fetchHistoricalPrices($stock->symbol)
            );

            $last = !empty($priceRows) ? end($priceRows) : null;
            $analytics = !empty($priceRows) ? $this->signalEngine->analyzeFromData($priceRows) : [];
            $signal = $analytics['signal'] ?? null;

            return [
                'watchlist_id'   => $w->id,
                'stock_id'       => $w->stock_id,
                'symbol'         => $stock->symbol,
                'name'           => $stock->name,
                'ltp'            => $last ? (float) $last['close'] : null,
                'change_percent' => $last ? (float) ($last['change_percent'] ?? 0) : null,
                'signal_type'    => $signal['signal_type'] ?? null,
                'confidence'     => $signal['confidence'] ?? null,
            ];
        });

        return view('watchlist.index', compact('watchlist'));
    }

    public function store(Request $request)
    {
        $request->validate(['stock_id' => 'required|exists:stocks,id']);

        Watchlist::firstOrCreate([
            'user_id'  => Auth::id(),
            'stock_id' => $request->stock_id,
        ]);

        return back()->with('success', 'Stock added to watchlist.');
    }

    public function destroy(int $stockId)
    {
        Watchlist::where('user_id', Auth::id())
            ->where('stock_id', $stockId)
            ->delete();

        return back()->with('success', 'Removed from watchlist.');
    }
}
