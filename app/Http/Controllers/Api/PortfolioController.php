<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioService $portfolio)
    {
    }

    public function overview(Request $request)
    {
        $data = $this->portfolio->overview($request->user());
        unset($data['holdings']); // raw Eloquent models with stock relation — trim for API

        return response()->json($data);
    }

    public function holdings(Request $request)
    {
        $filters = $request->only(['sector', 'search', 'movement']);
        $data = $this->portfolio->holdingsTable($request->user(), $filters);

        $data['rows'] = collect($data['rows'])->map(fn($r) => [
            'symbol'          => $r['symbol'],
            'name'            => $r['name'],
            'sector'          => $r['sector'],
            'quantity'        => $r['quantity'],
            'avg_cost'        => round($r['avg_cost'], 4),
            'ltp'             => round($r['ltp'], 2),
            'change_percent'  => round($r['change_percent'], 2),
            'investment'      => round($r['investment'], 2),
            'market_value'    => round($r['market_value'], 2),
            'day_gain_loss'   => round($r['day_gain_loss'], 2),
            'unrealized_gain' => round($r['unrealized_gain'], 2),
        ])->values();

        return response()->json($data);
    }

    public function adjustStore(Request $request)
    {
        $validated = $request->validate([
            'symbol'   => 'required|string|exists:stocks,symbol',
            'type'     => 'required|in:buy,sell',
            'quantity' => 'required|integer|min:1',
            'rate'     => 'required|numeric|min:0',
            'txn_date' => 'required|date',
            'remarks'  => 'nullable|string|max:255',
        ]);

        $stock = Stock::where('symbol', $validated['symbol'])->firstOrFail();

        $txn = $this->portfolio->applyTransaction(
            $request->user(),
            $stock,
            $validated['type'],
            (int) $validated['quantity'],
            (float) $validated['rate'],
            $validated['txn_date'],
            $validated['remarks'] ?? null
        );

        return response()->json(['message' => ucfirst($validated['type'])." recorded for {$stock->symbol}.", 'transaction' => $txn]);
    }

    public function transactions(Request $request)
    {
        $filters = $request->only(['symbol', 'type', 'from', 'to']);
        $transactions = $this->portfolio->transactionHistory($request->user(), $filters);

        return response()->json($transactions);
    }

    public function realized(Request $request)
    {
        $transactions = $this->portfolio->realizedHistory($request->user());
        $totalRealized = (float) $request->user()->portfolioTransactions()->where('type', 'sell')->sum('realized_gain');

        return response()->json(['transactions' => $transactions, 'total_realized' => $totalRealized]);
    }

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
