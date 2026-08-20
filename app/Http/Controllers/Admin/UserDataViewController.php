<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Watchlist;
use App\Services\NepseScraperService;
use App\Services\PortfolioService;
use App\Services\SignalEngine;
use App\Support\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only admin views into another user's portfolio/watchlist — support
 * and account-review use cases. Reuses the same portfolio.overview and
 * watchlist.index blade views as the user-facing pages, with a $readOnly
 * flag that hides every mutating form/link so an admin can never edit
 * another user's data from here.
 */
class UserDataViewController extends Controller
{
    public function __construct(
        private readonly PortfolioService $portfolio,
        private readonly NepseScraperService $scraper,
        private readonly SignalEngine $signalEngine
    ) {
    }

    public function portfolio(User $user)
    {
        Activity::log(Auth::user(), 'admin_view_portfolio', "Viewed {$user->name}'s portfolio.");

        $data = $this->portfolio->overview($user);

        return view('portfolio.overview', $data + ['viewingUser' => $user, 'readOnly' => true]);
    }

    public function watchlist(User $user)
    {
        Activity::log(Auth::user(), 'admin_view_watchlist', "Viewed {$user->name}'s watchlist.");

        $entries = Watchlist::with(['stock.sector'])
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get();

        $watchlist = $entries->map(function (Watchlist $w) {
            $stock = $w->stock;

            $priceRows = Cache::remember("chukul_adj_{$stock->symbol}", 300, fn() =>
                $this->scraper->fetchHistoricalPrices($stock->symbol)
            );

            $last = !empty($priceRows) ? end($priceRows) : null;
            $analytics = !empty($priceRows) ? $this->signalEngine->analyzeFromData($priceRows) : [];
            $signal = $analytics['signal'] ?? null;

            return [
                'watchlist_id'   => $w->id,
                'stock_id'       => $w->stock_id,
                'symbol'         => $stock->symbol,
                'name'           => $stock->name,
                'sector'         => $stock->sector->name ?? null,
                'ltp'            => $last ? (float) $last['close'] : null,
                'change_percent' => $last ? (float) ($last['change_percent'] ?? 0) : null,
                'high'           => $last ? (float) ($last['high'] ?? 0) : null,
                'low'            => $last ? (float) ($last['low'] ?? 0) : null,
                'volume'         => $last ? (float) ($last['volume'] ?? 0) : null,
                'signal_type'    => $signal['signal_type'] ?? null,
                'confidence'     => $signal['confidence'] ?? null,
            ];
        });

        return view('watchlist.index', compact('watchlist') + ['viewingUser' => $user, 'readOnly' => true]);
    }
}
