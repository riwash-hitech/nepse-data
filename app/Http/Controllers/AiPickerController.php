<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\Signal;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\AiSignalService;
use App\Services\NepseScraperService;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * AI Top 10 Picker — distinct from TopPicksController (which is purely
 * rule-based scoring). Here, SignalEngine's existing BUY signals form a
 * shortlist, and a single consolidated AI call re-ranks/curates the real
 * top 10 with its own independent reasoning, rather than just echoing the
 * rule-based confidence order.
 */
class AiPickerController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        $picks = Cache::remember('ai_top_picks_v1', 3600, fn() => $this->computePicks());

        if (Auth::check()) {
            Activity::log(Auth::user(), 'ai_picks_view', 'Viewed AI Top Picks.');
        }

        return view('ai-picks.index', compact('picks'));
    }

    public function refresh()
    {
        Cache::forget('ai_top_picks_v1');
        return redirect()->route('ai-picks.index')->with('success', 'AI picks refreshed.');
    }

    private function computePicks(): array
    {
        $latestDate = Cache::remember('latest_trading_date', 300, fn() =>
            StockPrice::max('date') ?? now()->toDateString()
        );

        // Shortlist: active BUY signals as of the latest trading date,
        // strongest rule-based confidence first, capped at 30 so the AI
        // prompt stays a reasonable size.
        $rows = Stock::active()
            ->select('stocks.*')
            ->join('signals as sig', function ($join) use ($latestDate) {
                $join->on('sig.stock_id', '=', 'stocks.id')
                     ->where('sig.date', '=', $latestDate)
                     ->where('sig.signal_type', '=', 'BUY')
                     ->where('sig.is_active', '=', 1);
            })
            ->join('stock_prices as sp', function ($join) use ($latestDate) {
                $join->on('sp.stock_id', '=', 'stocks.id')
                     ->where('sp.date', '=', $latestDate);
            })
            ->leftJoin('indicators as ind', function ($join) use ($latestDate) {
                $join->on('ind.stock_id', '=', 'stocks.id')
                     ->where('ind.date', '=', $latestDate);
            })
            ->with('sector')
            ->addSelect([
                'sig.confidence as signal_confidence',
                'sig.reasons as signal_reasons',
                'sig.entry_min as sig_entry_min',
                'sig.entry_max as sig_entry_max',
                'sig.target_1 as sig_target_1',
                'sig.stop_loss as sig_stop_loss',
                'sp.close as price',
                'ind.rsi_14 as rsi',
                'ind.macd_histogram as macd_hist',
            ])
            ->orderByDesc('sig.confidence')
            ->limit(30)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // The signal/stock_prices sync can lag days to weeks behind — the
        // stale 'price' column above is only a fallback. For every candidate
        // we overwrite it with today's actual live quote (same source/cache
        // StockController and PortfolioService use), so the AI reasons about
        // what the stock costs right now, not what it cost when the signal
        // was last computed.
        $candidates = $rows->map(function ($s) use ($latestDate) {
            $live = $this->liveQuote($s->symbol);

            return [
                'symbol'       => $s->symbol,
                'name'         => $s->name,
                'sector'       => $s->sector->name ?? 'Other',
                'price'        => $live ?? (float) $s->price,
                'price_is_live' => $live !== null,
                'rsi'          => $s->rsi !== null ? round((float) $s->rsi, 1) : null,
                'macd_hist'    => $s->macd_hist !== null ? round((float) $s->macd_hist, 2) : null,
                'confidence'   => (int) $s->signal_confidence,
                'reasons'      => is_array($s->signal_reasons) ? $s->signal_reasons : [],
                'entry_min'    => $s->sig_entry_min !== null ? (float) $s->sig_entry_min : null,
                'entry_max'    => $s->sig_entry_max !== null ? (float) $s->sig_entry_max : null,
                'target_1'     => $s->sig_target_1 !== null ? (float) $s->sig_target_1 : null,
                'stop_loss'    => $s->sig_stop_loss !== null ? (float) $s->sig_stop_loss : null,
                'signal_date'  => $latestDate,
            ];
        })->all();

        $aiPicks = app(AiSignalService::class)->topPicks($candidates);

        $bySymbol = collect($candidates)->keyBy('symbol');

        if ($aiPicks === null) {
            // AI unavailable — fall back to the top-confidence rule-based
            // shortlist as-is, flagged so the view can say so. The stored
            // target_1/stop_loss were computed against the stale signal-date
            // price, not the live price above — only reuse them if the stock
            // hasn't moved enough since then to make them misleading.
            return collect($candidates)->take(10)->map(function ($c) {
                $staleTarget = $c['target_1'];
                $priceMoved = $c['entry_max'] !== null && $c['price'] > 0
                    && abs($c['price'] - $c['entry_max']) / $c['price'] > 0.15;

                return $c + [
                    'ai_generated' => false,
                    'reason'       => null,
                    'target_price' => $priceMoved ? null : $staleTarget,
                ];
            })->values()->all();
        }

        return collect($aiPicks)->map(function ($p) use ($bySymbol) {
            $base = $bySymbol->get($p['symbol'], []);

            return array_merge($base, array_filter($p, fn($v) => $v !== null), [
                'ai_generated' => true,
            ]);
        })->values()->all();
    }

    /**
     * Live LTP straight from Chukul, cached 5 min per symbol — same cache
     * key/TTL PortfolioService and StockController use, so visiting any of
     * those pages keeps this warm too. Returns null (never throws) if the
     * live source has nothing, in which case the caller falls back to the
     * stale local stock_prices snapshot.
     */
    private function liveQuote(string $symbol): ?float
    {
        $summary = Cache::remember("chukul_summary_{$symbol}", 300, fn() =>
            $this->scraper->fetchMarketSummary($symbol)
        );

        return (!empty($summary) && isset($summary['close'])) ? (float) $summary['close'] : null;
    }
}
