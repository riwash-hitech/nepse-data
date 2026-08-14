@extends('layouts.app')
@section('title', 'Net Profit / Loss')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">📈 Net Profit / Loss (Unrealized)</h1>
        <a href="{{ route('portfolio.realized') }}" class="btn-ghost">View Realized P/L →</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div class="glass p-4">
            <div class="text-xs mb-1" style="color:#64748b;">Total Investment</div>
            <div class="font-mono font-bold text-lg" style="color:#0f172a;">{{ number_format($investment, 2) }}</div>
        </div>
        <div class="glass p-4">
            <div class="text-xs mb-1" style="color:#64748b;">Total Market Value</div>
            <div class="font-mono font-bold text-lg" style="color:#0f172a;">{{ number_format($market_value, 2) }}</div>
        </div>
        <div class="glass p-4">
            <div class="text-xs mb-1" style="color:#64748b;">Unrealized Gain/Loss</div>
            <div class="font-mono font-bold text-lg {{ $unrealized >= 0 ? 'change-pos' : 'change-neg' }}">
                {{ $unrealized >= 0 ? '+' : '' }}{{ number_format($unrealized, 2) }}
                ({{ $investment > 0 ? number_format($unrealized / $investment * 100, 2) : 0 }}%)
            </div>
        </div>
    </div>

    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Qty</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Avg Cost</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Investment</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Market Value</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Gain/Loss</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Gain/Loss %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($holdings as $r)
                @php $pct = $r['investment'] > 0 ? $r['unrealized_gain'] / $r['investment'] * 100 : 0; @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $r['symbol']) }}" class="font-bold hover:text-blue-600 transition-colors" style="color:#0f172a;">{{ $r['symbol'] }}</a>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['quantity']) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['avg_cost'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['ltp'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['investment'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($r['market_value'], 2) }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $r['unrealized_gain'] >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $r['unrealized_gain'] >= 0 ? '+' : '' }}{{ number_format($r['unrealized_gain'], 2) }}
                    </td>
                    <td class="text-right font-mono px-4 py-3 {{ $pct >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $pct >= 0 ? '+' : '' }}{{ number_format($pct, 2) }}%
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center px-4 py-12" style="color:#94a3b8;">No holdings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
