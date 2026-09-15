<?php

namespace App\Http\Controllers;

use App\Services\IpoService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class IpoController extends Controller
{
    public function index()
    {
        $ipoList = IpoService::getIpoList();

        // Sort: Open first, then by closing_date desc
        usort($ipoList, function ($a, $b) {
            $order = ['Open' => 0, 'Upcoming' => 1, 'Closed' => 2, 'Listed' => 3];
            $aO = $order[$a['status'] ?? ''] ?? 4;
            $bO = $order[$b['status'] ?? ''] ?? 4;
            if ($aO !== $bO) return $aO - $bO;
            return strcmp($b['closing_date'] ?? '', $a['closing_date'] ?? '');
        });

        if (Auth::check()) {
            Activity::log(Auth::user(), 'ipo_view', 'Viewed IPO Results.');
        }

        return view('ipo.index', compact('ipoList'));
    }

    /**
     * Refresh company list by clearing cache.
     */
    public function refreshCompanies()
    {
        Cache::forget('chukul_ipo_list');
        return redirect()->route('ipo.index')->with('success', 'IPO list refreshed.');
    }
}
