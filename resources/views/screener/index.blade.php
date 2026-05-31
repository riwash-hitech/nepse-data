@extends('layouts.app')
@section('title', 'Screener')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">📊 Stock Screener</h1>
    </div>

    {{-- Filter panel --}}
    <form method="GET" class="glass p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Sector</label>
                <select name="sector"
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
                    <option value="">All Sectors</option>
                    @foreach($sectors as $sector)
                    <option value="{{ $sector->id }}" {{ request('sector') == $sector->id ? 'selected' : '' }}>
                        {{ $sector->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">RSI Min</label>
                <input type="number" name="rsi_min" value="{{ request('rsi_min') }}" placeholder="e.g. 30"
                       min="0" max="100" step="1"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">RSI Max</label>
                <input type="number" name="rsi_max" value="{{ request('rsi_max') }}" placeholder="e.g. 70"
                       min="0" max="100" step="1"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Signal</label>
                <select name="signal"
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
                    <option value="">Any</option>
                    <option value="buy"  {{ request('signal') === 'buy' ? 'selected' : '' }}>BUY</option>
                    <option value="sell" {{ request('signal') === 'sell' ? 'selected' : '' }}>SELL</option>
                    <option value="hold" {{ request('signal') === 'hold' ? 'selected' : '' }}>HOLD</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Change % Min</label>
                <input type="number" name="change_min" value="{{ request('change_min') }}" placeholder="e.g. -5"
                       step="0.5"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center">Screen</button>
                @if(request()->hasAny(['sector','rsi_min','rsi_max','signal','change_min','change_max','vol_min']))
                <a href="{{ route('screener.index') }}" class="btn-ghost">✕</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Results --}}
    <div class="glass overflow-hidden">
        <div class="px-4 py-3 border-b text-sm" style="border-color:rgba(255,255,255,0.06);color:#64748b;">
            {{ $stocks->total() }} results found
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06);">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Change%</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Volume</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">RSI</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Signal</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php $p = $stock->latestPrice; $sig = $stock->latestSignal; @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);" class="hover:bg-white/3 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}"
                           class="font-bold text-white hover:text-blue-400 transition-colors">
                            {{ $stock->symbol }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#475569;">{{ Str::limit($stock->name, 20) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if($stock->sector)
                        <span class="text-xs px-2 py-0.5 rounded" style="background:rgba(99,102,241,0.1);color:#a5b4fc;">
                            {{ $stock->sector->name }}
                        </span>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-white">{{ $p ? number_format($p->close, 2) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $p ? ($p->change_percent >= 0 ? '+' : '') . number_format($p->change_percent, 2) . '%' : '—' }}
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#94a3b8;">{{ $p ? number_format($p->volume) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3">
                        @if($stock->latestIndicator)
                        @php $rsi = $stock->latestIndicator->rsi_14 ?? null; @endphp
                        @if($rsi)
                        <span class="{{ $rsi < 30 ? 'change-pos' : ($rsi > 70 ? 'change-neg' : '') }}">{{ number_format($rsi, 1) }}</span>
                        @else —
                        @endif
                        @else —
                        @endif
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
                    <td class="text-center px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}"
                           class="text-xs px-3 py-1 rounded-md transition-colors"
                           style="background:rgba(37,99,235,0.15);color:#60a5fa;border:1px solid rgba(37,99,235,0.2);">
                            Analyse
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center px-4 py-12" style="color:#475569;">
                        No stocks match your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $stocks->links() }}</div>
</div>
@endsection
