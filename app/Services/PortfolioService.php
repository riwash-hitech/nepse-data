<?php

namespace App\Services;

use App\Models\PortfolioHolding;
use App\Models\PortfolioTransaction;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Portfolio WACC (weighted-average cost) engine.
 *
 * One holding row per user+stock tracks the current position; every buy/sell
 * also appends an immutable PortfolioTransaction row for the ledger/history.
 * Average cost only moves on buys — selling never changes avg_cost, it only
 * realizes the gain/loss against whatever the average cost already was.
 */
class PortfolioService
{
    public function __construct(private readonly NepseScraperService $scraper)
    {
    }

    public static function applyTransaction(
        User $user,
        Stock $stock,
        string $type,
        int $quantity,
        float $rate,
        string $txnDate,
        ?string $remarks = null
    ): PortfolioTransaction {
        return DB::transaction(function () use ($user, $stock, $type, $quantity, $rate, $txnDate, $remarks) {
            $holding = PortfolioHolding::lockForUpdate()
                ->firstOrNew(['user_id' => $user->id, 'stock_id' => $stock->id]);
            $holding->quantity ??= 0;
            $holding->avg_cost ??= 0;

            $realizedGain = null;

            if ($type === 'buy') {
                $newQty = $holding->quantity + $quantity;
                $holding->avg_cost = $newQty > 0
                    ? (($holding->quantity * $holding->avg_cost) + ($quantity * $rate)) / $newQty
                    : 0;
                $holding->quantity = $newQty;
            } elseif ($type === 'sell') {
                if ($quantity > $holding->quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "You only hold {$holding->quantity} shares of {$stock->symbol} — cannot sell {$quantity}.",
                    ]);
                }
                $realizedGain = ($rate - (float) $holding->avg_cost) * $quantity;
                $holding->quantity -= $quantity;
            }

            $holding->user_id = $user->id;
            $holding->stock_id = $stock->id;
            $holding->save();

            return PortfolioTransaction::create([
                'user_id'       => $user->id,
                'stock_id'      => $stock->id,
                'type'          => $type,
                'quantity'      => $quantity,
                'rate'          => $rate,
                'txn_date'      => $txnDate,
                'realized_gain' => $realizedGain,
                'remarks'       => $remarks,
            ]);
        });
    }

    /**
     * Per-holding valuation shared by the overview and holdings pages.
     * Net worth = market value here (no brokerage/SEBON/DP commission
     * modeling in this pass — that data isn't available yet).
     */
    private function valuedHoldings(User $user): array
    {
        $holdings = $user->portfolioHoldings()
            ->with(['stock.latestPrice', 'stock.sector'])
            ->where('quantity', '>', 0)
            ->get();

        return $holdings->map(function (PortfolioHolding $h) {
            $stock = $h->stock;
            $avgCost = (float) $h->avg_cost;

            $live = $this->liveQuote($stock->symbol);
            $localPrice = $stock->latestPrice;

            if ($live !== null) {
                $ltp = $live['close'];
                $dayChange = $live['change'];
                $changePercent = $live['change_percent'];
                $dayHigh = $live['high'];
                $dayLow = $live['low'];
                $volume = $live['volume'];
                $hasLivePrice = true;
            } elseif ($localPrice) {
                $ltp = (float) $localPrice->close;
                $dayChange = (float) $localPrice->change;
                $changePercent = (float) $localPrice->change_percent;
                $dayHigh = (float) $localPrice->high;
                $dayLow = (float) $localPrice->low;
                $volume = (int) $localPrice->volume;
                $hasLivePrice = false;
            } else {
                $ltp = $avgCost;
                $dayChange = 0.0;
                $changePercent = 0.0;
                $dayHigh = null;
                $dayLow = null;
                $volume = null;
                $hasLivePrice = false;
            }

            $investment = $h->quantity * $avgCost;
            $marketValue = $h->quantity * $ltp;

            return [
                'holding'          => $h,
                'stock'            => $stock,
                'symbol'           => $stock->symbol,
                'name'             => $stock->name,
                'sector'           => $stock->sector?->name,
                'quantity'         => $h->quantity,
                'avg_cost'         => $avgCost,
                'ltp'              => $ltp,
                'day_high'         => $dayHigh,
                'day_low'          => $dayLow,
                'volume'           => $volume,
                'change_percent'   => $changePercent,
                'investment'       => $investment,
                'market_value'     => $marketValue,
                'day_gain_loss'    => $h->quantity * $dayChange,
                'unrealized_gain'  => $marketValue - $investment,
                'has_live_price'   => $hasLivePrice,
            ];
        })->values()->all();
    }

    /**
     * Live LTP/change straight from Chukul, cached 5 min per symbol (same
     * cache key/TTL StockController uses, so a visit to the stock page
     * keeps this warm too). Falls back to the local stock_prices snapshot
     * — and ultimately avg_cost — if the live source has nothing.
     */
    private function liveQuote(string $symbol): ?array
    {
        $summary = Cache::remember("chukul_summary_{$symbol}", 300, fn() =>
            $this->scraper->fetchMarketSummary($symbol)
        );

        if (empty($summary) || !isset($summary['close'])) {
            return null;
        }

        return [
            'close'          => (float) $summary['close'],
            'change'         => (float) ($summary['point_change'] ?? 0),
            'change_percent' => (float) ($summary['percentage_change'] ?? 0),
            'high'           => (float) ($summary['high'] ?? $summary['close']),
            'low'            => (float) ($summary['low'] ?? $summary['close']),
            'volume'         => (int) ($summary['volume'] ?? 0),
        ];
    }

    public function overview(User $user): array
    {
        $rows = $this->valuedHoldings($user);

        $investment  = array_sum(array_column($rows, 'investment'));
        $marketValue = array_sum(array_column($rows, 'market_value'));
        $dayGainLoss = array_sum(array_column($rows, 'day_gain_loss'));
        $unrealized  = $marketValue - $investment;
        $realized    = (float) $user->portfolioTransactions()
            ->where('type', 'sell')
            ->sum('realized_gain');

        $sectorTotals = [];
        foreach ($rows as $r) {
            $sector = $r['sector'] ?? 'Other';
            $sectorTotals[$sector] = ($sectorTotals[$sector] ?? 0) + $r['market_value'];
        }
        arsort($sectorTotals);

        $topHoldings = $rows;
        usort($topHoldings, fn($a, $b) => $b['market_value'] <=> $a['market_value']);
        $topHoldings = array_slice($topHoldings, 0, 10);

        return [
            'holdings'      => $rows,
            'investment'    => $investment,
            'market_value'  => $marketValue,
            'net_worth'     => $marketValue,
            'day_gain_loss' => $dayGainLoss,
            'unrealized'    => $unrealized,
            'realized'      => $realized,
            'sector_totals' => $sectorTotals,
            'top_holdings'  => $topHoldings,
            'stock_count'   => count($rows),
            'total_shares'  => array_sum(array_column($rows, 'quantity')),
        ];
    }

    public function holdingsTable(User $user, array $filters = []): array
    {
        $rows = $this->valuedHoldings($user);

        if (!empty($filters['sector'])) {
            $rows = array_filter($rows, fn($r) => $r['sector'] === $filters['sector']);
        }
        if (!empty($filters['search'])) {
            $term = strtoupper($filters['search']);
            $rows = array_filter($rows, fn($r) =>
                str_contains(strtoupper($r['symbol']), $term) || str_contains(strtoupper($r['name']), $term)
            );
        }
        if (!empty($filters['movement']) && $filters['movement'] !== 'all') {
            $rows = array_filter($rows, fn($r) => $filters['movement'] === 'gaining'
                ? $r['change_percent'] > 0
                : $r['change_percent'] < 0
            );
        }
        $rows = array_values($rows);

        $investment  = array_sum(array_column($rows, 'investment'));
        $marketValue = array_sum(array_column($rows, 'market_value'));

        return [
            'rows'         => $rows,
            'investment'   => $investment,
            'market_value' => $marketValue,
            'unrealized'   => $marketValue - $investment,
            'day_gain_loss' => array_sum(array_column($rows, 'day_gain_loss')),
        ];
    }

    public function realizedHistory(User $user)
    {
        return $user->portfolioTransactions()
            ->with('stock')
            ->where('type', 'sell')
            ->orderByDesc('txn_date')
            ->paginate(25);
    }

    public function transactionHistory(User $user, array $filters = [])
    {
        $query = $user->portfolioTransactions()->with('stock');

        if (!empty($filters['symbol'])) {
            $query->whereHas('stock', fn($q) => $q->where('symbol', $filters['symbol']));
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('txn_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('txn_date', '<=', $filters['to']);
        }

        return $query->paginate(25)->withQueryString();
    }
}
