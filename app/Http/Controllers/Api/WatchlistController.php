<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Watchlist;
use App\Services\NepseScraperService;
use App\Services\SignalEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WatchlistController extends Controller
{
    public function __construct(
        private readonly NepseScraperService $scraper,
        private readonly SignalEngine $signalEngine
    ) {}

    public function index(Request $request)
    {
        $entries = Watchlist::with(['stock.sector'])
            ->where('user_id', $request->user()->id)
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
                'sector'         => $stock->sector?->name,
                'ltp'            => $last ? (float) $last['close'] : null,
                'change_percent' => $last ? (float) ($last['change_percent'] ?? 0) : null,
                'signal_type'    => $signal['signal_type'] ?? null,
                'confidence'     => $signal['confidence'] ?? null,
            ];
        });

        return response()->json($watchlist);
    }

    public function store(Request $request)
    {
        $request->validate(['stock_id' => 'required|exists:stocks,id']);

        $entry = Watchlist::firstOrCreate([
            'user_id'  => $request->user()->id,
            'stock_id' => $request->stock_id,
        ]);

        return response()->json(['message' => 'Stock added to watchlist.', 'watchlist' => $entry]);
    }

    public function destroy(Request $request, int $stockId)
    {
        Watchlist::where('user_id', $request->user()->id)
            ->where('stock_id', $stockId)
            ->delete();

        return response()->json(['message' => 'Removed from watchlist.']);
    }
}
