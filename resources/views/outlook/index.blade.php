@extends('layouts.app')
@section('title', 'Top 1-Month Return')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold" style="color:#0f172a;">🚀 Top 1-Month Return Prediction</h1>
            <p class="text-sm mt-1" style="color:#64748b;">
                Stocks predicted to gain over the next 30 days, based on a trend fit over recent price
                history. Only stocks with a positive predicted return are listed — ranked highest first.
            </p>
        </div>
        <div class="flex flex-col items-start sm:items-end gap-1 shrink-0">
            <form method="POST" action="{{ route('outlook.generate') }}">
                @csrf
                <button type="submit"
                    onclick="this.disabled=true;this.innerHTML='Generating… (can take a minute)';this.form.submit();return false;"
                    class="btn-primary text-sm">
                    ⚡ Generate 1-Month Prediction
                </button>
            </form>
            @if($generatedAt)
            <span class="text-xs" style="color:#94a3b8;">Last generated {{ $generatedAt->diffForHumans() }} · valid 1 hour</span>
            @endif
        </div>
    </div>

    @if(is_null($outlook))
    <div class="glass p-10 text-center">
        <div class="text-4xl mb-4">📊</div>
        <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No prediction generated yet</div>
        <div class="text-sm mb-2" style="color:#64748b;">
            Click <strong>Generate 1-Month Prediction</strong> above to scan all active stocks and rank them
            by predicted return over the next 30 days.
        </div>
        <div class="text-xs" style="color:#94a3b8;">
            This scans ~200 stocks against the live market API, so it can take up to a minute.
        </div>
    </div>
    @elseif(empty($outlook))
    <div class="glass p-10 text-center">
        <div class="text-4xl mb-4">📉</div>
        <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No stocks are currently predicted to gain</div>
        <div class="text-sm mb-2" style="color:#64748b;">
            The market trend data right now doesn't show any stock with a reliable, positive 30-day
            trend — every candidate that cleared the confidence bar is trending down.
        </div>
        <div class="text-xs" style="color:#94a3b8;">
            This isn't an error — it reflects a broadly weak/declining market. Try again later, or check
            the <a href="{{ route('screener.index') }}" style="color:#2563eb;">Screener</a> for individual stock signals.
        </div>
    </div>
    @else

    <div class="glass overflow-hidden">
        <div class="px-4 py-3 border-b text-sm flex items-center justify-between flex-wrap gap-2" style="border-color:#e2e8f0;color:#64748b;">
            <span>{{ count($outlook) }} stocks predicted to clear the confidence bar for a reliable 30-day trend</span>
            <div class="flex items-center gap-3">
                <span class="text-xs">Ranked by predicted return — highest first</span>
                <a href="{{ route('outlook.export') }}" class="btn-ghost text-xs" style="padding:0.375rem 0.75rem;">
                    ⬇ Export to Excel
                </a>
            </div>
        </div>
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">#</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Sector</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Current</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">30D Target</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Range</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Predicted Return</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Confidence</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">R²</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outlook as $i => $o)
                @php
                    $dirColor = $o['direction'] === 'up' ? '#16a34a' : ($o['direction'] === 'down' ? '#dc2626' : '#64748b');
                    $confColor = $o['confidence'] >= 65 ? '#16a34a' : ($o['confidence'] >= 45 ? '#ca8a04' : '#dc2626');
                    $rankBg = $i === 0 ? '#fef9c3' : ($i === 1 ? '#f1f5f9' : ($i === 2 ? '#fef3e2' : 'transparent'));
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;background:{{ $i < 3 ? $rankBg : 'transparent' }};" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-bold" style="color:{{ $i < 3 ? '#0f172a' : '#94a3b8' }};">{{ $i + 1 }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $o['symbol']) }}"
                           class="font-bold hover:text-blue-600 transition-colors" style="color:#0f172a;">
                            {{ $o['symbol'] }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($o['name'], 22) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded" style="background:#eef2ff;color:#4338ca;">
                            {{ $o['sector'] }}
                        </span>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($o['current_price'], 2) }}</td>
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
                        <span class="text-xs px-2 py-0.5 rounded font-semibold" style="background:#f8fafc;border:1px solid #e2e8f0;color:{{ $confColor }};">
                            {{ $o['confidence'] }}%
                        </span>
                    </td>
                    <td class="text-right font-mono px-4 py-3 text-xs" style="color:#64748b;">{{ $o['r_squared'] }}</td>
                    <td class="text-center px-4 py-3">
                        <a href="{{ route('stocks.show', $o['symbol']) }}"
                           class="text-xs px-3 py-1 rounded-md transition-colors"
                           style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">
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

    <div class="p-4 rounded-xl text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
        <strong>⚠ Disclaimer:</strong> This is a statistical trend projection (log-linear regression over recent
        price history), not a guarantee of future profit. NEPSE stocks are volatile and 30 days is long enough for
        the underlying trend to reverse. Confidence reflects how well price has followed a straight trend recently —
        not certainty about the future. Always do your own research before investing.
    </div>

</div>
@endsection
