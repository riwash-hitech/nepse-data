@extends('layouts.app')
@section('title', 'Markets')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
<style>
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 20; vertical-align:middle; }
.market-input { padding:.5rem .75rem; font-size:.85rem; border-radius:.5rem; background:#f8fafc; border:1px solid #e2e8f0; color:#0f172a; outline:none; transition:border-color .15s, box-shadow .15s; }
.market-input:focus { border-color:#16A34A; box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.market-row:hover { background:#f8fafc; }
.view-chip { padding:.5rem 1rem; border-radius:9999px; border:1px solid #e2e8f0; background:#fff; color:#64748b; font-size:.8rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; transition:all .15s; }
.view-chip:hover { background:#f8fafc; }
.view-chip.active { background:#14532D; border-color:#14532D; color:#fff; }
</style>
@endpush

@section('content')
<div class="space-y-5">

    {{-- ════ MARKET OVERVIEW HEADER ═══════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-2 space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold"
                      style="background:{{ $marketStatus['open'] ? '#DCFCE7' : '#f1f5f9' }};color:{{ $marketStatus['open'] ? '#14532D' : '#64748b' }};">
                    <span class="w-2 h-2 rounded-full" style="background:{{ $marketStatus['open'] ? '#16a34a' : '#94a3b8' }};{{ $marketStatus['open'] ? 'animation:pulse 1.6s infinite;' : '' }}"></span>
                    {{ $marketStatus['open'] ? 'Market Open' : 'Market Closed' }}
                </span>
                <span class="text-sm" style="color:#94a3b8;">
                    {{ $marketStatus['open'] ? 'Closes in ' . $marketStatus['closesIn'] : 'Sun–Thu, 11:00–15:00 NPT' }}
                </span>
            </div>
            <h1 class="text-3xl font-bold" style="color:#0f172a;">Market Overview</h1>

            <form method="GET" class="flex flex-wrap gap-3 items-center pt-1">
                @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif
                @if($sector) <input type="hidden" name="sector" value="{{ $sector }}"> @endif
                @php
                $viewChips = [
                    'all'      => ['label'=>'All Stocks', 'icon'=>null],
                    'gainers'  => ['label'=>'Gainers',    'icon'=>'trending_up'],
                    'losers'   => ['label'=>'Losers',     'icon'=>'trending_down'],
                    'turnover' => ['label'=>'Turnover',   'icon'=>'swap_horiz'],
                ];
                @endphp
                @foreach($viewChips as $key => $chip)
                <a href="{{ route('stocks.index', array_merge(array_filter(request()->except('view')), ['view' => $key])) }}"
                   class="view-chip {{ $view === $key ? 'active' : '' }}">
                    @if($chip['icon'])<span class="material-symbols-outlined" style="font-size:16px;">{{ $chip['icon'] }}</span>@endif
                    {{ $chip['label'] }}
                </a>
                @endforeach
            </form>
        </div>

        {{-- Market Summary widget --}}
        <div class="bg-white border rounded-xl p-5" style="border-color:#e2e8f0;">
            <h3 class="text-base font-bold pb-3 mb-3 border-b" style="color:#0f172a;border-color:#f1f5f9;">Market Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color:#94a3b8;">Total Turnover</span>
                    <span class="font-semibold font-mono" style="color:#0f172a;">{{ $marketSummary['turnover'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color:#94a3b8;">Shares Traded</span>
                    <span class="font-semibold font-mono" style="color:#0f172a;">{{ $marketSummary['volume'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color:#94a3b8;">Listed Stocks</span>
                    <span class="font-semibold font-mono" style="color:#0f172a;">{{ number_format($stocks->total()) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color:#94a3b8;">Sectors</span>
                    <span class="font-semibold font-mono" style="color:#0f172a;">{{ $sectors->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ════ NEPSE INDEX BANNER ════════════════════════════════════════════ --}}
    @if($nepseIndex)
    <div class="rounded-xl overflow-hidden relative p-8" style="background:linear-gradient(135deg,#0f172a 0%,#14532D 55%,#166534 100%);">
        <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(34,197,94,.25),transparent 70%);pointer-events:none;"></div>
        <div class="relative z-10">
            <h2 class="text-2xl font-bold text-white">NEPSE Index</h2>
            <p class="text-xl mt-1" style="color:{{ $nepseIndex['change'] >= 0 ? '#86efac' : '#fca5a5' }};">
                {{ number_format($nepseIndex['close'], 2) }}
                <span class="text-sm">{{ $nepseIndex['change'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($nepseIndex['change']), 2) }} ({{ number_format($nepseIndex['change_percent'], 2) }}%)</span>
            </p>
        </div>
    </div>
    @endif

    {{-- ════ TOP GAINERS + TOP TURNOVER ════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border rounded-xl overflow-hidden" style="border-color:#e2e8f0;">
            <div class="p-4 border-b flex justify-between items-center" style="border-color:#e2e8f0;background:#f8fafc;">
                <h3 class="text-base font-bold flex items-center gap-2" style="color:#0f172a;">
                    <span class="material-symbols-outlined" style="color:#16a34a;">trending_up</span> Top Gainers
                </h3>
                <a href="{{ route('stocks.index', ['view' => 'gainers']) }}" class="text-sm font-semibold" style="color:#14532D;">View All</a>
            </div>
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0;background:#fff;">
                        <th class="p-3 text-xs font-semibold" style="color:#94a3b8;">Symbol</th>
                        <th class="p-3 text-xs font-semibold text-right" style="color:#94a3b8;">LTP</th>
                        <th class="p-3 text-xs font-semibold text-right" style="color:#94a3b8;">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topGainers as $g)
                    <tr class="market-row transition-colors" style="border-bottom:1px solid #f1f5f9;">
                        <td class="p-3"><a href="{{ route('stocks.show', $g['symbol']) }}" class="font-bold" style="color:#0f172a;">{{ $g['symbol'] }}</a></td>
                        <td class="p-3 text-right font-mono">{{ number_format($g['ltp'], 2) }}</td>
                        <td class="p-3 text-right font-mono font-semibold change-pos">+{{ number_format($g['change'], 2) }} ({{ number_format($g['change_percent'], 2) }}%)</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="p-6 text-center" style="color:#94a3b8;">Live data unavailable.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white border rounded-xl overflow-hidden" style="border-color:#e2e8f0;">
            <div class="p-4 border-b flex justify-between items-center" style="border-color:#e2e8f0;background:#f8fafc;">
                <h3 class="text-base font-bold flex items-center gap-2" style="color:#0f172a;">
                    <span class="material-symbols-outlined" style="color:#64748b;">swap_horiz</span> Top Turnover
                </h3>
                <a href="{{ route('stocks.index', ['view' => 'turnover']) }}" class="text-sm font-semibold" style="color:#14532D;">View All</a>
            </div>
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0;background:#fff;">
                        <th class="p-3 text-xs font-semibold" style="color:#94a3b8;">Symbol</th>
                        <th class="p-3 text-xs font-semibold text-right" style="color:#94a3b8;">Turnover (Rs)</th>
                        <th class="p-3 text-xs font-semibold text-right" style="color:#94a3b8;">LTP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topTurnover as $t)
                    <tr class="market-row transition-colors" style="border-bottom:1px solid #f1f5f9;">
                        <td class="p-3"><a href="{{ route('stocks.show', $t['symbol']) }}" class="font-bold" style="color:#0f172a;">{{ $t['symbol'] }}</a></td>
                        <td class="p-3 text-right font-mono">{{ \App\Services\MarketFormatter::compactRupees($t['turnover']) }}</td>
                        <td class="p-3 text-right font-mono font-semibold {{ $t['change_percent'] >= 0 ? 'change-pos' : 'change-neg' }}">{{ number_format($t['ltp'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="p-6 text-center" style="color:#94a3b8;">Live data unavailable.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ════ FILTERS ════════════════════════════════════════════════════════ --}}
    <form method="GET" class="bg-white border rounded-xl p-4 flex flex-wrap gap-3 items-end" style="border-color:#e2e8f0;">
        <input type="hidden" name="view" value="{{ $view }}">
        <div>
            <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Symbol or name…" class="market-input" style="width:220px;">
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Sector</label>
            <select name="sector" class="market-input">
                <option value="">All Sectors</option>
                @foreach($sectors as $sectorName)
                    <option value="{{ $sectorName }}" {{ $sector == $sectorName ? 'selected' : '' }}>{{ $sectorName }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        @if(request()->hasAny(['search','sector']))
        <a href="{{ route('stocks.index', ['view' => $view]) }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    {{-- ════ FULL STOCK LIST ═══════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-xl overflow-hidden" style="border-color:#e2e8f0;">
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                    <th class="hidden sm:table-cell text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">#</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Symbol</th>
                    <th class="hidden md:table-cell text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Company</th>
                    <th class="hidden sm:table-cell text-left px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Change</th>
                    <th class="hidden lg:table-cell text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Volume</th>
                    <th class="hidden lg:table-cell text-right px-4 py-3 text-xs font-semibold uppercase tracking-wider" style="color:#64748b;">Turnover</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $i => $stock)
                @php $p = $stock->latestPrice; @endphp
                <tr class="market-row transition-colors" style="border-bottom:1px solid #f1f5f9;">
                    <td class="hidden sm:table-cell px-4 py-3" style="color:#94a3b8;">{{ $stocks->firstItem() + $i }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}" class="font-bold transition-colors" style="color:#0f172a;"
                           onmouseover="this.style.color='#14532D'" onmouseout="this.style.color='#0f172a'">
                            {{ $stock->symbol }}
                        </a>
                        <div class="sm:hidden" style="font-size:.7rem;color:#94a3b8;">{{ $stock->sector->name ?? '' }}</div>
                    </td>
                    <td class="hidden md:table-cell px-4 py-3" style="color:#64748b;">{{ Str::limit($stock->name, 30) }}</td>
                    <td class="hidden sm:table-cell px-4 py-3">
                        @if($stock->sector)
                        <span class="text-xs px-2 py-0.5 rounded" style="background:#DCFCE7;color:#14532D;">{{ $stock->sector->name }}</span>
                        @else
                        <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ $p ? number_format($p->close, 2) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        @if($p) {{ $p->change_percent >= 0 ? '+' : '' }}{{ number_format($p->change_percent, 2) }}% @else — @endif
                    </td>
                    <td class="hidden lg:table-cell text-right font-mono px-4 py-3" style="color:#64748b;">{{ $p ? number_format($p->volume) : '—' }}</td>
                    <td class="hidden lg:table-cell text-right font-mono px-4 py-3" style="color:#64748b;">{{ $p ? \App\Services\MarketFormatter::compactRupees($p->turnover) : '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center px-4 py-12" style="color:#94a3b8;">No stocks found matching your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $stocks->links() }}</div>

</div>
@endsection
