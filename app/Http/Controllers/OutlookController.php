<?php

namespace App\Http\Controllers;

use App\Services\NepseScraperService;
use App\Services\Outlook30Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutlookController extends Controller
{
    private const CACHE_KEY = 'outlook_30d_v1';
    private const MIN_AVG_VOLUME = 500; // filter out illiquid/untradeable stocks
    // NEPSE stocks are volatile enough that even genuine trends rarely score
    // above ~25-30 on this confidence scale — 35 was screening out every
    // single stock, including real movers. 20 keeps out pure-noise fits
    // (confidence sits at the floor of 10 when R² is near zero) while still
    // admitting fits with a real, if imperfect, signal.
    private const MIN_CONFIDENCE = 20;

    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        // Not auto-computed — the admin/user explicitly clicks Generate,
        // since scanning ~200 stocks against the live API takes a while.
        $outlook = Cache::get(self::CACHE_KEY);
        $generatedAtRaw = Cache::get(self::CACHE_KEY . '_at');
        $generatedAt = $generatedAtRaw ? \Carbon\Carbon::parse($generatedAtRaw) : null;

        return view('outlook.index', [
            'outlook'      => $outlook,
            'generatedAt'  => $generatedAt,
        ]);
    }

    public function generate()
    {
        $outlook = $this->computeOutlook();

        Cache::put(self::CACHE_KEY, $outlook, 3600);
        Cache::put(self::CACHE_KEY . '_at', now()->toDateTimeString(), 3600);

        return redirect()->route('outlook.index')->with('success', 'Generated top 1-month return predictions for ' . count($outlook) . ' stocks.');
    }

    public function export(): StreamedResponse
    {
        $outlook = Cache::get(self::CACHE_KEY, []);

        $filename = 'top-1-month-return-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($outlook) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel doesn't mangle special characters
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Rank', 'Symbol', 'Name', 'Sector', 'Current Price', '30D Target',
                'Target Low', 'Target High', 'Predicted Return %', 'Confidence %', 'R-Squared',
            ]);

            foreach ($outlook as $i => $o) {
                fputcsv($out, [
                    $i + 1,
                    $o['symbol'],
                    $o['name'],
                    $o['sector'],
                    $o['current_price'],
                    $o['target_price'],
                    $o['target_low'],
                    $o['target_high'],
                    $o['expected_return_pct'],
                    $o['confidence'],
                    $o['r_squared'],
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }

    private function computeOutlook(): array
    {
        $stockList = Cache::remember('chukul_stock_list', 3600, fn() => $this->scraper->fetchStockList());

        $active = collect($stockList)
            ->filter(fn($s) => !($s['is_delisted'] ?? false) && !($s['is_merged'] ?? false))
            ->values();

        $ranked = [];

        // Analyse every active stock (limit to 200 to stay within request budget)
        foreach ($active->take(200) as $stock) {
            try {
                $result = $this->analyseStock($stock['symbol'], $stock['name'], $stock['sector'] ?? 'Other');
                if ($result !== null) {
                    $ranked[] = $result;
                }
            } catch (\Throwable $e) {
                Log::debug("Outlook30: skipping {$stock['symbol']} — {$e->getMessage()}");
            }
        }

        // Rank purely by predicted 1-month return (highest first) — the
        // confidence bar in analyseStock() already screens out unreliable
        // trend fits, so this surfaces the biggest predicted gainers among
        // stocks that already cleared that bar.
        usort($ranked, fn($a, $b) => $b['expected_return_pct'] <=> $a['expected_return_pct']);

        return array_slice($ranked, 0, 30);
    }

    private function analyseStock(string $symbol, string $name, string $sector): ?array
    {
        $prices = $this->scraper->fetchHistoricalPrices($symbol, 365);

        $projection = Outlook30Service::project($prices);
        if ($projection === null) {
            return null;
        }

        if ($projection['avg_volume_20d'] < self::MIN_AVG_VOLUME) {
            return null;
        }

        if ($projection['confidence'] < self::MIN_CONFIDENCE) {
            return null;
        }

        // Only surface stocks actually predicted to gain — this page is a
        // profit-picker, not a "least-bad" ranking. A negative projection
        // just means we skip it here (the Screener/Signals pages are where
        // downtrend/SELL analysis belongs).
        if ($projection['expected_return_pct'] <= 0) {
            return null;
        }

        return array_merge($projection, [
            'symbol' => $symbol,
            'name'   => $name,
            'sector' => $sector,
        ]);
    }
}
