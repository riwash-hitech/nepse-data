<?php

namespace App\Http\Controllers;

use App\Services\IndicatorService;
use App\Services\NepseScraperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TopPicksController extends Controller
{
    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        // Cached for 30 min — each stock analysis hits Chukul API
        $picks = Cache::remember('top_picks_v2', 1800, function () {
            return $this->computeTopPicks();
        });

        return view('top-picks.index', compact('picks'));
    }

    public function refresh()
    {
        Cache::forget('top_picks_v2');
        return redirect()->route('top-picks.index')->with('success', 'Top picks refreshed.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Core analysis engine
    // ─────────────────────────────────────────────────────────────────────────

    private function computeTopPicks(): array
    {
        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());

        $active = collect($stockList)
            ->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false))
            ->values();

        $scored = [];

        // Analyse every active stock (limit to 200 to stay within request budget)
        $candidates = $active->take(200);

        foreach ($candidates as $stock) {
            try {
                $result = $this->analyseStock($stock['symbol'], $stock['name'], $stock['sector'] ?? 'Other');
                if ($result !== null) {
                    $scored[] = $result;
                }
            } catch (\Throwable $e) {
                Log::debug("TopPicks: skipping {$stock['symbol']} — {$e->getMessage()}");
            }
        }

        // Sort by composite score descending, keep top 5
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, 5);
    }

    private function analyseStock(string $symbol, string $name, string $sector): ?array
    {
        $prices = $this->scraper->fetchHistoricalPrices($symbol, 365);

        if (count($prices) < 50) {
            return null;
        }

        $closes  = array_column($prices, 'close');
        $highs   = array_column($prices, 'high');
        $lows    = array_column($prices, 'low');
        $volumes = array_column($prices, 'volume');

        $currentClose = (float)end($closes);
        if ($currentClose <= 0) return null;

        // ── Indicators ────────────────────────────────────────────────────────
        $rsi    = IndicatorService::rsi($closes, 14);
        $macd   = IndicatorService::macd($closes);
        $bb     = IndicatorService::bollingerBands($closes, 20);
        $atr    = IndicatorService::atr($highs, $lows, $closes, 14);
        $sma20  = IndicatorService::sma($closes, 20);
        $sma50  = IndicatorService::sma($closes, 50);
        $sma200 = IndicatorService::sma($closes, count($closes) >= 200 ? 200 : count($closes));
        $ema9   = IndicatorService::ema($closes, 9);
        $ema21  = IndicatorService::ema($closes, 21);

        // ── Scoring — uptrend criteria ────────────────────────────────────────
        $score   = 0;
        $reasons = [];

        // 1. RSI in bullish momentum zone (40–65: room to run, not overbought)
        if ($rsi !== null) {
            if ($rsi >= 40 && $rsi <= 65) {
                $score += 25;
                $reasons[] = ['icon' => '📈', 'text' => "RSI at {$this->f($rsi)} — bullish momentum zone (40–65), plenty of upside room"];
            } elseif ($rsi > 20 && $rsi < 40) {
                $score += 18;
                $reasons[] = ['icon' => '🔵', 'text' => "RSI at {$this->f($rsi)} — recovering from oversold, reversal likely"];
            } elseif ($rsi >= 65 && $rsi < 75) {
                $score += 10;
                $reasons[] = ['icon' => '⚡', 'text' => "RSI at {$this->f($rsi)} — strong momentum, trending higher"];
            }
        }

        // 2. MACD bullish cross / positive histogram
        if ($macd !== null) {
            if ($macd['macd'] > $macd['signal'] && $macd['histogram'] > 0) {
                $score += 25;
                $reasons[] = ['icon' => '🚀', 'text' => "MACD above signal line with positive histogram ({$this->f($macd['histogram'])}) — buy momentum confirmed"];
            } elseif ($macd['macd'] > $macd['signal']) {
                $score += 15;
                $reasons[] = ['icon' => '📊', 'text' => "MACD above signal line — bullish crossover in effect"];
            }
            // Fresh bullish cross: histogram just turned positive
            if ($macd['histogram'] > 0 && abs($macd['histogram']) < abs($macd['macd']) * 0.2) {
                $score += 10;
                $reasons[] = ['icon' => '🔔', 'text' => "Fresh MACD bullish crossover detected — early entry opportunity"];
            }
        }

        // 3. Price above rising SMA alignment
        if ($sma20 !== null && $sma50 !== null) {
            if ($currentClose > $sma20 && $sma20 > $sma50) {
                $score += 25;
                $reasons[] = ['icon' => '📐', 'text' => "Price ({$this->f($currentClose)}) above rising SMA20 ({$this->f($sma20)}) above SMA50 ({$this->f($sma50)}) — classic uptrend alignment"];
            } elseif ($currentClose > $sma20) {
                $score += 12;
                $reasons[] = ['icon' => '📐', 'text' => "Price above SMA20 ({$this->f($sma20)}) — short-term trend positive"];
            }
            // Golden cross potential: SMA20 approaching SMA50 from below
            if ($sma20 !== null && $sma50 !== null && $sma20 < $sma50 && ($sma50 - $sma20) / $sma50 < 0.02) {
                $score += 15;
                $reasons[] = ['icon' => '⭐', 'text' => "Golden cross imminent — SMA20 only " . $this->f(($sma50 - $sma20) / $sma50 * 100) . "% below SMA50"];
            }
        }

        // 4. Long-term bullish: price above SMA200
        if ($sma200 !== null && $currentClose > $sma200) {
            $score += 15;
            $reasons[] = ['icon' => '🏔️', 'text' => "Price above SMA200 ({$this->f($sma200)}) — long-term uptrend intact"];
        }

        // 5. EMA9 > EMA21 (short-term momentum)
        if ($ema9 !== null && $ema21 !== null && $ema9 > $ema21) {
            $score += 10;
            $reasons[] = ['icon' => '⚡', 'text' => "EMA9 ({$this->f($ema9)}) above EMA21 ({$this->f($ema21)}) — short-term momentum bullish"];
        }

        // 6. Volume surge (above 10-day average)
        if (count($volumes) >= 11) {
            $avgVol  = array_sum(array_slice($volumes, -11, 10)) / 10;
            $lastVol = (int)end($volumes);
            if ($avgVol > 0 && $lastVol > $avgVol * 1.3) {
                $surge = round($lastVol / $avgVol, 1);
                $score += 15;
                $reasons[] = ['icon' => '📦', 'text' => "Volume {$surge}× above 10-day average — institutional buying activity"];
            }
        }

        // 7. Bollinger Band squeeze (volatility contraction = breakout potential)
        if ($bb !== null && $atr !== null && $bb['middle'] > 0) {
            $bandWidth = ($bb['upper'] - $bb['lower']) / $bb['middle'] * 100;
            if ($bandWidth < 5) {
                $score += 15;
                $reasons[] = ['icon' => '🎯', 'text' => "Bollinger Band squeeze (width {$this->f($bandWidth)}%) — consolidation before breakout"];
            }
            // Near lower band = oversold, potential bounce
            if ($currentClose <= $bb['lower'] * 1.01) {
                $score += 12;
                $reasons[] = ['icon' => '🔃', 'text' => "Price at lower Bollinger Band ({$this->f($bb['lower'])}) — mean reversion bounce expected"];
            }
        }

        // 8. Price momentum: 10-day return
        if (count($closes) >= 10) {
            $close10 = $closes[count($closes) - 11];
            $ret10   = ($currentClose - $close10) / $close10 * 100;
            if ($ret10 > 3 && $ret10 < 20) {
                $score += 10;
                $reasons[] = ['icon' => '📉→📈', 'text' => "+" . $this->f($ret10) . "% gain over last 10 sessions — healthy upward momentum"];
            }
        }

        // Need minimum signal quality
        if ($score < 30 || empty($reasons)) {
            return null;
        }

        // ── Entry / Exit calculation ──────────────────────────────────────────
        $atrVal   = $atr ?? ($currentClose * 0.015);
        $entryMin = round($currentClose - $atrVal * 0.5, 2);
        $entryMax = round($currentClose + $atrVal * 0.3, 2);
        $stopLoss = round($currentClose - $atrVal * 2.0, 2);
        $target1  = round($currentClose + $atrVal * 2.5, 2);
        $target2  = round($currentClose + $atrVal * 5.0, 2);
        $target3  = round($currentClose + $atrVal * 8.0, 2);

        $riskPct    = round(abs($currentClose - $stopLoss) / $currentClose * 100, 1);
        $reward1Pct = round(abs($target1 - $currentClose) / $currentClose * 100, 1);
        $reward2Pct = round(abs($target2 - $currentClose) / $currentClose * 100, 1);
        $reward3Pct = round(abs($target3 - $currentClose) / $currentClose * 100, 1);
        $rrRatio    = $riskPct > 0 ? round($reward1Pct / $riskPct, 2) : 0;

        // Upside potential % (to target 2)
        $upsidePct = $reward2Pct;

        // Confidence: scale 0–100 based on score (max possible ~155)
        $confidence = min(98, (int)round($score / 155 * 100));

        return [
            'symbol'      => $symbol,
            'name'        => $name,
            'sector'      => $sector,
            'score'       => $score,
            'confidence'  => $confidence,
            'current'     => $currentClose,
            'entry_min'   => $entryMin,
            'entry_max'   => $entryMax,
            'stop_loss'   => $stopLoss,
            'target_1'    => $target1,
            'target_2'    => $target2,
            'target_3'    => $target3,
            'risk_pct'    => $riskPct,
            'reward1_pct' => $reward1Pct,
            'reward2_pct' => $reward2Pct,
            'reward3_pct' => $reward3Pct,
            'rr_ratio'    => $rrRatio,
            'upside_pct'  => $upsidePct,
            'reasons'     => $reasons,
            'rsi'         => $rsi,
            'macd'        => $macd,
            'atr'         => $atr,
            'sma20'       => $sma20,
            'sma50'       => $sma50,
        ];
    }

    private function f(float $v, int $dp = 2): string
    {
        return number_format($v, $dp);
    }
}
