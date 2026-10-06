@extends('layouts.app')
@section('title', 'AI Top 10 Picks')

@push('head')
<style>
.ai-pick-card {
    background:#ffffff;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);transition:box-shadow 0.2s,transform 0.2s;
}
.ai-pick-card:hover { box-shadow:0 8px 28px rgba(124,58,237,0.12);transform:translateY(-2px); }
.ai-rank-1 { background:linear-gradient(135deg,#f59e0b,#fbbf24); color:#78350f; }
.ai-rank-2 { background:linear-gradient(135deg,#94a3b8,#cbd5e1); color:#1e293b; }
.ai-rank-3 { background:linear-gradient(135deg,#b45309,#d97706); color:#fff; }
.ai-rank-rest { background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; }
.ai-stat-cell {
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.625rem;padding:0.625rem 0.75rem;text-align:center;
}
</style>
@endpush

@section('content')

<div class="mb-8 p-6 sm:p-10 rounded-2xl" style="background:linear-gradient(135deg,#0f172a 0%,#2e1065 50%,#0f172a 100%);">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold tracking-widest uppercase" style="color:#c4b5fd;">AI-Curated</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1" style="color:#f1f5f9;">AI Top 10 Picks</h1>
            <p style="color:#94a3b8;font-size:0.9rem;max-width:40rem;">
                A rule-based BUY screen builds the shortlist — an AI model then independently ranks and reasons
                about the real top picks from it, rather than just echoing the rule-based score.
            </p>
        </div>

        <div class="flex flex-col gap-2 items-start sm:items-end shrink-0">
            <div class="text-xs" style="color:#a78bfa;">⚡ Cached 1 hour</div>
            <form method="POST" action="{{ route('ai-picks.refresh') }}">
                @csrf
                <button type="submit"
                    onclick="this.disabled=true;this.innerHTML='<span>Refreshing…</span>'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.15);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-run AI Analysis
                </button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-6 px-4 py-3 rounded-lg text-sm" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;">
    {{ session('success') }}
</div>
@endif

@if(empty($picks))
<div class="glass p-10 text-center">
    <div class="text-4xl mb-4">🤖</div>
    <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No AI picks available right now</div>
    <div class="text-sm mb-6" style="color:#64748b;">
        Either no active BUY signals exist for the latest trading date yet, or the AI provider
        (see <code>AI_PROVIDER</code> / API key in .env) didn't return a usable response.
    </div>
    <form method="POST" action="{{ route('ai-picks.refresh') }}">
        @csrf
        <button class="btn-primary">Try Again</button>
    </form>
</div>
@else

@if(!($picks[0]['ai_generated'] ?? true))
<div class="mb-6 p-4 rounded-xl text-xs" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;">
    <strong>⚠ AI unavailable:</strong> showing the top rule-based BUY candidates as a fallback —
    these are not yet AI-reasoned picks.
</div>
@endif

<div class="space-y-5">
@foreach($picks as $i => $pick)
@php
    $rank = $i + 1;
    $rankCls = $rank === 1 ? 'ai-rank-1' : ($rank === 2 ? 'ai-rank-2' : ($rank === 3 ? 'ai-rank-3' : 'ai-rank-rest'));
    $conf = $pick['confidence'] ?? 0;
    $confColor = $conf >= 75 ? '#15803d' : ($conf >= 55 ? '#1d4ed8' : '#92400e');
@endphp
<div class="ai-pick-card">
    <div class="flex items-start justify-between p-5 border-b" style="border-color:#f1f5f9;">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-lg shrink-0 {{ $rankCls }}">
                {{ $rank }}
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('stocks.show', $pick['symbol']) }}"
                       class="text-xl font-extrabold hover:text-purple-600 transition-colors"
                       style="color:#0f172a;font-family:'JetBrains Mono',monospace;">
                        {{ $pick['symbol'] }}
                    </a>
                    @if($pick['ai_generated'] ?? false)
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold" style="background:#ede9fe;color:#7c3aed;border:1px solid #ddd6fe;">
                        ✨ AI Pick
                    </span>
                    @endif
                </div>
                <div class="text-sm mt-0.5" style="color:#64748b;">
                    {{ Str::limit($pick['name'] ?? $pick['symbol'], 45) }}
                    @if(!empty($pick['sector']))
                    · <span style="color:#7c3aed;">{{ $pick['sector'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-right shrink-0">
            <div class="text-xs mb-1" style="color:#94a3b8;">Confidence</div>
            <div class="text-lg font-extrabold" style="color:{{ $confColor }};">{{ $conf }}%</div>
        </div>
    </div>

    <div class="p-5 space-y-4">
        <div class="flex flex-wrap gap-2 items-center">
            <div class="ai-stat-cell" style="min-width:120px;">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">
                    {{ ($pick['price_is_live'] ?? false) ? 'Live Price' : 'Price' }}
                </div>
                <div class="font-extrabold" style="font-size:1rem;color:#0f172a;font-family:'JetBrains Mono',monospace;">
                    NPR {{ number_format($pick['price'] ?? 0, 2) }}
                </div>
                @if(!($pick['price_is_live'] ?? false))
                <div class="text-xs mt-0.5" style="color:#d97706;">⚠ not live</div>
                @endif
            </div>
            @if(($pick['rsi'] ?? null) !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium" style="background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;">
                RSI {{ number_format($pick['rsi'], 1) }}
            </span>
            @endif
            @if(($pick['macd_hist'] ?? null) !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:{{ $pick['macd_hist'] > 0 ? '#f0fdf4' : '#fef2f2' }};
                         border:1px solid {{ $pick['macd_hist'] > 0 ? '#bbf7d0' : '#fecaca' }};
                         color:{{ $pick['macd_hist'] > 0 ? '#15803d' : '#dc2626' }};">
                MACD {{ $pick['macd_hist'] > 0 ? '▲' : '▼' }} {{ number_format($pick['macd_hist'], 2) }}
            </span>
            @endif
        </div>

        @if(!empty($pick['reason']))
        <div class="p-3 rounded-lg text-sm" style="background:#faf5ff;border:1px solid #e9d5ff;color:#4c1d95;">
            <span class="font-semibold">✨ AI take:</span> {{ $pick['reason'] }}
        </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="ai-stat-cell">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;text-transform:uppercase;">Entry Zone</div>
                <div class="font-bold text-sm" style="color:#1d4ed8;font-family:'JetBrains Mono',monospace;">
                    @if(($pick['entry_min'] ?? null) !== null && ($pick['entry_max'] ?? null) !== null)
                        {{ number_format($pick['entry_min'], 2) }}–{{ number_format($pick['entry_max'], 2) }}
                    @else — @endif
                </div>
            </div>
            <div class="ai-stat-cell">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;text-transform:uppercase;">Target</div>
                <div class="font-bold text-sm" style="color:#15803d;font-family:'JetBrains Mono',monospace;">
                    {{ ($pick['target_price'] ?? null) !== null ? number_format($pick['target_price'], 2) : '—' }}
                </div>
            </div>
            <div class="ai-stat-cell">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;text-transform:uppercase;">Stop Loss</div>
                <div class="font-bold text-sm" style="color:#dc2626;font-family:'JetBrains Mono',monospace;">
                    {{ ($pick['stop_loss'] ?? null) !== null ? number_format($pick['stop_loss'], 2) : '—' }}
                </div>
            </div>
            <a href="{{ route('stocks.show', $pick['symbol']) }}" class="btn-primary justify-center" style="padding:0.5rem;">
                Full Analysis
            </a>
        </div>

        @if(!empty($pick['reasons']))
        <div>
            <div class="text-xs font-semibold mb-2 uppercase tracking-wide" style="color:#94a3b8;">
                Rule-based signal reasons
                @if(!empty($pick['signal_date']))
                <span style="text-transform:none;font-weight:400;color:#cbd5e1;">· as of {{ $pick['signal_date'] }}</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach($pick['reasons'] as $r)
                <span class="text-xs px-2 py-1 rounded-md" style="background:#f8fafc;border:1px solid #f1f5f9;color:#475569;">{{ $r }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endforeach
</div>

<div class="mt-8 p-4 rounded-xl text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
    <strong>⚠ Disclaimer:</strong> These picks combine a rule-based technical screen with an AI model's independent
    read of the same data. They do not constitute financial advice — always do your own research and consider your
    risk tolerance before investing.
</div>

@endif

@endsection
