<?php

namespace App\Http\Controllers;

use App\Services\AiSignalService;
use App\Services\IndicatorService;
use App\Services\NepseScraperService;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Top 10 Picker — distinct from TopPicksController (which is purely
 * rule-based scoring). The shortlist here is built entirely from LIVE
 * Chukul data (current price + freshly computed RSI/MACD/SMA from live
 * historical prices, same source TopPicksController/StockController use),
 * not the local signals/indicators tables, which can lag the sync schedule
 * by days or weeks. A single consolidated AI call (with live Google Search
 * grounding where the provider supports it) then re-ranks/curates the real
 * top 10 with its own independent, web-informed reasoning.
 */
class AiPickerController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        $picks = Cache::remember('ai_top_picks_v2', 3600, fn() => $this->computePicks());

        if (Auth::check()) {
            Activity::log(Auth::user(), 'ai_picks_view', 'Viewed AI Top Picks.');
        }

        return view('ai-picks.index', compact('picks'));
    }

    public function refresh()
    {
        Cache::forget('ai_top_picks_v2');
        return redirect()->route('ai-picks.index')->with('success', 'AI picks refreshed.');
    }

    private function computePicks(): array
    {
        $candidates = $this->liveCandidates();

        if (empty($candidates)) {
            return [];
        }

        $aiPicks = app(AiSignalService::class)->topPicks($candidates);

        $bySymbol = collect($candidates)->keyBy('symbol');

        if ($aiPicks === null) {
            // AI unavailable — fall back to the top-confidence rule-based
            // shortlist as-is (still live data, just not AI-reasoned).
            return collect($candidates)->take(10)->map(fn($c) => $c + [
                'ai_generated' => false,
                'reason'       => null,
                'target_price' => null,
            ])->values()->all();
        }

        return collect($aiPicks)->map(function ($p) use ($bySymbol) {
            $base = $bySymbol->get($p['symbol'], []);

            return array_merge($base, array_filter($p, fn($v) => $v !== null), [
                'ai_generated' => true,
            ]);
        })->values()->all();
    }

    /**
     * Scans active stocks using live Chukul data only: current quote +
     * freshly fetched historical closes to compute RSI/MACD/SMA on the spot.
     * Scored with a simple bullish-technical rubric (same signal families
     * SignalEngine/TopPicksController use) — just enough to build a sane,
     * live shortlist for the AI to then apply its own judgement to. Capped
     * at 100 scanned / 30 kept so this stays within a reasonable request
     * budget (one HTTP call per scanned symbol, same pattern TopPicksController
     * already uses at up to 200).
     */
    private function liveCandidates(): array
    {
        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());

        $active = collect($stockList)
            ->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false))
            ->take(100);

        $scored = [];
        foreach ($active as $s) {
            try {
                $c = $this->scoreLive($s['symbol'], $s['name'] ?? $s['symbol'], $s['sector'] ?? 'Other');
                if ($c !== null) {
                    $scored[] = $c;
                }
            } catch (\Throwable $e) {
                Log::debug("AiPicker: skipping {$s['symbol']} — {$e->getMessage()}");
            }
        }

        usort($scored, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($scored, 0, 30);
    }

    private function scoreLive(string $symbol, string $name, string $sector): ?array
    {
        $prices = $this->scraper->fetchHistoricalPrices($symbol, 400);
        if (count($prices) < 30) {
            return null;
        }

        $closes = array_column($prices, 'close');
        $price = (float) end($closes);
        if ($price <= 0) {
            return null;
        }

        $rsi = IndicatorService::rsi($closes, 14);
        $macd = IndicatorService::macd($closes);
        $sma20 = IndicatorService::sma($closes, 20);
        $sma50 = IndicatorService::sma($closes, min(50, count($closes)));

        $score = 0;
        $reasons = [];

        if ($rsi !== null) {
            if ($rsi >= 40 && $rsi <= 65) {
                $score += 25;
                $reasons[] = "RSI {$rsi} — bullish momentum zone";
            } elseif ($rsi > 20 && $rsi < 40) {
                $score += 15;
                $reasons[] = "RSI {$rsi} — recovering from oversold";
            }
        }

        if ($macd !== null && $macd['macd'] > $macd['signal'] && $macd['histogram'] > 0) {
            $score += 25;
            $reasons[] = 'MACD bullish crossover with positive histogram';
        }

        if ($sma20 !== null && $sma50 !== null && $price > $sma20 && $sma20 > $sma50) {
            $score += 25;
            $reasons[] = 'Price above rising SMA20/SMA50 — uptrend alignment';
        } elseif ($sma20 !== null && $price > $sma20) {
            $score += 12;
            $reasons[] = 'Price above SMA20';
        }

        if (count($closes) >= 10) {
            $ret10 = ($price - $closes[count($closes) - 11]) / $closes[count($closes) - 11] * 100;
            if ($ret10 > 2 && $ret10 < 20) {
                $score += 10;
                $reasons[] = '+' . round($ret10, 1) . '% over the last 10 sessions';
            }
        }

        if ($score < 20) {
            return null;
        }

        return [
            'symbol'        => $symbol,
            'name'          => $name,
            'sector'        => $sector,
            'price'         => $price,
            'price_is_live' => true,
            'rsi'           => $rsi !== null ? round($rsi, 1) : null,
            'macd_hist'     => $macd !== null ? round($macd['histogram'], 2) : null,
            'confidence'    => min(95, $score),
            'reasons'       => $reasons,
            'entry_min'     => null,
            'entry_max'     => null,
            'target_1'      => null,
            'stop_loss'     => null,
            'signal_date'   => now()->toDateString(),
        ];
    }
}
