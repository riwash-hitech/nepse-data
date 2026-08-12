<?php

namespace App\Http\Controllers;

use App\Services\NepseScraperService;
use App\Services\Outlook30Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OutlookController extends Controller
{
    private const CACHE_KEY = 'outlook_30d_v1';
    private const MIN_AVG_VOLUME = 500; // filter out illiquid/untradeable stocks
    private const MIN_CONFIDENCE = 35;

    public function __construct(private readonly NepseScraperService $scraper) {}

    public function index()
    {
        // Cached for 1 hour — each stock analysis hits the Chukul API
        $outlook = Cache::remember(self::CACHE_KEY, 3600, function () {
            return $this->computeOutlook();
        });

        return view('outlook.index', compact('outlook'));
    }

    public function refresh()
    {
        Cache::forget(self::CACHE_KEY);
        return redirect()->route('outlook.index')->with('success', '30-day outlook refreshed.');
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

        // Rank by confidence-weighted expected return — a highly confident +8%
        // trend beats a coin-flip +25% one.
        usort($ranked, fn($a, $b) => $b['rank_score'] <=> $a['rank_score']);

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

        return array_merge($projection, [
            'symbol'     => $symbol,
            'name'       => $name,
            'sector'     => $sector,
            'rank_score' => $projection['expected_return_pct'] * ($projection['confidence'] / 100),
        ]);
    }
}
