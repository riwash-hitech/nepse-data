@extends('layouts.app')
@section('title', 'Oversold Stocks — Lowest RSI')

@section('content')

<div class="mb-8 p-6 sm:p-8 rounded-2xl" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#0f172a 100%);">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#2563eb,#0ea5e9);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold tracking-widest uppercase" style="color:#7dd3fc;">RSI Screener</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1" style="color:#f1f5f9;">Top {{ $limit }} Oversold Stocks</h1>
            <p style="color:#94a3b8;font-size:0.9rem;max-width:36rem;">
                Ranked by 14-day RSI, lowest first — the stocks currently most oversold on NEPSE and the most likely candidates for a mean-reversion bounce.
            </p>
        </div>
        <div class="text-xs shrink-0 text-right" style="color:#60a5fa;">
            Last trading date: {{ \Illuminate\Support\Facades\Cache::get('latest_trading_date') ?? '—' }}<br>
            ⚡ Cached 15 min
        </div>
    </div>

    <div class="flex items-center gap-2 mt-5">
        <span class="text-xs font-semibold uppercase tracking-wide" style="color:#64748b;">Show:</span>
        @foreach($limits as $opt)
        <a href="{{ route('oversold.index', ['limit' => $opt]) }}"
           class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
           style="{{ $opt === $limit
                ? 'background:#2563eb;color:#fff;'
                : 'background:rgba(255,255,255,0.08);color:#cbd5e1;border:1px solid rgba(255,255,255,0.12);' }}">
            {{ $opt }}
        </a>
        @endforeach
    </div>
</div>

@if(empty($stocks))
<div class="glass p-10 text-center">
    <div class="text-4xl mb-4">🔍</div>
    <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No RSI data yet</div>
    <div class="text-sm" style="color:#64748b;">Indicators haven't been computed for the latest trading date yet. Try again after the next sync.</div>
</div>
@else

