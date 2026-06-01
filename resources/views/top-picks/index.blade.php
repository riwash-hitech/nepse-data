@extends('layouts.app')
@section('title', 'Top 5 Stock Picks')

@push('head')
<style>
/* ── Page header gradient ── */
.picks-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    border-radius: 1.25rem;
    position: relative;
    overflow: hidden;
}
.picks-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 60% at 20% 50%, rgba(99,102,241,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 50% 80% at 80% 20%, rgba(16,185,129,0.15) 0%, transparent 60%);
    pointer-events: none;
}
/* ── Pick card ── */
.pick-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s, transform 0.2s;
}
.pick-card:hover {
    box-shadow: 0 8px 32px rgba(37,99,235,0.1);
    transform: translateY(-2px);
}
/* ── Rank badge ── */
.rank-1 { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #78350f; }
.rank-2 { background: linear-gradient(135deg, #94a3b8, #cbd5e1); color: #1e293b; }
.rank-3 { background: linear-gradient(135deg, #b45309, #d97706); color: #fff; }
.rank-4, .rank-5 { background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; }
/* ── Confidence arc bar ── */
.conf-ring {
    position: relative;
    width: 72px; height: 72px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.conf-ring svg { position: absolute; inset: 0; transform: rotate(-90deg); }
.conf-text { font-size: 1.05rem; font-weight: 800; color: #0f172a; z-index: 1; line-height: 1; }
.conf-text small { font-size: 0.6rem; color: #64748b; display: block; text-align: center; }
/* ── Price grid ── */
.price-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}
@media (min-width: 640px) {
    .price-grid { grid-template-columns: repeat(4, 1fr); }
}
.price-cell {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.625rem;
    padding: 0.625rem 0.75rem;
    text-align: center;
}
/* ── Target bars ── */
.target-bar { height: 6px; border-radius: 9999px; background: #e2e8f0; position: relative; overflow: hidden; }
.target-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.8s ease; }
/* ── Reason badge ── */
.reason-row {
    display: flex; align-items: flex-start; gap: 0.5rem;
    padding: 0.5rem 0.625rem;
    border-radius: 0.5rem;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    font-size: 0.8125rem;
    color: #374151;
    line-height: 1.4;
}
/* ── Upside badge ── */
.upside-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.25rem 0.75rem; border-radius: 9999px;
    font-size: 0.8125rem; font-weight: 700;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #15803d;
    border: 1px solid #86efac;
}
/* ── Skeleton loader ── */
@keyframes shimmer {
    0% { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}
.skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400px 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 0.5rem;
}
/* ── Loading overlay ── */
#loadingOverlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 1.25rem;
}
.spinner {
    width: 52px; height: 52px;
    border: 4px solid #e2e8f0;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')

{{-- Loading overlay (shown while page loads on first visit) --}}
@if(empty($picks))
<div id="loadingOverlay">
    <div class="spinner"></div>
    <div style="font-weight:600;color:#0f172a;font-size:1rem;">Analysing all stocks…</div>
    <div style="font-size:0.875rem;color:#64748b;">Scanning 200+ stocks for uptrend signals.<br>This takes 30–60 seconds on first run.</div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  HERO                                                       --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="picks-hero p-6 sm:p-10 mb-8">
    <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background:linear-gradient(135deg,#4f46e5,#10b981);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold tracking-widest uppercase"
                      style="color:#a5b4fc;">AI-Powered Picks</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1" style="color:#f1f5f9;">
                Top 5 Stocks — Best Uptrend
            </h1>
            <p style="color:#94a3b8;font-size:0.9rem;max-width:36rem;">
                Scanned 200+ NEPSE stocks using RSI, MACD, SMA alignment, volume surge &amp;
                Bollinger Band signals. Ranked by composite uptrend score.
            </p>
        </div>

        <div class="flex flex-col gap-2 items-start sm:items-end shrink-0">
            <div class="text-xs" style="color:#6366f1;">
                Last updated: {{ now()->format('d M Y, H:i') }} NPT
            </div>
            <form method="POST" action="{{ route('top-picks.refresh') }}">
                @csrf
                <button type="submit"
                    onclick="this.disabled=true;this.innerHTML='<span>Refreshing…</span>'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.15);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-analyse all stocks
                </button>
            </form>
            <div class="text-xs" style="color:#6366f1;">⚡ Cached 30 min · Click to force refresh</div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="mb-6 px-4 py-3 rounded-lg text-sm"
     style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;">
    {{ session('success') }}
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  NO DATA STATE                                              --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if(empty($picks))
<div class="glass p-10 text-center">
    <div class="text-4xl mb-4">🔍</div>
    <div class="font-semibold text-lg mb-2" style="color:#0f172a;">No picks found yet</div>
    <div class="text-sm mb-6" style="color:#64748b;">
        The analysis engine needs historical price data in the database.<br>
        Make sure at least 50+ days of price data exists for a few stocks.
    </div>
    <form method="POST" action="{{ route('top-picks.refresh') }}">
        @csrf
        <button class="btn-primary">Run Analysis Now</button>
    </form>
</div>
@else

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  LEGEND ROW                                                 --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="flex flex-wrap gap-3 mb-6 text-xs" style="color:#64748b;">
    <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#22c55e;"></span> Target 1 — Conservative
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#3b82f6;"></span> Target 2 — Base Case
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#a855f7;"></span> Target 3 — Bullish
    </span>
    <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#ef4444;"></span> Stop Loss
    </span>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  PICK CARDS                                                 --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="space-y-6">
@foreach($picks as $i => $pick)
@php
    $rank    = $i + 1;
    $rankCls = 'rank-' . $rank;
    $conf    = $pick['confidence'];
    // SVG ring: circumference = 2π × 30 = 188.5
    $circ    = 188.5;
    $offset  = $circ - ($conf / 100 * $circ);
    $ringColor = $conf >= 75 ? '#22c55e' : ($conf >= 55 ? '#3b82f6' : '#f59e0b');
    // Upside color
    $upColor = $pick['upside_pct'] >= 20 ? '#15803d' : ($pick['upside_pct'] >= 10 ? '#1d4ed8' : '#92400e');
@endphp
<div class="pick-card">

    {{-- Card header ── rank + symbol + confidence --}}
    <div class="flex items-start justify-between p-5 border-b" style="border-color:#f1f5f9;">
        <div class="flex items-center gap-4">
            {{-- Rank badge --}}
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-lg shrink-0 {{ $rankCls }}">
                {{ $rank }}
            </div>

            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('stocks.show', $pick['symbol']) }}"
                       class="text-xl font-extrabold hover:text-blue-600 transition-colors"
                       style="color:#0f172a;font-family:'JetBrains Mono',monospace;">
                        {{ $pick['symbol'] }}
                    </a>
                    <span class="upside-badge">
                        ▲ +{{ $pick['upside_pct'] }}% potential upside
                    </span>
                </div>
                <div class="text-sm mt-0.5" style="color:#64748b;">
                    {{ Str::limit($pick['name'], 45) }}
                    @if($pick['sector'])
                    · <span style="color:#2563eb;">{{ $pick['sector'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Confidence ring --}}
        <div class="conf-ring hidden sm:flex">
            <svg width="72" height="72" viewBox="0 0 72 72">
                <circle cx="36" cy="36" r="30" fill="none" stroke="#e2e8f0" stroke-width="6"/>
                <circle cx="36" cy="36" r="30" fill="none"
                        stroke="{{ $ringColor }}" stroke-width="6"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $circ }}"
                        stroke-dashoffset="{{ $offset }}"/>
            </svg>
            <div class="conf-text">
                {{ $conf }}%
                <small>score</small>
            </div>
        </div>
    </div>

    <div class="p-5 space-y-5">

        {{-- ── Always visible: Current Price + RSI + MACD + SMA badges ── --}}
        <div class="flex flex-wrap gap-2 items-center">
            <div class="price-cell" style="min-width:140px;">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Current Price</div>
                <div class="font-extrabold" style="font-size:1.1rem;color:#0f172a;font-family:'JetBrains Mono',monospace;">
                    NPR {{ number_format($pick['current'], 2) }}
                </div>
            </div>
            @if($pick['rsi'] !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;">
                RSI {{ number_format($pick['rsi'], 1) }}
            </span>
            @endif
            @if($pick['macd'] !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:{{ $pick['macd']['histogram'] > 0 ? '#f0fdf4' : '#fef2f2' }};
                         border:1px solid {{ $pick['macd']['histogram'] > 0 ? '#bbf7d0' : '#fecaca' }};
                         color:{{ $pick['macd']['histogram'] > 0 ? '#15803d' : '#dc2626' }};">
                MACD {{ $pick['macd']['histogram'] > 0 ? '▲' : '▼' }} {{ number_format($pick['macd']['histogram'], 2) }}
            </span>
            @endif
            @if($pick['sma20'] !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:#fefce8;border:1px solid #fef08a;color:#854d0e;">
                SMA20 {{ number_format($pick['sma20'], 2) }}
            </span>
            @endif
            @if($pick['sma50'] !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:#faf5ff;border:1px solid #e9d5ff;color:#7e22ce;">
                SMA50 {{ number_format($pick['sma50'], 2) }}
            </span>
            @endif
            @if($pick['atr'] !== null)
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  style="background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;">
                ATR {{ number_format($pick['atr'], 2) }}
            </span>
            @endif
        </div>

        {{-- ── Auth-gated: Entry/Stop/Targets/Reasons ── --}}
        @auth
        {{-- Entry zone, Stop Loss, R/R ── --}}
        <div class="price-grid">
            <div class="price-cell">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Entry Zone</div>
                <div class="font-bold text-sm" style="color:#1d4ed8;font-family:'JetBrains Mono',monospace;">
                    {{ number_format($pick['entry_min'], 2) }} – {{ number_format($pick['entry_max'], 2) }}
                </div>
            </div>
            <div class="price-cell">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">Stop Loss</div>
                <div class="font-bold text-sm" style="color:#dc2626;font-family:'JetBrains Mono',monospace;">
                    NPR {{ number_format($pick['stop_loss'], 2) }}
                    <span style="font-size:0.7rem;color:#dc2626;">(-{{ $pick['risk_pct'] }}%)</span>
                </div>
            </div>
            <div class="price-cell" style="grid-column:span 2;">
                <div class="text-xs mb-1" style="color:#94a3b8;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;">R/R Ratio</div>
                <div class="font-extrabold text-sm" style="color:{{ $pick['rr_ratio'] >= 2 ? '#15803d' : ($pick['rr_ratio'] >= 1.5 ? '#0369a1' : '#92400e') }};">
                    1 : {{ $pick['rr_ratio'] }}
                </div>
            </div>
        </div>

        {{-- Targets ── --}}
        <div>
            <div class="text-xs font-semibold mb-3 uppercase tracking-wide" style="color:#64748b;">Price Targets</div>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-20 text-xs font-semibold" style="color:#15803d;">
                        T1 <span style="font-family:monospace;">{{ number_format($pick['target_1'], 0) }}</span>
                    </div>
                    <div class="flex-1 target-bar">
                        <div class="target-bar-fill" style="width:{{ min(100, $pick['reward1_pct'] * 5) }}%;background:#22c55e;"></div>
                    </div>
                    <div class="shrink-0 text-xs font-bold" style="color:#15803d;min-width:3.5rem;text-align:right;">
                        +{{ $pick['reward1_pct'] }}%
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-20 text-xs font-semibold" style="color:#1d4ed8;">
                        T2 <span style="font-family:monospace;">{{ number_format($pick['target_2'], 0) }}</span>
                    </div>
                    <div class="flex-1 target-bar">
                        <div class="target-bar-fill" style="width:{{ min(100, $pick['reward2_pct'] * 4) }}%;background:#3b82f6;"></div>
                    </div>
                    <div class="shrink-0 text-xs font-bold" style="color:#1d4ed8;min-width:3.5rem;text-align:right;">
                        +{{ $pick['reward2_pct'] }}%
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="shrink-0 w-20 text-xs font-semibold" style="color:#7e22ce;">
                        T3 <span style="font-family:monospace;">{{ number_format($pick['target_3'], 0) }}</span>
                    </div>
                    <div class="flex-1 target-bar">
                        <div class="target-bar-fill" style="width:{{ min(100, $pick['reward3_pct'] * 3) }}%;background:#a855f7;"></div>
                    </div>
                    <div class="shrink-0 text-xs font-bold" style="color:#7e22ce;min-width:3.5rem;text-align:right;">
                        +{{ $pick['reward3_pct'] }}%
                    </div>
                </div>
            </div>
        </div>

        {{-- Reasons ── --}}
        <div>
            <div class="text-xs font-semibold mb-2.5 uppercase tracking-wide flex items-center gap-1.5" style="color:#64748b;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Why this stock is going up
            </div>
            <div class="space-y-1.5">
                @foreach($pick['reasons'] as $reason)
                <div class="reason-row">
                    <span class="shrink-0 text-base leading-none" style="line-height:1.4;">{{ $reason['icon'] }}</span>
                    <span>{{ $reason['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        @else
        {{-- Guest lock banner ── --}}
        <div style="position:relative;border-radius:0.75rem;overflow:hidden;">
            {{-- Blurred preview of locked content ── --}}
            <div style="filter:blur(5px);user-select:none;pointer-events:none;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;">
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <div style="height:2rem;width:30%;background:#e2e8f0;border-radius:0.5rem;"></div>
                        <div style="height:2rem;width:30%;background:#e2e8f0;border-radius:0.5rem;"></div>
                        <div style="height:2rem;width:30%;background:#e2e8f0;border-radius:0.5rem;"></div>
                    </div>
                    <div style="height:0.75rem;background:#e2e8f0;border-radius:9999px;width:100%;"></div>
                    <div style="height:0.75rem;background:#e2e8f0;border-radius:9999px;width:80%;"></div>
                    <div style="height:0.75rem;background:#e2e8f0;border-radius:9999px;width:90%;"></div>
                    <div style="height:0.75rem;background:#e2e8f0;border-radius:9999px;width:70%;"></div>
                    <div style="height:0.75rem;background:#e2e8f0;border-radius:9999px;width:85%;"></div>
                </div>
            </div>
            {{-- Lock overlay ── --}}
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem;background:rgba(255,255,255,0.7);backdrop-filter:blur(2px);border-radius:0.75rem;">
                <div style="width:2.5rem;height:2.5rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:1.1rem;height:1.1rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div style="text-align:center;">
                    <div style="font-weight:700;font-size:0.9375rem;color:#0f172a;margin-bottom:0.25rem;">
                        Login to unlock full analysis
                    </div>
                    <div style="font-size:0.8125rem;color:#64748b;">
                        Entry zone · Stop loss · Price targets · Reasons
                    </div>
                </div>
                <a href="{{ route('login') }}"
                   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1.25rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:0.875rem;font-weight:600;border-radius:0.625rem;text-decoration:none;box-shadow:0 2px 8px rgba(79,70,229,0.35);">
                    <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Sign in free
                </a>
            </div>
        </div>
        @endauth

        {{-- ── Score + Full Analysis CTA (always visible) ── --}}
        <div class="flex gap-3 pt-1">
            <a href="{{ route('stocks.show', $pick['symbol']) }}"
               class="btn-primary flex-1 justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Full Analysis
            </a>
            <div class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium"
                 style="background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Score: <strong style="color:#0f172a;">{{ $pick['score'] }} pts</strong>
            </div>
        </div>

    </div>
</div>
@endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  DISCLAIMER                                                 --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="mt-8 p-4 rounded-xl text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
    <strong>⚠ Disclaimer:</strong> These picks are generated by a rule-based algorithm using technical indicators only.
    They do not constitute financial advice. Always do your own research and consider your risk tolerance before investing.
    Past performance does not guarantee future results.
</div>
@endif

@endsection
