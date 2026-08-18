@extends('layouts.app')
@section('title', 'Screener')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 20; vertical-align:middle; }
.screener-input { width:100%; padding:.55rem .75rem; font-size:.85rem; border-radius:.5rem; background:#f8fafc; border:1px solid #e2e8f0; color:#0f172a; outline:none; transition:border-color .15s, box-shadow .15s; }
.screener-input:focus { border-color:#16A34A; box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.screener-row:hover { background:#f8fafc; }
</style>
@endpush

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold" style="color:#0f172a;">📊 Stock Screener</h1>
            <p class="text-sm mt-1" style="color:#64748b;">Filter and analyse NEPSE stocks based on technical indicators.</p>
        </div>
        <div class="inline-flex items-center text-sm font-semibold px-3 py-1 rounded-full self-start md:self-auto"
             style="background:#DCFCE7;color:#14532D;">
            {{ $stocks->total() }} results found
        </div>
    </div>

    {{-- Filter panel --}}
    <form method="GET" class="bg-white border rounded-xl p-4" style="border-color:#e2e8f0;">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Sector</label>
                <select name="sector" class="screener-input">
                    <option value="">All Sectors</option>
                    @foreach($sectors as $sector)
                    <option value="{{ $sector->id }}" {{ request('sector') == $sector->id ? 'selected' : '' }}>
                        {{ $sector->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">RSI (14)</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="rsi_min" value="{{ request('rsi_min') }}" placeholder="Min"
                           min="0" max="100" step="1" class="screener-input">
                    <span style="color:#94a3b8;">–</span>
                    <input type="number" name="rsi_max" value="{{ request('rsi_max') }}" placeholder="Max"
                           min="0" max="100" step="1" class="screener-input">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Signal</label>
                <select name="signal" class="screener-input">
                    <option value="">Any Signal</option>
                    <option value="buy"  {{ request('signal') === 'buy' ? 'selected' : '' }}>Buy</option>
                    <option value="sell" {{ request('signal') === 'sell' ? 'selected' : '' }}>Sell</option>
                    <option value="hold" {{ request('signal') === 'hold' ? 'selected' : '' }}>Hold</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">% Change</label>
                <select name="change_sign" class="screener-input">
                    <option value="">Any</option>
                    <option value="positive" {{ request('change_sign') === 'positive' ? 'selected' : '' }}>Positive</option>
                    <option value="negative" {{ request('change_sign') === 'negative' ? 'selected' : '' }}>Negative</option>
                </select>
            </div>
            <div class="flex gap-2 col-span-2 md:col-span-1">
                <button type="submit" class="btn-primary flex-1 justify-center">Apply</button>
                @if(request()->hasAny(['sector','rsi_min','rsi_max','signal','change_sign']))
                <a href="{{ route('screener.index') }}" class="btn-ghost justify-center">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Results --}}
    <div class="bg-white border rounded-xl overflow-hidden" style="border-color:#e2e8f0;">
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Symbol</th>
                    <th class="hidden md:table-cell text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Change %</th>
                    <th class="hidden lg:table-cell text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Volume</th>
                    <th class="hidden sm:table-cell text-center px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">RSI (14)</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Signal</th>
                    <th class="hidden sm:table-cell text-center px-4 py-3 text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#64748b;">Action</th>
                </tr>
            </thead>
            <tbody style="color:#0f172a;">
                @forelse($stocks as $stock)
                @php $p = $stock->latestPrice; $sig = $stock->latestSignal; @endphp
                <tr class="screener-row transition-colors" style="border-bottom:1px solid #f1f5f9;">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}"
                           class="font-bold transition-colors" style="color:#0f172a;"
                           onmouseover="this.style.color='#14532D'" onmouseout="this.style.color='#0f172a'">
                            {{ $stock->symbol }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($stock->name, 20) }}</div>
                    </td>
                    <td class="hidden md:table-cell px-4 py-3">
                        @if($stock->sector)
                        <span class="text-xs px-2 py-0.5 rounded" style="background:#DCFCE7;color:#14532D;">
                            {{ $stock->sector->name }}
                        </span>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3">{{ $p ? number_format($p->close, 2) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $p ? ($p->change_percent >= 0 ? '+' : '') . number_format($p->change_percent, 2) . '%' : '—' }}
                    </td>
                    <td class="hidden lg:table-cell text-right font-mono px-4 py-3" style="color:#64748b;">{{ $p ? number_format($p->volume) : '—' }}</td>
                    <td class="hidden sm:table-cell text-center font-mono px-4 py-3">
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
                        @else <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-center px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}"
                           class="text-xs px-3 py-1 rounded-md font-semibold transition-colors"
                           style="background:#DCFCE7;color:#14532D;border:1px solid #bbf7d0;">
                            Analyse
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center px-4 py-12" style="color:#94a3b8;">
                        No stocks match your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div>{{ $stocks->links() }}</div>
</div>
@endsection
