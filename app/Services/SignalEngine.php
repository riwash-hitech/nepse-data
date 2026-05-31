<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPrice;
use App\Models\Indicator;
use App\Models\Signal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SignalEngine
{
    public function __construct(
        private readonly IndicatorService $indicators
    ) {}

    /**
     * Run full analysis and generate signal for a stock.
     * Stores both indicator and signal rows.
     */
    public function analyze(Stock $stock, \Carbon\Carbon $date = null): ?Signal
    {
        $date = $date ?? now()->toDateString();

        // Fetch enough historical prices (200 candles for SMA200)
        $prices = StockPrice::where('stock_id', $stock->id)
            ->where('date', '<=', $date)
            ->orderBy('date')
            ->take(250)
            ->get();

        if ($prices->count() < 30) {
            return null;
        }

        $closes  = $prices->pluck('close')->map(fn($v) => (float)$v)->toArray();
        $highs   = $prices->pluck('high')->map(fn($v) => (float)$v)->toArray();
        $lows    = $prices->pluck('low')->map(fn($v) => (float)$v)->toArray();
        $volumes = $prices->pluck('volume')->map(fn($v) => (int)$v)->toArray();

        $lastPrice = $prices->last();
        $currentClose = (float)$lastPrice->close;

        // Compute indicators
        $sma20  = IndicatorService::sma($closes, 20);
        $sma50  = IndicatorService::sma($closes, 50);
        $sma200 = IndicatorService::sma($closes, 200);
        $ema12  = IndicatorService::ema($closes, 12);
        $ema26  = IndicatorService::ema($closes, 26);
        $ema50  = IndicatorService::ema($closes, 50);
        $rsi    = IndicatorService::rsi($closes, 14);
        $macd   = IndicatorService::macd($closes);
        $bb     = IndicatorService::bollingerBands($closes);
        $atr    = IndicatorService::atr($highs, $lows, $closes, 14);
        $pivots = IndicatorService::pivotPoints((float)$lastPrice->high, (float)$lastPrice->low, $currentClose);
        $swing  = IndicatorService::swingSupportResistance($highs, $lows, 30);

        $support1    = $swing['support_1'] ?? $pivots['support_1'];
        $support2    = $swing['support_2'] ?? $pivots['support_2'];
        $resistance1 = $swing['resistance_1'] ?? $pivots['resistance_1'];
        $resistance2 = $swing['resistance_2'] ?? $pivots['resistance_2'];

        // Persist indicator row
        Indicator::updateOrCreate(
            ['stock_id' => $stock->id, 'date' => $date],
            [
                'sma_20' => $sma20, 'sma_50' => $sma50, 'sma_200' => $sma200,
                'ema_12' => $ema12, 'ema_26' => $ema26, 'ema_50' => $ema50,
                'rsi_14' => $rsi,
                'macd' => $macd['macd'] ?? null,
                'macd_signal' => $macd['signal'] ?? null,
                'macd_histogram' => $macd['histogram'] ?? null,
                'bb_upper' => $bb['upper'] ?? null,
                'bb_middle' => $bb['middle'] ?? null,
                'bb_lower' => $bb['lower'] ?? null,
                'atr_14' => $atr,
                'support_1' => $support1, 'support_2' => $support2,
                'resistance_1' => $resistance1, 'resistance_2' => $resistance2,
                'pivot' => $pivots['pivot'],
            ]
        );

        // Build signal
        [$signalType, $confidence, $reasons] = $this->scoreSignal(
            close: $currentClose,
            rsi: $rsi,
            macd: $macd,
            sma20: $sma20,
            sma50: $sma50,
            support1: $support1,
            resistance1: $resistance1,
            volumes: $volumes,
            bb: $bb
        );

        // Entry / Exit calculation
        $entryExit = $this->calculateEntryExit(
            close: $currentClose,
            support1: $support1,
            resistance1: $resistance1,
            atr: $atr,
            signalType: $signalType
        );

        $signal = Signal::updateOrCreate(
            ['stock_id' => $stock->id, 'date' => $date],
            array_merge([
                'signal_type'      => $signalType,
                'confidence'       => $confidence,
                'reasons'          => $reasons,
                'price_at_signal'  => $currentClose,
                'rsi_value'        => $rsi,
                'macd_value'       => $macd['macd'] ?? null,
                'volume_at_signal' => (int)$lastPrice->volume,
                'is_active'        => true,
                'expires_at'       => now()->addDays(3),
            ], $entryExit)
        );

        return $signal;
    }

    /**
     * Rule-based scoring — returns [signalType, confidence(0-100), reasons[]]
     */
    private function scoreSignal(
        float $close,
        ?float $rsi,
        ?array $macd,
        ?float $sma20,
        ?float $sma50,
        ?float $sma200,
        ?float $support1,
        ?float $resistance1,
        array $volumes,
        ?array $bb
    ): array {
        $buyScore  = 0;
        $sellScore = 0;
        $reasons   = [];

        // ── RSI — trend-following, not contrarian ─────────────
        if ($rsi !== null) {
            if ($rsi < 30) {
                $buyScore += 30;
                $reasons[] = "RSI deeply oversold ({$this->fmt($rsi)}) — strong buy zone";
            } elseif ($rsi < 45) {
                $buyScore += 15;
                $reasons[] = "RSI below 45, approaching oversold ({$this->fmt($rsi)})";
            } elseif ($rsi >= 50 && $rsi <= 65) {
                $buyScore += 10;
                $reasons[] = "RSI in bullish momentum zone ({$this->fmt($rsi)})";
            } elseif ($rsi > 80) {
                $sellScore += 30;
                $reasons[] = "RSI extreme overbought ({$this->fmt($rsi)})";
            } elseif ($rsi > 70) {
                $sellScore += 15;
                $reasons[] = "RSI overbought ({$this->fmt($rsi)})";
            }
        }

        // ── MACD — position AND histogram direction ───────────
        if ($macd !== null) {
            if ($macd['macd'] > $macd['signal']) {
                $buyScore += 15;
                $reasons[] = "MACD above signal line (bullish)";
                if ($macd['histogram'] > 0) {
                    $buyScore += 10;
                    $reasons[] = "Positive MACD histogram — momentum building";
                }
            } elseif ($macd['macd'] < $macd['signal']) {
                $sellScore += 15;
                $reasons[] = "MACD below signal line (bearish)";
                if ($macd['histogram'] < 0) {
                    $sellScore += 10;
                    $reasons[] = "Negative MACD histogram — bearish momentum";
                }
            }
        }

        // ── MA alignment — trend following ────────────────────
        if ($sma20 !== null && $sma50 !== null) {
            if ($close > $sma20 && $sma20 > $sma50) {
                $buyScore += 20;
                $reasons[] = "Price above rising SMA20 & SMA50 — uptrend confirmed";
            } elseif ($close < $sma20 && $sma20 < $sma50) {
                $sellScore += 20;
                $reasons[] = "Price below falling SMA20 & SMA50 — downtrend";
            } elseif ($close > $sma20) {
                $buyScore += 8;
                $reasons[] = "Price above SMA20";
            } elseif ($close < $sma20) {
                $sellScore += 8;
                $reasons[] = "Price below SMA20";
            }
        }

        // ── SMA200 — long-term bias ───────────────────────────
        if ($sma200 !== null) {
            if ($close > $sma200) {
                $buyScore += 10;
                $reasons[] = "Price above SMA200 (long-term uptrend)";
            } else {
                $sellScore += 10;
                $reasons[] = "Price below SMA200 (long-term downtrend)";
            }
        }

        // ── Price vs Support/Resistance ───────────────────────
        if ($support1 !== null && $support1 > 0) {
            $nearSupport = abs($close - $support1) / $support1 < 0.025;
            if ($nearSupport && $close >= $support1) {
                $buyScore += 15;
                $reasons[] = "Price bouncing at support NPR {$this->fmt($support1)}";
            }
        }
        if ($resistance1 !== null && $resistance1 > 0) {
            $atResistance = abs($close - $resistance1) / $resistance1 < 0.025;
            if ($atResistance) {
                $sellScore += 15;
                $reasons[] = "Price at resistance NPR {$this->fmt($resistance1)}";
            }
            // Breakout above resistance = strongly bullish
            if ($close > $resistance1 * 1.02) {
                $buyScore += 15;
                $reasons[] = "Breakout above resistance NPR {$this->fmt($resistance1)}";
            }
        }

        // ── Volume confirmation ───────────────────────────────
        if (count($volumes) >= 10) {
            $avgVol  = array_sum(array_slice($volumes, -10, 9)) / 9;
            $lastVol = end($volumes);
            if ($avgVol > 0 && $lastVol > $avgVol * 1.5) {
                $reasons[] = "High volume confirmation (1.5× avg)";
                if ($buyScore > $sellScore) {
                    $buyScore += 10;
                } else {
                    $sellScore += 10;
                }
            }
        }

        // ── Bollinger Bands ───────────────────────────────────
        if ($bb !== null) {
            if ($close <= $bb['lower']) {
                $buyScore += 15;
                $reasons[] = "Price at/below lower Bollinger Band — oversold";
            } elseif ($close >= $bb['upper']) {
                $sellScore += 10;
                $reasons[] = "Price at/above upper Bollinger Band";
            } elseif ($bb['middle'] !== null && $close > $bb['middle']) {
                $buyScore += 5;
            }
        }

        // ── Determine final signal ────────────────────────────
        $total = $buyScore + $sellScore;
        if ($buyScore > $sellScore && $buyScore >= 30) {
            $confidence = (int)min(95, round(($buyScore / max($total, 50)) * 100));
            return ['BUY', $confidence, $reasons];
        } elseif ($sellScore > $buyScore && $sellScore >= 30) {
            $confidence = (int)min(95, round(($sellScore / max($total, 50)) * 100));
            return ['SELL', $confidence, $reasons];
        }

        $holdConfidence = (int)max(35, min(60, 50 - abs($buyScore - $sellScore)));
        $reasons[] = 'Mixed signals — no strong directional bias';
        return ['HOLD', $holdConfidence, $reasons];
    }

    /**
     * Generate entry/exit levels using ATR and S/R
     */
    private function calculateEntryExit(
        float $close,
        ?float $support1,
        ?float $resistance1,
        ?float $atr,
        string $signalType
    ): array {
        $atr = $atr ?? ($close * 0.015); // fallback: 1.5% ATR estimate

        if ($signalType === 'BUY') {
            $entryMin  = $support1 ? max($close - $atr, $support1) : $close - $atr;
            $entryMax  = $close + ($atr * 0.5);
            $stopLoss  = $entryMin - ($atr * 1.5);
            $target1   = $close + ($atr * 2);
            $target2   = $close + ($atr * 4);
        } else {
            $entryMin  = $close - ($atr * 0.5);
            $entryMax  = $resistance1 ? min($close + $atr, $resistance1) : $close + $atr;
            $stopLoss  = $entryMax + ($atr * 1.5);
            $target1   = $close - ($atr * 2);
            $target2   = $close - ($atr * 4);
        }

        $risk   = abs($entryMax - $stopLoss);
        $reward = abs($target1 - $entryMax);
        $rr     = $risk > 0 ? round($reward / $risk, 2) : 0;

        return [
            'entry_min'   => round($entryMin, 2),
            'entry_max'   => round($entryMax, 2),
            'stop_loss'   => round($stopLoss, 2),
            'target_1'    => round($target1, 2),
            'target_2'    => round($target2, 2),
            'risk_reward' => $rr,
        ];
    }

    private function fmt(float $v): string
    {
        return number_format($v, 2);
    }

    /**
     * Multi-timeframe trend analysis.
     * Returns rich per-timeframe data: direction, signal, strength, quality, description.
     */
    public function multiTimeframeTrend(array $closes): array
    {
        $n    = count($closes);
        $last = (float)end($closes);

        $ema5   = IndicatorService::ema($closes, 5);
        $ema10  = IndicatorService::ema($closes, 10);
        $sma20  = IndicatorService::sma($closes, 20);
        $sma50  = IndicatorService::sma($closes, 50);
        $sma200 = IndicatorService::sma($closes, 200);

        // Helper: analyse one timeframe pair
        $analyse = function (
            ?float $fast,
            ?float $slow,
            string $fastLabel,
            string $slowLabel,
            float  $strongThresh = 1.5,
            float  $weakThresh   = 0.3
        ): array {
            if ($fast === null || $slow === null || $slow == 0) {
                return ['direction'=>'N/A','signal'=>'HOLD','strength'=>'N/A','quality'=>'N/A',
                        'description'=>'Insufficient data for this timeframe.','pct_diff'=>0];
            }
            $pct = ($fast - $slow) / $slow * 100;
            if ($pct > $strongThresh) {
                return ['direction'=>'Uptrend','signal'=>'BUY','strength'=>'Strong','quality'=>'Good Uptrend',
                    'description'=>"{$fastLabel} ({$this->fmt($fast)}) is {$this->fmt2($pct)}% above {$slowLabel} ({$this->fmt($slow)}). Strong bullish momentum.",
                    'pct_diff'=>round($pct,2)];
            } elseif ($pct > $weakThresh) {
                return ['direction'=>'Uptrend','signal'=>'BUY','strength'=>'Moderate','quality'=>'Mild Uptrend',
                    'description'=>"{$fastLabel} ({$this->fmt($fast)}) is {$this->fmt2($pct)}% above {$slowLabel} ({$this->fmt($slow)}). Modest upward bias — watch for confirmation.",
                    'pct_diff'=>round($pct,2)];
            } elseif ($pct < -$strongThresh) {
                return ['direction'=>'Downtrend','signal'=>'SELL','strength'=>'Strong','quality'=>'Declining',
                    'description'=>"{$fastLabel} ({$this->fmt($fast)}) is {$this->fmt2($pct)}% below {$slowLabel} ({$this->fmt($slow)}). Strong bearish pressure — avoid buying.",
                    'pct_diff'=>round($pct,2)];
            } elseif ($pct < -$weakThresh) {
                return ['direction'=>'Downtrend','signal'=>'SELL','strength'=>'Moderate','quality'=>'Mild Downtrend',
                    'description'=>"{$fastLabel} ({$this->fmt($fast)}) is {$this->fmt2($pct)}% below {$slowLabel} ({$this->fmt($slow)}). Mild bearish bias — caution advised.",
                    'pct_diff'=>round($pct,2)];
            } else {
                return ['direction'=>'Sideways','signal'=>'HOLD','strength'=>'Weak','quality'=>'Consolidating',
                    'description'=>"{$fastLabel} ({$this->fmt($fast)}) and {$slowLabel} ({$this->fmt($slow)}) are very close ({$this->fmt2(abs($pct))}% gap). Market is consolidating — no clear direction.",
                    'pct_diff'=>round($pct,2)];
            }
        };

        // ── Very Short Term: EMA5 vs EMA10 ──
        $vs = $analyse($ema5, $ema10, 'EMA5', 'EMA10', 1.0, 0.25);
        if ($vs['direction'] === 'Uptrend') {
            $vs['quality']      = abs($vs['pct_diff']) > 1.5 ? 'Strong Momentum' : 'Short-term Rally';
            $vs['description'] .= ' ' . (abs($vs['pct_diff']) > 1.5 ? 'Strong momentum move across recent days.' : 'This may be just a few days of strength — watch closely.');
        } elseif ($vs['direction'] === 'Downtrend') {
            $vs['quality']      = abs($vs['pct_diff']) > 1.5 ? 'Sharp Sell-off' : 'Short-term Weakness';
            $vs['description'] .= ' Recent sessions show selling pressure.';
        }

        // ── Short Term: Price vs SMA20 ──
        $st = $analyse($last, $sma20, 'Price', 'SMA20', 1.5, 0.4);
        if ($st['direction'] === 'Uptrend') {
            $daysAbove = 0;
            for ($i = $n - 1; $i >= max(0, $n - 15); $i--) {
                if ($sma20 && (float)$closes[$i] > $sma20) $daysAbove++;
                else break;
            }
            $st['quality']     = $daysAbove >= 8 ? 'Sustained Uptrend' : ($daysAbove >= 4 ? 'Building Momentum' : 'Just Crossed Above');
            $st['description'] .= " Price has stayed above SMA20 for ~{$daysAbove} sessions — " .
                ($daysAbove >= 8 ? 'a sustained uptrend, not just a few days.' : ($daysAbove >= 4 ? 'building momentum.' : 'recent crossover, monitor closely.'));
        }

        // ── Mid Term: SMA20 vs SMA50 ──
        $mt = $analyse($sma20, $sma50, 'SMA20', 'SMA50', 1.0, 0.3);
        if ($mt['direction'] === 'Uptrend') {
            $mt['quality']     = abs($mt['pct_diff']) > 2 ? 'Good Uptrend' : 'Early Uptrend';
            $mt['description'] .= abs($mt['pct_diff']) > 2
                ? ' Golden cross structure — mid-term trend firmly bullish.'
                : ' Early-stage uptrend; more confirmation needed.';
        } elseif ($mt['direction'] === 'Downtrend') {
            $mt['quality']     = abs($mt['pct_diff']) > 2 ? 'Established Downtrend' : 'Early Downtrend';
            $mt['description'] .= abs($mt['pct_diff']) > 2
                ? ' Death cross structure — mid-term trend firmly bearish.'
                : ' Early-stage downtrend forming.';
        }

        // ── Long Term: Price vs SMA200 ──
        if ($sma200 === null) {
            $lt = ['direction'=>'N/A','signal'=>'HOLD','strength'=>'N/A','quality'=>'N/A',
                   'description'=>'Need 200+ sessions of data for long-term trend.','pct_diff'=>0];
        } else {
            $pct200 = ($last - $sma200) / $sma200 * 100;
            if ($pct200 > 5) {
                $lt = ['direction'=>'Uptrend','signal'=>'BUY','strength'=>'Strong','quality'=>'Good Long-term Uptrend',
                    'description'=>"Price (NPR {$this->fmt($last)}) is {$this->fmt2($pct200)}% above SMA200 (NPR {$this->fmt($sma200)}). Solid long-term bullish structure — stock is well above yearly average.",'pct_diff'=>round($pct200,2)];
            } elseif ($pct200 > 1) {
                $lt = ['direction'=>'Uptrend','signal'=>'BUY','strength'=>'Moderate','quality'=>'Mild Long-term Uptrend',
                    'description'=>"Price is {$this->fmt2($pct200)}% above SMA200. Marginally bullish long-term — early recovery or mild uptrend forming.",'pct_diff'=>round($pct200,2)];
            } elseif ($pct200 < -5) {
                $lt = ['direction'=>'Downtrend','signal'=>'SELL','strength'=>'Strong','quality'=>'Long-term Downtrend',
                    'description'=>"Price is {$this->fmt2(abs($pct200))}% below SMA200 (NPR {$this->fmt($sma200)}). Long-term trend is bearish — high risk for new long positions.",'pct_diff'=>round($pct200,2)];
            } elseif ($pct200 < -1) {
                $lt = ['direction'=>'Downtrend','signal'=>'SELL','strength'=>'Moderate','quality'=>'Mild Long-term Downtrend',
                    'description'=>"Price is {$this->fmt2(abs($pct200))}% below SMA200. Mildly bearish long-term — caution warranted.",'pct_diff'=>round($pct200,2)];
            } else {
                $lt = ['direction'=>'Sideways','signal'=>'HOLD','strength'=>'Weak','quality'=>'Long-term Consolidation',
                    'description'=>"Price is nearly flat vs SMA200 (gap: {$this->fmt2(abs($pct200))}%). Long-term trend is neutral — stock consolidating around its yearly average.",'pct_diff'=>round($pct200,2)];
            }
        }

        // ── Overall consensus ──
        $signals   = [$vs['signal'], $st['signal'], $mt['signal'], $lt['signal']];
        $buyCount  = count(array_filter($signals, fn($s) => $s === 'BUY'));
        $sellCount = count(array_filter($signals, fn($s) => $s === 'SELL'));
        $consensus = $buyCount >= 3 ? 'BUY' : ($sellCount >= 3 ? 'SELL' : ($buyCount > $sellCount ? 'MILD BUY' : ($sellCount > $buyCount ? 'MILD SELL' : 'NEUTRAL')));
        $overallDesc = match($consensus) {
            'BUY'       => 'All/most timeframes align bullishly. Strong buy setup with broad trend confirmation.',
            'SELL'      => 'All/most timeframes are bearish. Avoid long positions; consider reducing exposure.',
            'MILD BUY'  => 'More timeframes lean bullish. Consider buying on pullbacks with a clear stop-loss.',
            'MILD SELL' => 'More timeframes lean bearish. Wait for trend reversal signals before entering.',
            default     => 'Timeframes are mixed. Market is indecisive — wait for clearer direction.',
        };

        return [
            'very_short' => $vs,
            'short'      => $st,
            'mid'        => $mt,
            'long'       => $lt,
            'consensus'  => ['signal'=>$consensus,'buy_count'=>$buyCount,'sell_count'=>$sellCount,'description'=>$overallDesc],
        ];
    }

    private function fmt2(float $v): string { return number_format(abs($v), 2); }

    // ────────────────────────────────────────────────────────────────────
    //  Live analytics — no DB reads or writes.
    //  Pass raw price rows from Chukul (oldest→newest ascending order).
    //  Returns ['indicator' => [...], 'signal' => [...]] as plain arrays.
    // ────────────────────────────────────────────────────────────────────

    public function analyzeFromData(array $priceRows): array
    {
        if (count($priceRows) < 30) {
            return ['indicator' => null, 'signal' => null];
        }

        $closes  = array_column($priceRows, 'close');
        $highs   = array_column($priceRows, 'high');
        $lows    = array_column($priceRows, 'low');
        $volumes = array_column($priceRows, 'volume');

        $last         = end($priceRows);
        $currentClose = (float)$last['close'];

        // Indicators
        $sma20  = IndicatorService::sma($closes, 20);
        $sma50  = IndicatorService::sma($closes, 50);
        $sma200 = IndicatorService::sma($closes, 200);
        $ema12  = IndicatorService::ema($closes, 12);
        $ema26  = IndicatorService::ema($closes, 26);
        $ema50  = IndicatorService::ema($closes, 50);
        $rsi    = IndicatorService::rsi($closes, 14);
        $macd   = IndicatorService::macd($closes);
        $bb     = IndicatorService::bollingerBands($closes);
        $atr    = IndicatorService::atr($highs, $lows, $closes, 14);
        $pivots = IndicatorService::pivotPoints((float)$last['high'], (float)$last['low'], $currentClose);
        $swing  = IndicatorService::swingSupportResistance($highs, $lows, 30);

        $support1    = $swing['support_1']    ?? $pivots['support_1'];
        $support2    = $swing['support_2']    ?? $pivots['support_2'];
        $resistance1 = $swing['resistance_1'] ?? $pivots['resistance_1'];
        $resistance2 = $swing['resistance_2'] ?? $pivots['resistance_2'];

        // Score
        [$signalType, $confidence, $reasons] = $this->scoreSignal(
            close: $currentClose, rsi: $rsi, macd: $macd,
            sma20: $sma20, sma50: $sma50, sma200: $sma200,
            support1: $support1, resistance1: $resistance1,
            volumes: $volumes, bb: $bb
        );

        $entryExit = $this->calculateEntryExit(
            close: $currentClose,
            support1: $support1, resistance1: $resistance1,
            atr: $atr, signalType: $signalType
        );

        $trend = $this->multiTimeframeTrend($closes);

        return [
            'indicator' => [
                'sma_20'          => $sma20,
                'sma_50'          => $sma50,
                'sma_200'         => $sma200,
                'ema_12'          => $ema12,
                'ema_26'          => $ema26,
                'ema_50'          => $ema50,
                'rsi_14'          => $rsi,
                'macd'            => $macd['macd']      ?? null,
                'macd_signal'     => $macd['signal']    ?? null,
                'macd_histogram'  => $macd['histogram'] ?? null,
                'bb_upper'        => $bb['upper']       ?? null,
                'bb_middle'       => $bb['middle']      ?? null,
                'bb_lower'        => $bb['lower']       ?? null,
                'atr_14'          => $atr,
                'support_1'       => $support1,
                'support_2'       => $support2,
                'resistance_1'    => $resistance1,
                'resistance_2'    => $resistance2,
                'pivot'           => $pivots['pivot'],
            ],
            'signal' => array_merge([
                'signal_type'      => $signalType,
                'confidence'       => $confidence,
                'reasons'          => $reasons,
                'price_at_signal'  => $currentClose,
                'rsi_value'        => $rsi,
                'macd_value'       => $macd['macd'] ?? null,
                'volume_at_signal' => (int)$last['volume'],
                'signal_color'     => match($signalType) {
                    'BUY'  => 'green',
                    'SELL' => 'red',
                    default => 'yellow',
                },
            ], $entryExit),
            'trend' => $trend,
        ];
    }
}

