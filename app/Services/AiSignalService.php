<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * AiSignalService — a second opinion on top of SignalEngine's rule-based
 * BUY/SELL/HOLD score, using an LLM to read the same computed indicators
 * the way an analyst would instead of just summing point weights.
 *
 * Supports both Gemini and Claude — the active provider is chosen via
 * AI_PROVIDER in .env (services.ai_provider), so switching is a config
 * change, not a code change. Never throws — every failure mode (missing
 * key, timeout, bad JSON) is caught, logged, and returns null so the stock
 * page always renders with or without an AI take.
 */
class AiSignalService
{
    private const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const ANTHROPIC_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_API_VERSION = '2023-06-01';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'         => 20,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * @param object     $stock     Plain object with symbol/name/sector (as built in StockController::show)
     * @param array      $indicator RSI/MACD/SMA/BB/ATR/support-resistance, as computed by SignalEngine
     * @param array      $ruleSignal The existing rule-based verdict: signal_type/confidence/reasons/price_at_signal
     * @param array|null $trend     Multi-timeframe trend consensus from SignalEngine::multiTimeframeTrend()
     * @param array      $context   Optional extra grounding data — any of:
     *                              'high_low' (52-week high/low + moving averages, Chukul's
     *                                  high-low-avg-count shape: weeks_high_52/weeks_low_52/
     *                                  days_avg_120/days_avg_180/days_avg_volume_50),
     *                              'volume_analytics' (buy_pct/sell_pct/avg_volume/last_volume
     *                                  from StockController's last-20-candle buy/sell pressure calc),
     *                              'alpha_beta' (Chukul alpha-beta shape), 'var_monthly' (Chukul VaR shape),
     *                              'recent_closes' (array of the last ~15 daily closes, oldest→newest)
     * @return array{
     *     verdict:string,confidence:int,summary:string,key_risk:string,
     *     when_to_buy:string,when_to_sell:string,
     *     entry_price:?float,target_price:?float,stop_loss:?float,
     *     outlook_10d:string,prediction_10d:array<int,array{day:int,price:float,trend:string}>
     * }|null
     */
    public function analyze(object $stock, array $indicator, array $ruleSignal, ?array $trend = null, array $context = []): ?array
    {
        $provider = strtolower((string) config('services.ai_provider', 'gemini'));
        $symbol = $stock->symbol ?? null;
        $prompt = $this->buildPrompt($stock, $indicator, $ruleSignal, $trend, $context);

        try {
            $text = match ($provider) {
                'anthropic' => $this->callAnthropic($prompt),
                default     => $this->callGemini($prompt),
            };

            if ($text === null) {
                return null;
            }

            return $this->parseResult($text, $symbol);
        } catch (\Throwable $e) {
            Log::warning('[ai-signal] request failed', [
                'provider' => $provider,
                'symbol'   => $symbol,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function callGemini(string $prompt): ?string
    {
        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $url = sprintf(self::GEMINI_ENDPOINT, $model);

        $response = $this->client->post($url, [
            'query'   => ['key' => $apiKey],
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => [
                'systemInstruction' => [
                    'parts' => [['text' => $this->systemPrompt()]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'      => 0.3,
                    // Gemini 3.x "flash" models spend part of the output budget on
                    // internal reasoning (thoughtsTokenCount) before the visible
                    // JSON, and the response now includes a 10-day prediction array —
                    // 2048 leaves comfortable headroom for both.
                    'maxOutputTokens'  => 2048,
                    'responseMimeType' => 'application/json',
                ],
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        return (is_string($text) && trim($text) !== '') ? $text : null;
    }

    private function callAnthropic(string $prompt): ?string
    {
        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return null;
        }

        $response = $this->client->post(self::ANTHROPIC_ENDPOINT, [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => self::ANTHROPIC_API_VERSION,
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'       => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
                'max_tokens'  => 1500,
                'temperature' => 0.3,
                'system'      => $this->systemPrompt(),
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $text = $body['content'][0]['text'] ?? null;

        return (is_string($text) && trim($text) !== '') ? $text : null;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a NEPSE (Nepal Stock Exchange) equity analyst. You are given a stock's
current technical indicators and an existing rule-based signal. Give your own
independent read of the same data — you may agree or disagree with the
rule-based verdict. Also give concrete buy/sell guidance and a 10-trading-day
price path estimate, anchored to the given current price and consistent with
your verdict (an UP path for BUY, DOWN for SELL, roughly flat/choppy for HOLD).

Respond with STRICT JSON only, no markdown fences, no extra text, matching
exactly this shape:
{
  "verdict": "BUY|SELL|HOLD",
  "confidence": <integer 0-100>,
  "summary": "<2-3 plain-English sentences>",
  "key_risk": "<one sentence>",
  "when_to_buy": "<one sentence: the condition/price zone that would justify buying now, or 'Already a buy at current levels' if applicable>",
  "when_to_sell": "<one sentence: the condition/price zone that would justify selling/exiting>",
  "entry_price": <number, a sensible entry price near current price>,
  "target_price": <number, realistic take-profit target>,
  "stop_loss": <number, a risk-management stop level>,
  "outlook_10d": "<one sentence summarizing the 10-day path>",
  "prediction_10d": [
    {"day": 1, "price": <number>, "trend": "up|down|flat"},
    ... exactly 10 entries, day 1 through day 10, each price a plausible next-day-close continuing from the previous day ...
  ]
}
PROMPT;
    }

    private function buildPrompt(object $stock, array $indicator, array $ruleSignal, ?array $trend, array $context = []): string
    {
        $lines = [
            "Stock: {$stock->symbol} — " . ($stock->name ?? $stock->symbol) . (isset($stock->sector->name) ? " ({$stock->sector->name} sector)" : ''),
            'Current price: NPR ' . number_format((float) ($ruleSignal['price_at_signal'] ?? 0), 2),
            '',
            'Indicators:',
            '- RSI(14): ' . ($indicator['rsi_14'] ?? 'n/a'),
            '- MACD: ' . ($indicator['macd'] ?? 'n/a') . ' / signal: ' . ($indicator['macd_signal'] ?? 'n/a') . ' / histogram: ' . ($indicator['macd_histogram'] ?? 'n/a'),
            '- SMA20/50/200: ' . ($indicator['sma_20'] ?? 'n/a') . ' / ' . ($indicator['sma_50'] ?? 'n/a') . ' / ' . ($indicator['sma_200'] ?? 'n/a'),
            '- Bollinger Bands: lower ' . ($indicator['bb_lower'] ?? 'n/a') . ', middle ' . ($indicator['bb_middle'] ?? 'n/a') . ', upper ' . ($indicator['bb_upper'] ?? 'n/a'),
            '- ATR(14): ' . ($indicator['atr_14'] ?? 'n/a'),
            '- Support: ' . ($indicator['support_1'] ?? 'n/a') . ' / ' . ($indicator['support_2'] ?? 'n/a'),
            '- Resistance: ' . ($indicator['resistance_1'] ?? 'n/a') . ' / ' . ($indicator['resistance_2'] ?? 'n/a'),
            '',
            'Rule-based verdict: ' . ($ruleSignal['signal_type'] ?? 'n/a') . ' (' . ($ruleSignal['confidence'] ?? 0) . '% confidence)',
            'Rule-based reasons: ' . implode('; ', $ruleSignal['reasons'] ?? []),
        ];

        if (!empty($trend['consensus']['signal'])) {
            $lines[] = '';
            $lines[] = "Multi-timeframe trend consensus: {$trend['consensus']['signal']} — {$trend['consensus']['description']}";
        }

        $hl = $context['high_low'] ?? null;
        if (!empty($hl)) {
            $lines[] = '';
            $lines[] = '52-week high: NPR ' . ($hl['weeks_high_52'] ?? 'n/a') . ' / 52-week low: NPR ' . ($hl['weeks_low_52'] ?? 'n/a');
            $lines[] = '120-day avg price: ' . ($hl['days_avg_120'] ?? 'n/a') . ' / 180-day avg price: ' . ($hl['days_avg_180'] ?? 'n/a');
            $lines[] = '50-day avg volume: ' . ($hl['days_avg_volume_50'] ?? 'n/a');
        }

        $vol = $context['volume_analytics'] ?? null;
        if (!empty($vol)) {
            $lines[] = '';
            $lines[] = "Last 20 sessions: {$vol['buy_pct']}% buy-pressure volume vs {$vol['sell_pct']}% sell-pressure volume"
                . " ({$vol['buy_candles']} up-days, {$vol['sell_candles']} down-days)";
            $lines[] = 'Average volume: ' . ($vol['avg_volume'] ?? 'n/a') . ' / Last session volume: ' . ($vol['last_volume'] ?? 'n/a');
        }

        $ab = $context['alpha_beta'] ?? null;
        if (!empty($ab)) {
            $lines[] = '';
            $lines[] = 'Risk profile: 12-month beta ' . ($ab['beta_12_months'] ?? 'n/a') . ' (volatility vs. NEPSE index), 12-month alpha ' . ($ab['alpha_12_months'] ?? 'n/a');
        }

        $var = $context['var_monthly'] ?? null;
        if (!empty($var)) {
            $lines[] = 'Value-at-Risk (95% monthly): ' . ($var['var_95_cf'] ?? 'n/a') . '% / monthly volatility (std dev): ' . ($var['std_deviation_monthly'] ?? 'n/a') . '%';
        }

        $closes = $context['recent_closes'] ?? null;
        if (!empty($closes)) {
            $lines[] = '';
            $lines[] = 'Last ' . count($closes) . ' daily closes (oldest→newest): ' . implode(', ', array_map(fn ($c) => number_format((float) $c, 2), $closes));
        }

        return implode("\n", $lines);
    }

    private function parseResult(string $text, ?string $symbol): ?array
    {
        $text = trim($text);
        // Strip markdown code fences if the model wraps the JSON anyway.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            Log::warning('[ai-signal] non-JSON response', ['symbol' => $symbol, 'text' => $text]);
            return null;
        }

        $verdict = strtoupper((string) ($decoded['verdict'] ?? ''));
        if (!in_array($verdict, ['BUY', 'SELL', 'HOLD'], true)) {
            Log::warning('[ai-signal] invalid verdict', ['symbol' => $symbol, 'verdict' => $verdict]);
            return null;
        }

        $confidence = (int) ($decoded['confidence'] ?? 0);
        $confidence = max(0, min(100, $confidence));

        $prediction10d = [];
        foreach ((array) ($decoded['prediction_10d'] ?? []) as $row) {
            if (!is_array($row) || !isset($row['day'], $row['price'])) {
                continue;
            }
            $trend = strtolower((string) ($row['trend'] ?? 'flat'));
            $prediction10d[] = [
                'day'   => (int) $row['day'],
                'price' => (float) $row['price'],
                'trend' => in_array($trend, ['up', 'down', 'flat'], true) ? $trend : 'flat',
            ];
        }

        return [
            'verdict'        => $verdict,
            'confidence'     => $confidence,
            'summary'        => (string) ($decoded['summary'] ?? ''),
            'key_risk'       => (string) ($decoded['key_risk'] ?? ''),
            'when_to_buy'    => (string) ($decoded['when_to_buy'] ?? ''),
            'when_to_sell'   => (string) ($decoded['when_to_sell'] ?? ''),
            'entry_price'    => isset($decoded['entry_price']) ? (float) $decoded['entry_price'] : null,
            'target_price'   => isset($decoded['target_price']) ? (float) $decoded['target_price'] : null,
            'stop_loss'      => isset($decoded['stop_loss']) ? (float) $decoded['stop_loss'] : null,
            'outlook_10d'    => (string) ($decoded['outlook_10d'] ?? ''),
            'prediction_10d' => $prediction10d,
        ];
    }
}
