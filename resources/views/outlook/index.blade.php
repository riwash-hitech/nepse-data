@extends('layouts.app')
@section('title', '30-Day Outlook')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">📅 30-Day Outlook</h1>
            <p class="text-sm mt-1" style="color:#64748b;">
                Stocks ranked by a log-linear trend fit over recent price history, projected forward
                30 calendar days and confidence-weighted by how reliable that trend actually is.
            </p>
        </div>
        <div class="flex flex-col items-start sm:items-end gap-1 shrink-0">
            <form method="POST" action="{{ route('outlook.refresh') }}">
                @csrf
                <button type="submit"
                    onclick="this.disabled=true;this.innerHTML='Refreshing…'"
                    class="btn-ghost text-sm">
                    ↻ Re-analyse all stocks
                </button>
            </form>
            <span class="text-xs" style="color:#475569;">Cached 1 hour · updated {{ now()->format('d M Y, H:i') }} NPT</span>
        </div>
    </div>

    @if(session('success'))
    <div class="px-4 py-3 rounded-lg text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#4ade80;">
        {{ session('success') }}
    </div>
    @endif

    @if(empty($outlook))
    <div class="glass p-10 text-center">
        <div class="text-4xl mb-4">🔍</div>
        <div class="font-semibold text-lg mb-2 text-white">No confident 30-day trends found</div>
        <div class="text-sm mb-6" style="color:#64748b;">
            Either there isn't enough liquid trading history yet, or no stock currently has a
            trend fit strong enough to clear the minimum confidence bar.
        </div>
    </div>
    @else

    <div class="glass overflow-hidden">
        <div class="px-4 py-3 border-b text-sm flex items-center justify-between" style="border-color:rgba(255,255,255,0.06);color:#64748b;">
            <span>{{ count($outlook) }} stocks with a reliable 30-day trend</span>
            <span class="text-xs">Ranked by expected return × confidence</span>
        </div>
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06);">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">#</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Current</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">30D Target</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Range</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Expected</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Confidence</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">R²</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outlook as $i => $o)
                @php
                    $dirColor = $o['direction'] === 'up' ? '#4ade80' : ($o['direction'] === 'down' ? '#f87171' : '#94a3b8');
                    $confColor = $o['confidence'] >= 65 ? '#4ade80' : ($o['confidence'] >= 45 ? '#facc15' : '#f87171');
                @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);" class="hover:bg-white/3 transition-colors">
                    <td class="px-4 py-3" style="color:#475569;">{{ $i + 1 }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $o['symbol']) }}"
                           class="font-bold text-white hover:text-blue-400 transition-colors">
                            {{ $o['symbol'] }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#475569;">{{ Str::limit($o['name'], 22) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded" style="background:rgba(99,102,241,0.1);color:#a5b4fc;">
                            {{ $o['sector'] }}
                        </span>
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-white">{{ number_format($o['current_price'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3 font-bold" style="color:{{ $dirColor }};">
                        {{ number_format($o['target_price'], 2) }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-xs" style="color:#64748b;">
                        {{ number_format($o['target_low'], 0) }}–{{ number_format($o['target_high'], 0) }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 font-bold" style="color:{{ $dirColor }};">
                        {{ $o['expected_return_pct'] >= 0 ? '+' : '' }}{{ number_format($o['expected_return_pct'], 1) }}%
                    </td>
                    <td class="text-right px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded font-semibold" style="background:rgba(255,255,255,0.05);color:{{ $confColor }};">
                            {{ $o['confidence'] }}%
                        </span>
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-xs" style="color:#64748b;">{{ $o['r_squared'] }}</td>
                    <td class="text-center px-4 py-3">
                        <a href="{{ route('stocks.show', $o['symbol']) }}"
                           class="text-xs px-3 py-1 rounded-md transition-colors"
                           style="background:rgba(37,99,235,0.15);color:#60a5fa;border:1px solid rgba(37,99,235,0.2);">
                            Analyse
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    <div class="p-4 rounded-xl text-xs" style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);color:#fbbf24;">
        <strong>⚠ Disclaimer:</strong> This is a statistical trend projection (log-linear regression over recent
        price history), not a guarantee. NEPSE stocks are volatile and 30 days is long enough for the underlying
        trend to reverse. Confidence reflects how well price has followed a straight trend recently — not certainty
        about the future. Always do your own research.
    </div>

</div>
@endsection
