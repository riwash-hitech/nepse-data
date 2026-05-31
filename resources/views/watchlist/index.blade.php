@extends('layouts.app')
@section('title', 'My Watchlist')

@section('content')
<div class="space-y-5">

    <h1 class="text-2xl font-bold text-white">📌 My Watchlist</h1>

    @if($watchlist->isEmpty())
        <div class="glass p-12 text-center" style="color:#64748b;">
            <p class="mb-3">Your watchlist is empty.</p>
            <a href="{{ route('stocks.index') }}" class="btn-primary inline-flex">Browse Stocks</a>
        </div>
    @else
    <div class="glass overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06);">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Change%</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Signal</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Confidence</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($watchlist as $w)
                @php $p = $w->stock->latestPrice; $sig = $w->stock->latestSignal; @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);" class="hover:bg-white/3 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $w->stock->symbol) }}"
                           class="font-bold text-white hover:text-blue-400 transition-colors">
                            {{ $w->stock->symbol }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#475569;">{{ Str::limit($w->stock->name, 28) }}</div>
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-white">{{ $p ? number_format($p->close, 2) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $p ? ($p->change_percent >= 0 ? '+' : '') . number_format($p->change_percent, 2) . '%' : '—' }}
                    </td>
                    <td class="text-center px-4 py-3">
                        @if($sig)
                            @if($sig->signal_type === 'BUY')   <span class="badge-buy">BUY</span>
                            @elseif($sig->signal_type === 'SELL') <span class="badge-sell">SELL</span>
                            @else <span class="badge-hold">HOLD</span>
                            @endif
                        @else <span style="color:#334155;">—</span>
                        @endif
                    </td>
                    <td class="text-right px-4 py-3">
                        @if($sig)
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-16 rounded-full h-1.5" style="background:rgba(255,255,255,0.1);">
                                <div class="h-1.5 rounded-full"
                                     style="width:{{ $sig->confidence }}%;background:{{ $sig->signal_type === 'BUY' ? '#22c55e' : ($sig->signal_type === 'SELL' ? '#ef4444' : '#eab308') }};"></div>
                            </div>
                            <span class="text-xs font-mono" style="color:#94a3b8;">{{ $sig->confidence }}%</span>
                        </div>
                        @else —
                        @endif
                    </td>
                    <td class="text-center px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('stocks.show', $w->stock->symbol) }}"
                               class="text-xs px-3 py-1 rounded-md"
                               style="background:rgba(37,99,235,0.15);color:#60a5fa;">View</a>
                            <form method="POST" action="{{ route('watchlist.destroy', $w->stock_id) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Remove from watchlist?')"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
