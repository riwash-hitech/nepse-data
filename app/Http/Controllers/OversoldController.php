<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\AiSignalService;
use App\Services\BounceAnalysisService;
use App\Services\NepseScraperService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OversoldController extends Controller
{
    private const ALLOWED_LIMITS = [10, 20, 30, 50];

    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 20);
        if (!in_array($limit, self::ALLOWED_LIMITS, true)) {
            $limit = 20;
        }

        $stocks = Cache::remember("oversold_top_{$limit}", 900, fn() => $this->computeOversold($limit));

        if (Auth::check()) {
            Activity::log(Auth::user(), 'oversold_view', "Viewed Oversold Stocks (top {$limit}).");
        }

        return view('oversold.index', [
            'stocks' => $stocks,
            'limit'  => $limit,
            'limits' => self::ALLOWED_LIMITS,
        ]);
    }

    /**
     * On-demand AI take for one stock's bounce setup — kept out of the list
     * load entirely (fetched lazily via JS when the user asks for it) so
     * viewing the screener never blocks on up to 50 LLM calls at once.
     * Cached 12h per symbol since the underlying bounce history barely
     * changes day to day.
     */
    public function aiInsight(string $symbol, AiSignalService $ai)
    {
        $symbol = strtoupper($symbol);

        $insight = Cache::remember("oversold_ai_{$symbol}", 43200, function () use ($symbol, $ai) {
            $stock = Stock::where('symbol', $symbol)->first();
            if (!$stock) {
                return null;
            }

            $latestDate = Cache::get('latest_trading_date') ?? StockPrice::max('date');
            $indicator = \App\Models\Indicator::where('stock_id', $stock->id)->where('date', $latestDate)->first();
            $price = StockPrice::where('stock_id', $stock->id)->where('date', $latestDate)->first();

            if (!$indicator || !$price || $indicator->rsi_14 === null) {
                return null;
            }

            $history = $this->longHistory($symbol, $stock->id);

            $bounce = BounceAnalysisService::analyze($history, (float) $price->close);

            return $ai->bounceTake($symbol, $stock->name, (float) $price->close, (float) $indicator->rsi_14, $bounce);
        });

        return response()->json([
            'insight' => $insight ?? 'AI take unavailable for this stock right now.',
        ]);
    }

    private function computeOversold(int $limit): array
    {
        $latestDate = Cache::remember('latest_trading_date', 300, fn() =>
            StockPrice::max('date') ?? now()->toDateString()
        );

        $rows = Stock::active()
            ->select('stocks.*')
            ->join('stock_prices as sp', function ($join) use ($latestDate) {
                $join->on('sp.stock_id', '=', 'stocks.id')
                     ->where('sp.date', '=', $latestDate);
            })
            ->join('indicators as ind', function ($join) use ($latestDate) {
                $join->on('ind.stock_id', '=', 'stocks.id')
                     ->where('ind.date', '=', $latestDate)
                     ->whereNotNull('ind.rsi_14');
            })
            ->with(['sector', 'latestSignal'])
            ->addSelect([
                'sp.close as last_close',
                'sp.change_percent as last_change_percent',
                'sp.volume as last_volume',
                'ind.rsi_14 as rsi',
                'ind.sma_20 as sma_20',
                'ind.support_1 as support_1',
            ])
            ->orderBy('ind.rsi_14', 'asc')
            ->limit($limit)
            ->get();

        // Flatten to plain arrays before caching — caching the raw Eloquent
        // collection serializes model class references, which can break on
        // unserialize ("incomplete object") if the cache outlives a deploy
        // or opcache reload. Plain arrays have no such fragility.
        return $rows->map(function ($s) {
            $history = $this->longHistory($s->symbol, $s->id);

            $bounce = BounceAnalysisService::analyze($history, (float) $s->last_close);

            return [
                'symbol'              => $s->symbol,
                'name'                => $s->name,
                'sector'              => $s->sector->name ?? null,
                'rsi'                 => (float) $s->rsi,
                'last_close'          => (float) $s->last_close,
                'last_change_percent' => (float) $s->last_change_percent,
                'sma_20'              => $s->sma_20 !== null ? (float) $s->sma_20 : null,
                'signal_type'         => $s->latestSignal->signal_type ?? null,
                'bounce'              => $bounce,
            ];
        })->all();
    }

    /**
     * Closing-price history used to find past oversold episodes, merged from
     * two sources since neither alone covers enough ground: the local
     * stock_prices table only retains a ~4-month sync window ending where
     * the last scheduled sync ran, while Chukul's adjusted-history endpoint
     * (despite accepting a lookback-days param) only ever returns its own
     * trailing ~3-month window regardless of what's requested — but that
     * window extends more recently than the local sync does. Merging both
     * by date (remote wins on overlap) gives a longer effective lookback
     * than either alone. Remote fetch cached 24h per symbol.
     */
    private function longHistory(string $symbol, int $stockId): array
    {
        $remote = Cache::remember("price_history_long_{$symbol}", 86400, function () use ($symbol) {
            try {
                $rows = $this->scraper->fetchHistoricalPrices($symbol, 730);
                return array_map(fn($r) => ['date' => $r['date'], 'close' => (float) $r['close']], $rows);
            } catch (\Throwable $e) {
                Log::debug("Oversold: history fetch failed for {$symbol} — {$e->getMessage()}");
                return [];
            }
        });

        $local = StockPrice::where('stock_id', $stockId)
            ->orderBy('date')
            ->get(['date', 'close'])
            ->map(fn($r) => ['date' => $r->date->format('Y-m-d'), 'close' => (float) $r->close])
            ->all();

        $byDate = [];
        foreach (array_merge($local, $remote) as $row) {
            $byDate[$row['date']] = $row['close'];
        }
        ksort($byDate);

        return array_map(fn($date, $close) => ['date' => $date, 'close' => $close], array_keys($byDate), array_values($byDate));
    }
}
