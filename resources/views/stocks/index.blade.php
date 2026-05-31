@extends('layouts.app')
@section('title', 'Markets')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Markets</h1>
    </div>

    {{-- Filters --}}
    <form method="GET" class="glass p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs mb-1" style="color:#64748b;">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Symbol or name…"
                   class="px-3 py-2 text-sm rounded-lg"
                   style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;width:220px;">
        </div>
        <div>
            <label class="block text-xs mb-1" style="color:#64748b;">Sector</label>
            <select name="sector"
                    class="px-3 py-2 text-sm rounded-lg"
                    style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
                <option value="">All Sectors</option>
                @foreach($sectors as $sectorName)
                    <option value="{{ $sectorName }}" {{ request('sector') == $sectorName ? 'selected' : '' }}>
                        {{ $sectorName }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        @if(request()->hasAny(['search','sector']))
        <a href="{{ route('stocks.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="glass overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">#</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Company</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Change</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Volume</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Turnover</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Signal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $i => $stock)
                @php $p = $stock->latestPrice; @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);" class="hover:bg-white/3 transition-colors">
                    <td class="px-4 py-3" style="color:#475569;">{{ $stocks->firstItem() + $i }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $stock->symbol) }}"
                           class="font-bold text-white hover:text-blue-400 transition-colors">
                            {{ $stock->symbol }}
                        </a>
                    </td>
                    <td class="px-4 py-3" style="color:#94a3b8;">{{ Str::limit($stock->name, 30) }}</td>
                    <td class="px-4 py-3">
                        @if($stock->sector)
                        <span class="text-xs px-2 py-0.5 rounded"
                              style="background:rgba(99,102,241,0.1);color:#a5b4fc;">
                            {{ $stock->sector->name }}
                        </span>
                        @else
                        <span style="color:#334155;">—</span>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-white">
                        {{ $p ? number_format($p->close, 2) : '—' }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        @if($p)
                            {{ $p->change_percent >= 0 ? '+' : '' }}{{ number_format($p->change_percent, 2) }}%
                        @else —
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#94a3b8;">
                        {{ $p ? number_format($p->volume) : '—' }}
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#94a3b8;">
                        {{ $p ? 'NPR ' . number_format($p->turnover / 1000, 0) . 'K' : '—' }}
                    </td>
                    <td class="text-center px-4 py-3">
                        @php $sig = $stock->latestSignal; @endphp
                        @if($sig)
                            @if($sig->signal_type === 'BUY')  <span class="badge-buy">BUY</span>
                            @elseif($sig->signal_type === 'SELL') <span class="badge-sell">SELL</span>
                            @else <span class="badge-hold">HOLD</span>
                            @endif
                        @else
                            <span style="color:#334155;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center px-4 py-12" style="color:#475569;">
                        No stocks found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>{{ $stocks->links() }}</div>

</div>
@endsection
