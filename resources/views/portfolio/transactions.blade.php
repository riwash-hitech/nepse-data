@extends('layouts.app')
@section('title', 'Transaction History')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">🧾 Transaction History</h1>
        <a href="{{ route('portfolio.adjust') }}" class="btn-primary">+ Adjust Holdings</a>
    </div>

    <form method="GET" class="glass p-4">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Symbol</label>
                <input type="text" name="symbol" value="{{ $filters['symbol'] ?? '' }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">Type</label>
                <select name="type" class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                    <option value="">Any</option>
                    <option value="buy" {{ ($filters['type'] ?? '') === 'buy' ? 'selected' : '' }}>Buy</option>
                    <option value="sell" {{ ($filters['type'] ?? '') === 'sell' ? 'selected' : '' }}>Sell</option>
                </select>
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div>
                <label class="block text-xs mb-1" style="color:#64748b;">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center">Filter</button>
                @if(request()->hasAny(['symbol','type','from','to']))
                <a href="{{ route('portfolio.transactions') }}" class="btn-ghost">✕</a>
                @endif
            </div>
        </div>
    </form>

    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Type</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Quantity</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Rate</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Amount</th>
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
                    <td class="text-center px-4 py-3">
                        @if($t->type === 'buy')
                        <span class="badge-buy">BUY</span>
                        @else
                        <span class="badge-sell">SELL</span>
                        @endif
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($t->quantity) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($t->rate, 2) }}</td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ number_format($t->quantity * $t->rate, 2) }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $t->realized_gain === null ? '' : ($t->realized_gain >= 0 ? 'change-pos' : 'change-neg') }}">
                        {{ $t->realized_gain === null ? '—' : (($t->realized_gain >= 0 ? '+' : '') . number_format($t->realized_gain, 2)) }}
                    </td>
                    <td class="px-4 py-3" style="color:#64748b;">{{ $t->remarks ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center px-4 py-12" style="color:#94a3b8;">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div>{{ $transactions->links() }}</div>
</div>
@endsection
