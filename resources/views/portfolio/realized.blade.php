@extends('layouts.app')
@section('title', 'Realized Profit / Loss')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">💰 Realized Profit / Loss</h1>
        <a href="{{ route('portfolio.profit-loss') }}" class="btn-ghost">← Unrealized P/L</a>
    </div>

    <div class="glass p-4">
        <div class="text-xs mb-1" style="color:#64748b;">Total Realized Gain/Loss (all-time)</div>
        <div class="font-mono font-bold text-xl {{ $totalRealized >= 0 ? 'change-pos' : 'change-neg' }}">
            {{ $totalRealized >= 0 ? '+' : '' }}{{ number_format($totalRealized, 2) }}
        </div>
    </div>

    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Qty Sold</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Sell Rate</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Realized G/L</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3" style="color:#64748b;">{{ $t->txn_date->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $t->stock->symbol) }}" class="font-bold hover:text-blue-600 transition-colors" style="color:#0f172a;">{{ $t->stock->symbol }}</a>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($t->quantity) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($t->rate, 2) }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $t->realized_gain >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $t->realized_gain >= 0 ? '+' : '' }}{{ number_format($t->realized_gain, 2) }}
                    </td>
                    <td class="px-4 py-3" style="color:#64748b;">{{ $t->remarks ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center px-4 py-12" style="color:#94a3b8;">No sell transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div>{{ $transactions->links() }}</div>
</div>
@endsection
