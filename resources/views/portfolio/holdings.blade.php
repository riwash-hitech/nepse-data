@extends('layouts.app')
@section('title', 'My Holdings')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">📊 My Holdings</h1>
        <a href="{{ route('portfolio.adjust') }}" class="btn-primary">+ Adjust Holdings</a>
    </div>

    {{-- Filter panel --}}
    <form method="GET" class="glass p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Sector</label>
                <select name="sector" class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                    <option value="">All Sectors</option>
                    @foreach($sectors as $s)
                    <option value="{{ $s }}" {{ ($filters['sector'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Company</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Symbol or name"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center">Search</button>
                @if(request()->hasAny(['sector','search']))
                <a href="{{ route('portfolio.holdings') }}" class="btn-ghost">✕</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Movement tabs --}}
    <div class="flex gap-2 text-sm">
        @foreach(['all' => 'All', 'gaining' => 'Gaining', 'losing' => 'Losing'] as $key => $label)
        <a href="{{ route('portfolio.holdings', array_merge(request()->except('movement'), $key === 'all' ? [] : ['movement' => $key])) }}"
           class="px-3 py-1.5 rounded-lg"
           style="background:{{ ($filters['movement'] ?? 'all') === $key ? '#eff6ff' : '#ffffff' }};
                  border:1px solid {{ ($filters['movement'] ?? 'all') === $key ? '#bfdbfe' : '#e2e8f0' }};
                  color:{{ ($filters['movement'] ?? 'all') === $key ? '#2563eb' : '#64748b' }};">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="glass overflow-hidden">
        <div class="px-4 py-3 border-b text-sm" style="border-color:#f1f5f9;color:#64748b;">
            {{ count($rows) }} holdings
        </div>
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Qty / Avg Cost</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP / Chg%</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Market Value</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Weight%</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Today's G/L</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Unrealized G/L</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $r['symbol']) }}" class="font-bold hover:text-blue-600 transition-colors" style="color:#0f172a;">
                            {{ $r['symbol'] }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($r['name'], 22) }}</div>
                        @if(!$r['has_live_price'])
                        <div class="text-xs" style="color:#d97706;">no live price — using cost</div>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">
                        {{ number_format($r['quantity']) }}
                        <div class="text-xs" style="color:#64748b;">{{ number_format($r['avg_cost'], 2) }}</div>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">
                        {{ number_format($r['ltp'], 2) }}
                        <div class="text-xs {{ $r['change_percent'] >= 0 ? 'change-pos' : 'change-neg' }}">
                            {{ $r['change_percent'] >= 0 ? '+' : '' }}{{ number_format($r['change_percent'], 2) }}%
                        </div>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['market_value'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#64748b;">
                        {{ $market_value > 0 ? number_format($r['market_value'] / $market_value * 100, 1) : 0 }}%
                    </td>
                    <td class="text-right font-mono px-4 py-3 {{ $r['day_gain_loss'] >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $r['day_gain_loss'] >= 0 ? '+' : '' }}{{ number_format($r['day_gain_loss'], 2) }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 {{ $r['unrealized_gain'] >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $r['unrealized_gain'] >= 0 ? '+' : '' }}{{ number_format($r['unrealized_gain'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center px-4 py-12" style="color:#94a3b8;">
                        No holdings match your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($rows))
            <tfoot>
                <tr style="border-top:2px solid #e2e8f0;font-weight:700;">
                    <td class="px-4 py-3" style="color:#0f172a;">Total</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3"></td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($market_value, 2) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="text-right font-mono px-4 py-3 {{ $day_gain_loss >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $day_gain_loss >= 0 ? '+' : '' }}{{ number_format($day_gain_loss, 2) }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 {{ $unrealized >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $unrealized >= 0 ? '+' : '' }}{{ number_format($unrealized, 2) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>

    @if(count($rows))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="glass p-4">
            <div class="text-sm font-semibold mb-3" style="color:#0f172a;">Stockwise Investment Weight %</div>
            <div style="position:relative;height:260px;">
                <canvas id="investWeightChart"></canvas>
            </div>
        </div>
        <div class="glass p-4">
            <div class="text-sm font-semibold mb-3" style="color:#0f172a;">Stockwise Market Value Weight %</div>
            <div style="position:relative;height:260px;">
                <canvas id="mvWeightChart"></canvas>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@if(count($rows))
@push('scripts')
<script>
const holdSymbols = @json(array_column($rows, 'symbol'));
const holdInvestment = @json(array_column($rows, 'investment'));
const holdMarketValue = @json(array_column($rows, 'market_value'));
const holdPalette = ['#3b82f6','#22c55e','#a855f7','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16','#6366f1','#f97316'];

const donutOpts = {
    plugins: { legend: { position: 'bottom', labels: { color: '#475569', boxWidth: 10, font: { size: 11 } } } }
};

new Chart(document.getElementById('investWeightChart'), {
    type: 'doughnut',
    data: { labels: holdSymbols, datasets: [{ data: holdInvestment, backgroundColor: holdPalette, borderWidth: 0 }] },
    options: donutOpts
});

new Chart(document.getElementById('mvWeightChart'), {
    type: 'doughnut',
    data: { labels: holdSymbols, datasets: [{ data: holdMarketValue, backgroundColor: holdPalette, borderWidth: 0 }] },
    options: donutOpts
});
</script>
@endpush
@endif
