@extends('layouts.app')
@section('title', 'Portfolio Overview')

@section('content')
<div class="space-y-5">

    @isset($viewingUser)
    <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Viewing <strong>{{ $viewingUser->name }}</strong>'s portfolio (read-only) ·
        <a href="{{ route('admin.users.index') }}" style="text-decoration:underline;">Back to Users</a>
    </div>
    @endisset

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">💼 Portfolio Overview</h1>
        @unless($readOnly ?? false)
        <a href="{{ route('portfolio.adjust') }}" class="btn-primary">+ Adjust Holdings</a>
        @endunless
    </div>

    @if($stock_count === 0)
    <div class="glass p-10 text-center">
        <div class="text-4xl mb-4">📭</div>
        <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No holdings yet</div>
        <div class="text-sm mb-6" style="color:#64748b;">
            @unless($readOnly ?? false)
            Add your first buy transaction to start tracking your portfolio.
            @else
            This user has no holdings yet.
            @endunless
        </div>
        @unless($readOnly ?? false)
        <a href="{{ route('portfolio.adjust') }}" class="btn-primary">Adjust Holdings</a>
        @endunless
    </div>
    @else

    {{-- Stat row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
            $stats = [
                ['Investment', $investment, false],
                ['Market Value', $market_value, false],
                ['Net Worth', $net_worth, false],
                ['Day Gain/Loss', $day_gain_loss, true],
                ['Unrealized G/L', $unrealized, true],
                ['Realized G/L', $realized, true],
            ];
        @endphp
        @foreach($stats as [$label, $val, $signed])
        <div class="glass p-4">
            <div class="text-xs mb-1" style="color:#64748b;">{{ $label }}</div>
            <div class="font-mono font-bold" style="font-size:1.05rem;color:{{ $signed ? ($val >= 0 ? '#16a34a' : '#dc2626') : '#0f172a' }};">
                {{ $signed && $val >= 0 ? '+' : '' }}{{ number_format($val, 2) }}
            </div>
            @if($signed)
            <div class="text-xs" style="color:{{ $val >= 0 ? '#16a34a' : '#dc2626' }};">
                ({{ $investment > 0 ? number_format($val / max($investment,1) * 100, 2) : 0 }}%)
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="glass px-4 py-3 text-xs" style="color:#64748b;">
        {{ $stock_count }} stocks · {{ number_format($total_shares) }} total shares
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="glass p-4">
            <div class="text-sm font-semibold mb-3" style="color:#0f172a;">Sector Allocation (Market Value)</div>
            <div style="position:relative;height:260px;">
                <canvas id="sectorChart"></canvas>
            </div>
        </div>
        <div class="glass p-4">
            <div class="text-sm font-semibold mb-3" style="color:#0f172a;">Top 10 Holdings — Investment vs Market Value</div>
            <div style="position:relative;height:260px;">
                <canvas id="topHoldingsChart"></canvas>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@if($stock_count > 0)
@push('scripts')
<script>
const sectorLabels = @json(array_keys($sector_totals));
const sectorValues = @json(array_values($sector_totals));
const palette = ['#3b82f6','#22c55e','#a855f7','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16','#6366f1','#f97316'];

new Chart(document.getElementById('sectorChart'), {
    type: 'doughnut',
    data: {
        labels: sectorLabels,
        datasets: [{ data: sectorValues, backgroundColor: palette, borderWidth: 0 }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: '#475569', boxWidth: 10, font: { size: 11 } } } }
    }
});

const topSymbols = @json(array_column($top_holdings, 'symbol'));
const topInvestment = @json(array_column($top_holdings, 'investment'));
const topMarketValue = @json(array_column($top_holdings, 'market_value'));

new Chart(document.getElementById('topHoldingsChart'), {
    type: 'bar',
    data: {
        labels: topSymbols,
        datasets: [
            { label: 'Investment', data: topInvestment, backgroundColor: '#3b82f6' },
            { label: 'Market Value', data: topMarketValue, backgroundColor: '#22c55e' },
        ]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            x: { ticks: { color: '#475569' }, grid: { color: 'rgba(0,0,0,0.05)' } },
            y: { ticks: { color: '#475569' }, grid: { color: 'rgba(0,0,0,0.05)' } },
        },
        plugins: { legend: { position: 'bottom', labels: { color: '#475569', boxWidth: 10, font: { size: 11 } } } }
    }
});
</script>
@endpush
@endif
