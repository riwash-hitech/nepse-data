<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\Stock;
use App\Services\PortfolioService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioService $portfolio)
    {
    }

    public function overview()
    {
        $data = $this->portfolio->overview(Auth::user());

        return view('portfolio.overview', $data);
    }

    public function holdings(Request $request)
    {
        $filters = $request->only(['sector', 'search', 'movement']);
        $data = $this->portfolio->holdingsTable(Auth::user(), $filters);
        $sectors = Sector::orderBy('name')->pluck('name');

        return view('portfolio.holdings', $data + ['sectors' => $sectors, 'filters' => $filters]);
    }

    public function profitLoss()
    {
        $data = $this->portfolio->overview(Auth::user());

        return view('portfolio.profit-loss', $data);
    }

    public function realized()
    {
        $transactions = $this->portfolio->realizedHistory(Auth::user());
        $totalRealized = (float) Auth::user()->portfolioTransactions()->where('type', 'sell')->sum('realized_gain');

        return view('portfolio.realized', compact('transactions', 'totalRealized'));
    }

    public function adjustForm()
    {
        return view('portfolio.adjust');
    }

    public function adjustStore(Request $request)
    {
        $validated = $request->validate([
            'symbol'     => 'required|string|exists:stocks,symbol',
            'type'       => 'required|in:buy,sell',
            'quantity'   => 'required|integer|min:1',
            'rate'       => 'required|numeric|min:0',
            'txn_date'   => 'required|date',
            'remarks'    => 'nullable|string|max:255',
        ]);

        $stock = Stock::where('symbol', $validated['symbol'])->firstOrFail();

        $this->portfolio->applyTransaction(
            Auth::user(),
            $stock,
            $validated['type'],
            (int) $validated['quantity'],
            (float) $validated['rate'],
            $validated['txn_date'],
            $validated['remarks'] ?? null
        );

        Activity::log(Auth::user(), 'portfolio_' . $validated['type'], ucfirst($validated['type']) . " {$validated['quantity']} {$stock->symbol} @ {$validated['rate']}.");

        return redirect()->route('portfolio.adjust')->with('success', ucfirst($validated['type']) . " recorded for {$stock->symbol}.");
    }

    public function transactions(Request $request)
    {
        $filters = $request->only(['symbol', 'type', 'from', 'to']);
        $transactions = $this->portfolio->transactionHistory(Auth::user(), $filters);

        return view('portfolio.transactions', compact('transactions', 'filters'));
    }

    // ── Local-DB stock autocomplete for the Adjust Holdings form ──────────────
    // (separate from StockController::search, which returns live Chukul-list
    // results that don't map to a local stocks.id needed for the FK here)
    public function searchStocks(Request $request)
    {
        $term = trim($request->get('q', ''));
        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $results = Stock::active()->search($term)->limit(10)->get(['id', 'symbol', 'name']);

        return response()->json($results);
    }
}
