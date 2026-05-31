<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\Signal;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScreenerController extends Controller
{
    public function index(Request $request)
    {
        $latestDate = Cache::remember('latest_trading_date', 300, fn() =>
            StockPrice::max('date') ?? now()->toDateString()
        );

        $query = Stock::active()
            ->select('stocks.*')
            ->join('stock_prices as sp', function ($join) use ($latestDate) {
                $join->on('sp.stock_id', '=', 'stocks.id')
                     ->where('sp.date', '=', $latestDate);
            })
            ->leftJoin('indicators as ind', function ($join) use ($latestDate) {
                $join->on('ind.stock_id', '=', 'stocks.id')
                     ->where('ind.date', '=', $latestDate);
            })
            ->with(['sector', 'latestSignal']);

        // Filters
        if ($sector = $request->get('sector')) {
            $query->where('stocks.sector_id', $sector);
        }
        if ($minRsi = $request->get('rsi_min')) {
            $query->where('ind.rsi_14', '>=', $minRsi);
        }
        if ($maxRsi = $request->get('rsi_max')) {
            $query->where('ind.rsi_14', '<=', $maxRsi);
        }
        if ($signal = $request->get('signal')) {
            $query->join('signals as sig', function ($join) use ($latestDate, $signal) {
                $join->on('sig.stock_id', '=', 'stocks.id')
                     ->where('sig.signal_type', '=', strtoupper($signal))
                     ->where('sig.is_active', '=', 1);
            });
        }
        if ($minChange = $request->get('change_min')) {
            $query->where('sp.change_percent', '>=', $minChange);
        }
        if ($maxChange = $request->get('change_max')) {
            $query->where('sp.change_percent', '<=', $maxChange);
        }
        if ($minVol = $request->get('vol_min')) {
            $query->where('sp.volume', '>=', $minVol);
        }

        $sortBy = $request->get('sort', 'sp.change_percent');
        $dir    = $request->get('dir', 'desc');
        $allowedSorts = ['sp.change_percent', 'sp.volume', 'sp.close', 'sp.turnover', 'ind.rsi_14'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'sp.change_percent';
        }

        $stocks  = $query->orderBy($sortBy, $dir === 'asc' ? 'asc' : 'desc')->paginate(50)->withQueryString();
        $sectors = Sector::orderBy('name')->get();

        return view('screener.index', compact('stocks', 'sectors'));
    }
}
