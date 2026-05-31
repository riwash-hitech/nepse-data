<?php

namespace App\Services;

use App\Models\Floorsheet;
use App\Models\MarketSummary;
use App\Models\Sector;
use App\Models\Stock;
use App\Models\StockPrice;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NepseScraperService — powered by Chukul.com open APIs
 *
 * Confirmed working endpoints:
 *   GET /api/sector/                                → all sectors
 *   GET /api/stock/                                 → all 674 stocks
 *   GET /api/data/historydata/data/?symbol=SYMBOL   → daily OHLCV columnar
 *   GET /api/data/adjhistorydata/data/?symbol=SYM   → adjusted daily OHLCV
 *   GET /api/data/newintrahistorydata/data/?symbol= → intraday columnar
 *   GET /api/broker/                                → broker list
 *
 * Data format (TradingView columnar):
 *   { t:[unix_ts], o:[open], c:[close], h:[high], l:[low], vol:[volume], amt:[turnover] }
 */
class NepseScraperService
{
    private const BASE = 'https://chukul.com';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => self::BASE,
            'timeout'         => 30,
            'connect_timeout' => 10,
            'verify'          => false,
            'headers'         => [
                'Accept'          => 'application/json',
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer'         => 'https://chukul.com/',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    //  Stock list — fetch only, no DB (for live search / autocomplete)
    // ────────────────────────────────────────────────────────────────────

    /**
     * Returns all Chukul stocks as plain array — cached 1 hr, never writes to DB.
     * Each item: [symbol, name, sector, is_delisted, is_merged]
     */
    public function fetchStockList(): array
    {
        $raw = $this->get('/api/stock/');
        if (!is_array($raw)) {
            return [];
        }
        return array_map(fn($s) => [
            'symbol'      => strtoupper(trim($s['symbol'] ?? '')),
            'name'        => $s['name'] ?? $s['symbol'] ?? '',
            'sector'      => $s['sector'] ?? null,
            'is_delisted' => !empty($s['is_delisted']),
            'is_merged'   => !empty($s['is_merged']),
        ], array_filter($raw, fn($s) => !empty($s['symbol'])));
    }

    // ────────────────────────────────────────────────────────────────────
    //  Sectors — fetch only, no DB
    // ────────────────────────────────────────────────────────────────────

    /**
     * Returns sector list as plain array — no DB writes.
     * Each item: [name, symbol, active, index]
     */
    public function fetchSectorList(): array
    {

        $raw = $this->get('/api/sector/');
        if (!is_array($raw)) {
            return [];
        }
        return array_map(fn($s) => [
            'name'   => $s['name'] ?? '',
            'symbol' => $s['symbol'] ?? '',
            'active' => $s['active'] ?? true,
        ], array_filter($raw, fn($s) => !empty($s['name'])));
    }

    // ────────────────────────────────────────────────────────────────────
    //  Broker list  (GET /api/broker/)  — fetch only, no DB
    // ────────────────────────────────────────────────────────────────────

    /**
     * Returns all Chukul brokers as plain array — cached 24 hr.
     * Each item: [id, broker_no, broker_name]
     */
    public function fetchBrokerList(): array
    {
        $raw = $this->get('/api/broker/');
        if (!is_array($raw)) return [];
        return $raw;
    }

    // ────────────────────────────────────────────────────────────────────
    //  Sectors  (GET /api/sector/)  — with DB sync
    // ────────────────────────────────────────────────────────────────────

    public function syncSectors(): int
    {
        $raw = $this->get('/api/sector/');
        if (!is_array($raw)) {
            Log::warning('NepseScraper: sectors response invalid');
            return 0;
        }
        $count = 0;
        foreach ($raw as $item) {
            if (empty($item['name'])) continue;
            Sector::updateOrCreate(
                ['name' => $item['name']],
                ['slug' => Str::slug($item['name'])]
            );
            $count++;
        }
        return $count;
    }

    // ────────────────────────────────────────────────────────────────────
    //  All stocks  (GET /api/stock/)
    //  Response: [{id, name, symbol, is_merged, is_delisted, core_capital, sector}]
    // ────────────────────────────────────────────────────────────────────

    public function syncStocks(): int
    {
        $raw = $this->get('/api/stock/');
        if (!is_array($raw)) {
            Log::warning('NepseScraper: stocks response invalid');
            return 0;
        }

        $sectorMap = Sector::pluck('id', 'name')->all();
        $count = 0;

        foreach ($raw as $item) {
            if (empty($item['symbol'])) continue;

            $sectorId = null;
            if (!empty($item['sector'])) {
                $sectorName = (string)$item['sector'];
                if (!isset($sectorMap[$sectorName])) {
                    $sector = Sector::firstOrCreate(
                        ['name' => $sectorName],
                        ['slug' => Str::slug($sectorName)]
                    );
                    $sectorMap[$sectorName] = $sector->id;
                }
                $sectorId = $sectorMap[$sectorName];
            }

            Stock::updateOrCreate(
                ['symbol' => strtoupper(trim($item['symbol']))],
                [
                    'name'      => $item['name'] ?? $item['symbol'],
                    'sector_id' => $sectorId,
                    'is_active' => empty($item['is_delisted']) && empty($item['is_merged']),
                ]
            );
            $count++;
        }
        return $count;
    }

    // ────────────────────────────────────────────────────────────────────
    //  Adjusted daily OHLCV  (GET /api/data/adjhistorydata/?symbol=SYMBOL)
    //  Row response: [{date, symbol, open, high, low, close, ltp, volume, amount}]
    //  Most-recent first. Preferred over historydata/data/ columnar format.
    // ────────────────────────────────────────────────────────────────────

    public function fetchHistoricalPrices(string $symbol, int $days = 9999): array
    {
        $raw = $this->get('/api/data/adjhistorydata/', ['symbol' => strtoupper($symbol)]);

        if (!is_array($raw) || empty($raw)) {
            Log::info("NepseScraper: no adjhistory data for {$symbol}");
            return [];
        }

        $cutoff = now()->subDays($days)->toDateString();
        $rows   = [];

        foreach ($raw as $item) {
            $date  = $item['date'] ?? '';
            $close = (float)($item['close'] ?? $item['ltp'] ?? 0);
            if (!$date || $close <= 0 || $date < $cutoff) continue;

            $rows[] = [
                'date'           => $date,
                'open'           => (float)($item['open']   ?? $close),
                'high'           => (float)($item['high']   ?? $close),
                'low'            => (float)($item['low']    ?? $close),
                'close'          => $close,
                'previous_close' => 0,
                'volume'         => (int)($item['volume']   ?? 0),
                'turnover'       => (float)($item['amount'] ?? 0),
                'transactions'   => 0,
                'change'         => 0,
                'change_percent' => 0,
                'vwap'           => 0,
            ];
        }

        // API returns most-recent first; sort ascending for analytics
        usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

        // Compute change, change_percent, previous_close, vwap
        foreach ($rows as $i => &$row) {
            if ($i > 0) {
                $prev = $rows[$i - 1]['close'];
                $row['previous_close'] = $prev;
                $row['change']         = round($row['close'] - $prev, 2);
                $row['change_percent'] = $prev > 0
                    ? round((($row['close'] - $prev) / $prev) * 100, 2)
                    : 0;
            }
            if ($row['volume'] > 0) {
                $row['vwap'] = round($row['turnover'] / $row['volume'], 2);
            }
        }
        unset($row);

        return $rows;
    }

    // ────────────────────────────────────────────────────────────────────
    //  Current-day market summary  (GET /api/data/v2/market-summary/bysymbol/)
    //  Returns: [{date,open,high,low,close,volume,amount,prev_close,
    //             percentage_change,point_change,
    //             prev_close_weekly,percentage_change_weekly,...
    //             prev_close_monthly,percentage_change_monthly,...}]
    // ────────────────────────────────────────────────────────────────────

    public function fetchMarketSummary(string $symbol): array
    {
        $raw = $this->get('/api/data/v2/market-summary/bysymbol/', ['symbol' => strtoupper($symbol)]);
        if (is_array($raw) && !empty($raw[0])) return $raw[0];
        return [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  52-week high/low + volume averages
    //  GET /api/data/v2/high-low-avg-count/?symbol=SYM
    //  Returns: {weeks_high_52,weeks_low_52,days_avg_120,days_avg_180,
    //            all_time_high_volume,weeks_high_volume_52,weeks_low_volume_52,
    //            days_avg_volume_50}
    // ────────────────────────────────────────────────────────────────────

    public function fetchHighLowStats(string $symbol): array
    {
        $raw = $this->get('/api/data/v2/high-low-avg-count/', ['symbol' => strtoupper($symbol)]);
        return is_array($raw) ? $raw : [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Live RSI  (GET /api/data/indicators/?symbol=SYM)
    //  Returns: [{rsi14}]
    // ────────────────────────────────────────────────────────────────────

    public function fetchLiveIndicators(string $symbol): array
    {
        $raw = $this->get('/api/data/indicators/', ['symbol' => strtoupper($symbol)]);
        if (is_array($raw) && !empty($raw[0])) return $raw[0];
        return [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Pivot support levels  (GET /api/data/support/?symbol=SYM)
    //  Returns: [{date,symbol,low,close}]  — most-recent first
    // ────────────────────────────────────────────────────────────────────

    public function fetchSupportLevels(string $symbol): array
    {
        $raw = $this->get('/api/data/support/', ['symbol' => strtoupper($symbol)]);
        return is_array($raw) ? array_slice($raw, 0, 5) : [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Pivot resistance levels  (GET /api/data/resistance/?symbol=SYM)
    //  Returns: [{date,symbol,high,close}]  — most-recent first
    // ────────────────────────────────────────────────────────────────────

    public function fetchResistanceLevels(string $symbol): array
    {
        $raw = $this->get('/api/data/resistance/', ['symbol' => strtoupper($symbol)]);
        return is_array($raw) ? array_slice($raw, 0, 5) : [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Alpha/Beta risk metrics  (GET /api/data/alpha-beta/?symbol=SYM)
    //  Returns: [{symbol,beta_1_months,alpha_1_months,...beta_12_months,alpha_12_months}]
    // ────────────────────────────────────────────────────────────────────

    public function fetchAlphaBeta(string $symbol): array
    {
        $raw = $this->get('/api/data/alpha-beta/', ['symbol' => strtoupper($symbol)]);
        if (is_array($raw) && !empty($raw[0])) return $raw[0];
        return [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Value-at-Risk + volatility  (GET /api/data/v2/var-monthly/?symbol=SYM)
    //  Returns: [{symbol,var_90_cf,var_95_cf,var_99_cf,std_deviation_monthly,mean_return_month}]
    // ────────────────────────────────────────────────────────────────────

    public function fetchVarMonthly(string $symbol): array
    {
        $raw = $this->get('/api/data/v2/var-monthly/', ['symbol' => strtoupper($symbol)]);
        if (is_array($raw) && !empty($raw[0])) return $raw[0];
        return [];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Floorsheet broker activity  (GET /api/data/floorsheet/?symbol=SYM&date=YYYY-MM-DD)
    //  Returns rows: [{transaction,symbol,buyer,seller,quantity,rate,amount}]
    //
    //  Aggregates per buyer/seller broker number and returns:
    //  [
    //    'date'              => '2026-05-27',
    //    'rows'              => (int) total transactions,
    //    'total_buy_qty'     => float,
    //    'total_sell_qty'    => float,
    //    'total_buy_amount'  => float,
    //    'total_sell_amount' => float,
    //    'buys'  => [ brokerNo => ['qty'=>float,'amount'=>float] ],
    //    'sells' => [ brokerNo => ['qty'=>float,'amount'=>float] ],
    //  ]
    // ────────────────────────────────────────────────────────────────────

    public function fetchFloorsheet(string $symbol, string $date): array
    {
        $raw = $this->get('/api/data/floorsheet/', [
            'symbol' => strtoupper($symbol),
            'date'   => $date,
        ]);

        if (!is_array($raw) || empty($raw)) {
            return [];
        }

        $buys  = [];
        $sells = [];

        foreach ($raw as $row) {
            $buyer  = (string)($row['buyer']    ?? '');
            $seller = (string)($row['seller']   ?? '');
            $qty    = (float)($row['quantity']  ?? 0);
            $amt    = (float)($row['amount']    ?? 0);

            if ($buyer !== '') {
                $buys[$buyer]['qty']    = ($buys[$buyer]['qty']    ?? 0) + $qty;
                $buys[$buyer]['amount'] = ($buys[$buyer]['amount'] ?? 0) + $amt;
            }
            if ($seller !== '') {
                $sells[$seller]['qty']    = ($sells[$seller]['qty']    ?? 0) + $qty;
                $sells[$seller]['amount'] = ($sells[$seller]['amount'] ?? 0) + $amt;
            }
        }

        // Sort by qty descending
        arsort($buys);   // arsort by value works on array-of-arrays? No — use uasort
        uasort($buys,  fn($a, $b) => $b['qty'] <=> $a['qty']);
        uasort($sells, fn($a, $b) => $b['qty'] <=> $a['qty']);

        $totalBuyQty    = array_sum(array_column($buys,  'qty'));
        $totalSellQty   = array_sum(array_column($sells, 'qty'));
        $totalBuyAmt    = array_sum(array_column($buys,  'amount'));
        $totalSellAmt   = array_sum(array_column($sells, 'amount'));

        return [
            'date'              => $date,
            'rows'              => count($raw),
            'total_buy_qty'     => $totalBuyQty,
            'total_sell_qty'    => $totalSellQty,
            'total_buy_amount'  => $totalBuyAmt,
            'total_sell_amount' => $totalSellAmt,
            'buys'              => $buys,
            'sells'             => $sells,
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Intraday candles  (GET /api/data/newintrahistorydata/data/?symbol=SYMBOL)
    //  Response: {t, o, c, h, l, cv(current_value), vol, ca(change_amount), amt}
    // ────────────────────────────────────────────────────────────────────

    public function fetchIntradayPrices(string $symbol): array
    {
        $raw = $this->get('/api/data/newintrahistorydata/data/', ['symbol' => strtoupper($symbol)]);

        if (empty($raw['t']) || !is_array($raw['t'])) {
            return [];
        }

        $rows = [];
        foreach ($raw['t'] as $i => $ts) {
            $rows[] = [
                'datetime' => date('Y-m-d H:i:s', (int)$ts),
                'open'     => (float)($raw['o'][$i]   ?? 0),
                'high'     => (float)($raw['h'][$i]   ?? 0),
                'low'      => (float)($raw['l'][$i]   ?? 0),
                'close'    => (float)($raw['cv'][$i]  ?? $raw['c'][$i] ?? 0),
                'volume'   => (int)($raw['vol'][$i]  ?? 0),
                'turnover' => (float)($raw['amt'][$i] ?? 0),
                'change'   => (float)($raw['ca'][$i]  ?? 0),
            ];
        }
        return $rows;
    }

    // ────────────────────────────────────────────────────────────────────
    //  Live prices — latest candle per active stock
    // ────────────────────────────────────────────────────────────────────

    /**
     * For every active stock, fetch historydata and extract the last (latest) candle.
     * Used by FetchLiveMarketDataJob to refresh today's prices.
     */
    public function fetchLivePrices(): array
    {
        $symbols = Stock::active()->pluck('symbol')->all();
        $result  = [];

        foreach ($symbols as $symbol) {
            try {
                $raw = $this->get('/api/data/historydata/data/', ['symbol' => $symbol]);
                if (empty($raw['t']) || !is_array($raw['t'])) continue;

                $last  = count($raw['t']) - 1;
                $close = (float)($raw['c'][$last] ?? 0);
                if ($close <= 0) continue;

                $prev    = $last > 0 ? (float)($raw['c'][$last - 1] ?? 0) : 0;
                $chg     = round($close - $prev, 2);
                $chgPct  = $prev > 0 ? round(($chg / $prev) * 100, 2) : 0;
                $vol     = (int)($raw['vol'][$last] ?? 0);
                $amt     = (float)($raw['amt'][$last] ?? 0);

                $result[] = [
                    'symbol'         => $symbol,
                    'name'           => $symbol,
                    'date'           => date('Y-m-d', (int)$raw['t'][$last]),
                    'open'           => (float)($raw['o'][$last] ?? $close),
                    'high'           => (float)($raw['h'][$last] ?? $close),
                    'low'            => (float)($raw['l'][$last] ?? $close),
                    'close'          => $close,
                    'previous_close' => $prev,
                    'volume'         => $vol,
                    'turnover'       => $amt,
                    'transactions'   => 0,
                    'change'         => $chg,
                    'change_percent' => $chgPct,
                    'vwap'           => $vol > 0 ? round($amt / $vol, 2) : 0,
                    'sector'         => null,
                ];
            } catch (\Throwable $e) {
                Log::warning("NepseScraper: live price failed for {$symbol}", ['error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    // ────────────────────────────────────────────────────────────────────
    //  Market summary — computed from latest DB prices
    // ────────────────────────────────────────────────────────────────────

    public function computeMarketSummary(): array
    {
        $latestDate = StockPrice::max('date') ?? now()->toDateString();
        $prices     = StockPrice::with('stock:id,symbol')->whereDate('date', $latestDate)->get();

        if ($prices->isEmpty()) return [];

        $topGainers = $prices->where('change_percent', '>', 0)
            ->sortByDesc('change_percent')->take(10)
            ->map(fn($p) => ['symbol' => optional($p->stock)->symbol, 'close' => (float)$p->close, 'change_percent' => (float)$p->change_percent])
            ->values()->toArray();

        $topLosers = $prices->where('change_percent', '<', 0)
            ->sortBy('change_percent')->take(10)
            ->map(fn($p) => ['symbol' => optional($p->stock)->symbol, 'close' => (float)$p->close, 'change_percent' => (float)$p->change_percent])
            ->values()->toArray();

        $mostActive = $prices->sortByDesc('volume')->take(10)
            ->map(fn($p) => ['symbol' => optional($p->stock)->symbol, 'close' => (float)$p->close, 'volume' => (int)$p->volume, 'turnover' => (float)$p->turnover])
            ->values()->toArray();

        return [
            'date'               => $latestDate,
            'nepse_index'        => 0,
            'nepse_change'       => 0,
            'nepse_change_pct'   => 0,
            'total_turnover'     => $prices->sum('turnover'),
            'total_volume'       => $prices->sum('volume'),
            'total_transactions' => $prices->sum('transactions'),
            'positive_count'     => $prices->where('change_percent', '>', 0)->count(),
            'negative_count'     => $prices->where('change_percent', '<', 0)->count(),
            'unchanged_count'    => $prices->where('change_percent', 0)->count(),
            'scrip_traded'       => $prices->count(),
            'top_gainers'        => $topGainers,
            'top_losers'         => $topLosers,
            'most_active'        => $mostActive,
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    //  Persist helpers
    // ────────────────────────────────────────────────────────────────────

    public function persistLivePrices(array $prices): int
    {
        $saved = 0;
        foreach ($prices as $p) {
            if (empty($p['symbol']) || ($p['close'] ?? 0) <= 0) continue;

            $stock = Stock::firstOrCreate(
                ['symbol' => $p['symbol']],
                ['name' => $p['name'] ?? $p['symbol'], 'is_active' => true]
            );

            StockPrice::updateOrCreate(
                ['stock_id' => $stock->id, 'date' => $p['date']],
                [
                    'open'           => $p['open']           ?? $p['close'],
                    'high'           => $p['high']           ?? $p['close'],
                    'low'            => $p['low']            ?? $p['close'],
                    'close'          => $p['close'],
                    'previous_close' => $p['previous_close'] ?? 0,
                    'volume'         => $p['volume']         ?? 0,
                    'turnover'       => $p['turnover']       ?? 0,
                    'transactions'   => $p['transactions']   ?? 0,
                    'change'         => $p['change']         ?? 0,
                    'change_percent' => $p['change_percent'] ?? 0,
                    'vwap'           => $p['vwap']           ?? 0,
                ]
            );
            $saved++;
        }
        return $saved;
    }

    public function persistHistoricalPrices(Stock $stock, array $rows): int
    {
        $saved = 0;
        foreach ($rows as $r) {
            if (empty($r['date']) || ($r['close'] ?? 0) <= 0) continue;

            StockPrice::updateOrCreate(
                ['stock_id' => $stock->id, 'date' => $r['date']],
                [
                    'open'           => $r['open']           ?? $r['close'],
                    'high'           => $r['high']           ?? $r['close'],
                    'low'            => $r['low']            ?? $r['close'],
                    'close'          => $r['close'],
                    'previous_close' => $r['previous_close'] ?? 0,
                    'volume'         => $r['volume']         ?? 0,
                    'turnover'       => $r['turnover']       ?? 0,
                    'transactions'   => $r['transactions']   ?? 0,
                    'change'         => $r['change']         ?? 0,
                    'change_percent' => $r['change_percent'] ?? 0,
                    'vwap'           => $r['vwap']           ?? 0,
                ]
            );
            $saved++;
        }
        return $saved;
    }

    public function persistMarketSummary(array $data): void
    {
        if (empty($data['date'])) return;
        MarketSummary::updateOrCreate(['date' => $data['date']], $data);
    }

    // ────────────────────────────────────────────────────────────────────
    //  HTTP helper
    // ────────────────────────────────────────────────────────────────────

    private function get(string $path, array $query = []): mixed
    {
        try {
            $opts = [];
            if ($query) $opts['query'] = $query;
            $response = $this->client->get($path, $opts);
            $body     = $response->getBody()->getContents();
            $decoded  = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("NepseScraper: invalid JSON from {$path}", ['body' => substr($body, 0, 200)]);
                return null;
            }
            return $decoded;
        } catch (\Throwable $e) {
            Log::warning("NepseScraper GET {$path} failed", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
