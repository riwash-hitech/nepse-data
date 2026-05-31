<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $watchlist = Watchlist::with(['stock.latestPrice', 'stock.latestSignal'])
            ->where('user_id', Auth::id())
            ->orderBy('sort_order')
            ->get();

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
