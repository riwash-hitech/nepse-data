<?php

namespace App\Http\Controllers;

use App\Models\PortfolioHolding;
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

    /**
     * Quick-add a position straight from the Holdings page, rather than the
     * separate Adjust Holdings page — still goes through applyTransaction
     * so the ledger/realized-gain math stays consistent, it's just a 'buy'
     * dated today with a fixed remark.
     */
    public function quickAdd(Request $request)
    {
        $validated = $request->validate([
            'symbol'   => 'required|string|exists:stocks,symbol',
            'quantity' => 'required|integer|min:1',
            'rate'     => 'required|numeric|min:0',
        ]);

        $stock = Stock::where('symbol', $validated['symbol'])->firstOrFail();

        $this->portfolio->applyTransaction(
            Auth::user(),
            $stock,
            'buy',
            (int) $validated['quantity'],
            (float) $validated['rate'],
            now()->toDateString(),
            'Quick add from Holdings'
        );

        Activity::log(Auth::user(), 'portfolio_quick_add', "Added {$validated['quantity']} {$stock->symbol} @ {$validated['rate']} from Holdings.");

        return redirect()->route('portfolio.holdings')->with('success', "Added {$stock->symbol} to your portfolio.");
    }

    /**
     * Direct override of a holding's quantity/avg cost — unlike
     * adjustStore()/applyTransaction(), this does NOT append a ledger
     * transaction or touch realized gain; it's a manual correction for when
     * the WACC math doesn't match reality (e.g. bonus shares, a data-entry
     * fix), not a buy/sell event.
     */
    public function updateHolding(Request $request, PortfolioHolding $holding)
    {
        abort_unless($holding->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'avg_cost' => 'required|numeric|min:0',
        ]);

        $holding->update([
            'quantity' => $validated['quantity'],
            'avg_cost' => $validated['avg_cost'],
        ]);

        Activity::log(Auth::user(), 'portfolio_edit', "Manually set {$holding->stock->symbol} to {$validated['quantity']} shares @ {$validated['avg_cost']} avg cost.");

        return redirect()->route('portfolio.holdings')->with('success', "Updated {$holding->stock->symbol}.");
    }

    public function destroyHolding(PortfolioHolding $holding)
    {
        abort_unless($holding->user_id === Auth::id(), 403);

        $symbol = $holding->stock->symbol;
        $holding->delete();

        Activity::log(Auth::user(), 'portfolio_delete', "Removed {$symbol} from portfolio.");

        return redirect()->route('portfolio.holdings')->with('success', "Removed {$symbol} from your portfolio.");
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
