<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SignalController extends Controller
{
    public function index(Request $request)
    {
        $type       = $request->get('type', 'all');
        $minConfidence = (int)$request->get('confidence', 60);

        $query = Signal::with('stock:id,symbol,name,sector_id')
            ->active()
            ->where('confidence', '>=', $minConfidence)
            ->whereDate('date', '>=', now()->subDays(5));

        if ($type === 'buy') {
            $query->buy();
        } elseif ($type === 'sell') {
            $query->sell();
        }

        $signals = $query->orderByDesc('confidence')->paginate(30)->withQueryString();

        return view('signals.index', compact('signals', 'type', 'minConfidence'));
    }

    public function show(int $id)
    {
        $signal = Signal::with('stock.sector')->findOrFail($id);
        return view('signals.show', compact('signal'));
    }
}