<div class="overflow-x-auto rounded-xl border" style="border-color:#e2e8f0;">
    <table class="w-full text-sm" style="border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th class="text-left px-4 py-3 font-semibold" style="color:#64748b;">#</th>
                <th class="text-left px-4 py-3 font-semibold" style="color:#64748b;">Symbol</th>
                <th class="text-left px-4 py-3 font-semibold" style="color:#64748b;">Sector</th>
                <th class="text-right px-4 py-3 font-semibold" style="color:#64748b;">RSI (14)</th>
                <th class="text-right px-4 py-3 font-semibold" style="color:#64748b;">LTP</th>
                <th class="text-right px-4 py-3 font-semibold" style="color:#64748b;">Change %</th>
                <th class="text-right px-4 py-3 font-semibold" style="color:#64748b;">SMA20</th>
                <th class="text-center px-4 py-3 font-semibold" style="color:#64748b;">Signal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($stocks as $i => $stock)
        @php
            $rsi = (float) $stock['rsi'];
            $rsiColor = $rsi < 20 ? '#dc2626' : ($rsi < 30 ? '#ea580c' : '#ca8a04');
            $rsiBg    = $rsi < 20 ? '#fef2f2' : ($rsi < 30 ? '#fff7ed' : '#fefce8');
            $chg = (float) $stock['last_change_percent'];
            $sigType = $stock['signal_type'];
            $bounce = $stock['bounce'] ?? null;
        @endphp
        <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50">
            <td class="px-4 py-3" style="color:#94a3b8;">{{ $i + 1 }}</td>
            <td class="px-4 py-3">
                <a href="{{ route('stocks.show', $stock['symbol']) }}" class="font-bold hover:text-blue-600" style="color:#0f172a;font-family:'JetBrains Mono',monospace;">
                    {{ $stock['symbol'] }}
                </a>
                <div class="text-xs" style="color:#94a3b8;">{{ \Illuminate\Support\Str::limit($stock['name'], 28) }}</div>
            </td>
            <td class="px-4 py-3" style="color:#64748b;">{{ $stock['sector'] ?? '—' }}</td>
            <td class="px-4 py-3 text-right">
                <span class="inline-block px-2.5 py-1 rounded-full font-bold" style="background:{{ $rsiBg }};color:{{ $rsiColor }};font-family:'JetBrains Mono',monospace;">
                    {{ number_format($rsi, 1) }}
                </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold" style="color:#0f172a;font-family:'JetBrains Mono',monospace;">
                {{ number_format($stock['last_close'], 2) }}
            </td>
            <td class="px-4 py-3 text-right font-semibold" style="color:{{ $chg >= 0 ? '#15803d' : '#dc2626' }};">
                {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 2) }}%
            </td>
            <td class="px-4 py-3 text-right" style="color:#64748b;font-family:'JetBrains Mono',monospace;">
                {{ $stock['sma_20'] !== null ? number_format($stock['sma_20'], 2) : '—' }}
            </td>
            <td class="px-4 py-3 text-center">
                @if($sigType)
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                      style="background:{{ $sigType === 'BUY' ? '#f0fdf4' : ($sigType === 'SELL' ? '#fef2f2' : '#f8fafc') }};
                             color:{{ $sigType === 'BUY' ? '#15803d' : ($sigType === 'SELL' ? '#dc2626' : '#64748b') }};">
                    {{ $sigType }}
                </span>
                @else
                <span style="color:#cbd5e1;">—</span>
                @endif
            </td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;background:#fafbfc;">
            <td></td>
            <td colspan="7" class="px-4 py-3">
                @if($bounce)
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs" style="color:#475569;">
                    <span>
                        <strong style="color:#0f172a;">Last bounce:</strong>
                        {{ number_format($bounce['last_low_price'], 2) }} ({{ \Illuminate\Support\Carbon::parse($bounce['last_low_date'])->format('d M') }})
                        → {{ number_format($bounce['last_bounce_price'], 2) }} ({{ \Illuminate\Support\Carbon::parse($bounce['last_bounce_date'])->format('d M') }})
                        <span style="color:#15803d;font-weight:700;">+{{ $bounce['last_bounce_pct'] }}%</span>
                        in {{ $bounce['last_bounce_days'] }} sessions
                    </span>
                    <span>
                        <strong style="color:#0f172a;">History:</strong>
                        {{ $bounce['episodes_count'] }} episode{{ $bounce['episodes_count'] > 1 ? 's' : '' }},
                        avg <span style="font-weight:700;color:#1d4ed8;">+{{ $bounce['avg_bounce_pct'] }}%</span>,
                        best <span style="font-weight:700;color:#7e22ce;">+{{ $bounce['max_bounce_pct'] }}%</span>
                    </span>
                    <span>
                        <strong style="color:#0f172a;">Could bounce to:</strong>
                        <span style="font-weight:700;color:#0369a1;font-family:'JetBrains Mono',monospace;">
                            {{ number_format($bounce['projected_target_low'], 2) }} – {{ number_format($bounce['projected_target_high'], 2) }}
                        </span>
                    </span>
                    @if($bounce['is_ongoing'])
                    <span style="color:#ca8a04;">⚠ current oversold episode still in progress — history above is from prior episodes</span>
                    @endif

                    <button type="button"
                            class="ai-insight-btn ml-auto text-xs font-semibold px-3 py-1 rounded-full"
                            data-symbol="{{ $stock['symbol'] }}"
                            style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;">
                        ✨ AI Take
                    </button>
                </div>
                <div class="ai-insight-text mt-2 text-xs italic" style="color:#6366f1;display:none;"></div>
                @else
                <div class="flex items-center justify-between gap-3 text-xs" style="color:#94a3b8;">
                    <span>No historical oversold episode found in this stock's available price history — no bounce precedent to compare against.</span>
                    <button type="button"
                            class="ai-insight-btn text-xs font-semibold px-3 py-1 rounded-full shrink-0"
                            data-symbol="{{ $stock['symbol'] }}"
                            style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;">
                        ✨ AI Take
                    </button>
                </div>
                <div class="ai-insight-text mt-2 text-xs italic" style="color:#6366f1;display:none;"></div>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6 p-4 rounded-xl text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
    <strong>⚠ Note:</strong> A low RSI means a stock has fallen sharply and may be "oversold" — it does not by itself mean the stock
    will bounce back. Past bounce percentages and the AI take are both based on the stock's own price history and are not guarantees.
    Always check the Technical Signal and trend direction on the stock's detail page before acting.
</div>

@endif

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.ai-insight-btn');
    if (!btn) return;

    const symbol = btn.dataset.symbol;
    const textEl = btn.closest('td').querySelector('.ai-insight-text');

    btn.disabled = true;
    btn.textContent = 'Thinking…';
    textEl.style.display = 'block';
    textEl.textContent = '';

    fetch(`/oversold/${symbol}/ai-insight`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            textEl.textContent = data.insight;
            btn.remove();
        })
        .catch(() => {
            textEl.textContent = 'Could not load AI take right now.';
            btn.disabled = false;
            btn.textContent = '✨ AI Take';
        });
});
</script>
@endpush

@endsection
