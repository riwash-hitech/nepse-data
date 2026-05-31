@extends('layouts.app')
@section('title', 'NEPSE Analytics')

@push('head')
<style>
/* ── Hero gradient ── */
.hero-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 50% at 50% -10%, rgba(37,99,235,0.06) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 80% 30%, rgba(124,58,237,0.04) 0%, transparent 60%);
    pointer-events: none;
}
.hero-title {
    background: linear-gradient(135deg, #0f172a 30%, #1e40af 70%, #4f46e5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
/* ── Ticker strip ── */
@keyframes ticker {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.ticker-inner { display:flex; animation: ticker 40s linear infinite; width: max-content; }
.ticker-inner:hover { animation-play-state: paused; }
/* ── Stat card accent bars ── */
.stat-card { position:relative; overflow:hidden; }
.stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:0.75rem 0.75rem 0 0; }
.stat-card.blue::before  { background: linear-gradient(90deg,#2563eb,#60a5fa); }
.stat-card.purple::before { background: linear-gradient(90deg,#7c3aed,#a78bfa); }
.stat-card.green::before  { background: linear-gradient(90deg,#059669,#34d399); }
.stat-card.orange::before { background: linear-gradient(90deg,#d97706,#fbbf24); }
/* ── Sector pill hover ── */
.sector-pill {
    display:flex; align-items:center; justify-content:space-between;
    padding: 0.75rem 1rem; border-radius:0.625rem;
    background:#ffffff; border:1px solid #e2e8f0;
    transition: all 0.18s; cursor:pointer; text-decoration:none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.sector-pill:hover { background:#eff6ff; border-color:#bfdbfe; transform:translateY(-1px); box-shadow:0 4px 12px rgba(37,99,235,0.1); }
/* ── Stock row hover ── */
.stock-row { transition: background 0.12s; }
.stock-row:hover { background:#f8fafc; }
/* ── Hero search ── */
.hero-search {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    color: #0f172a;
    font-size: 1rem;
    padding: 0.875rem 1rem 0.875rem 3rem;
    width: 100%;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.hero-search:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}
.hero-search::placeholder { color: #94a3b8; }
/* ── Feature badge ── */
.feature-tag {
    display:inline-flex; align-items:center; gap:0.4rem;
    padding:0.25rem 0.75rem; border-radius:9999px;
    font-size:0.75rem; font-weight:500;
    background:#eff6ff; color:#2563eb;
    border:1px solid #bfdbfe;
}
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════ --}}
{{--  HERO                                               --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="relative rounded-2xl overflow-hidden mb-8"
     style="background:linear-gradient(135deg,#f8faff 0%,#eff6ff 50%,#f5f3ff 100%);
            border:1px solid #e0e7ff;
            padding:3rem 2.5rem 2.5rem;
            box-shadow:0 4px 24px rgba(37,99,235,0.07);">
    <div class="hero-glow"></div>

    {{-- Top row: date + refresh --}}
    <div class="relative flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <span class="feature-tag">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>
                Live Data
            </span>
            <span style="color:#94a3b8;font-size:0.8125rem;">{{ now()->format('l, d M Y') }}</span>
        </div>
        <form method="POST" action="{{ route('dashboard.sync') }}">
            @csrf
            <button type="submit"
                onclick="this.disabled=true;this.textContent='Refreshing…'"
                style="font-size:0.8rem;color:#94a3b8;background:transparent;border:none;cursor:pointer;padding:0.25rem 0;transition:color 0.15s;"
                onmouseover="this.style.color='#64748b'"
                onmouseout="this.style.color='#94a3b8'">
                ↻ Refresh
            </button>
        </form>
    </div>

    @if(session('success'))
    <div class="relative mb-5 px-4 py-2.5 rounded-lg text-sm"
         style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Headline --}}
    <div class="relative mb-2">
        <h1 class="hero-title text-4xl md:text-5xl font-bold leading-tight tracking-tight">
            Nepal Stock Exchange
        </h1>
        <h2 class="hero-title text-4xl md:text-5xl font-bold leading-tight tracking-tight">
            Analytics Platform
        </h2>
    </div>
    <p class="relative mb-8" style="color:#64748b;font-size:1rem;max-width:38rem;">
        Live technical analysis — RSI, MACD, Bollinger Bands, signals — fetched directly from
        Chukul.com for every listed stock. No delays, no stale data.
    </p>

    {{-- Big search bar --}}
    <div class="relative max-w-2xl">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none"
             style="color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input id="heroSearch" type="text"
               placeholder="Search symbol or company — e.g. NABIL, NICA, Hydropower…"
               autocomplete="off"
               class="hero-search">
        <div id="heroDropdown"
             class="absolute left-0 right-0 mt-2 rounded-xl shadow-xl hidden z-50 overflow-hidden"
             style="background:#ffffff;border:1px solid #e2e8f0;top:100%;">
        </div>
    </div>

    {{-- Feature tags --}}
    <div class="relative flex flex-wrap gap-2 mt-5">
        <span class="feature-tag">RSI 14</span>
        <span class="feature-tag">MACD 12/26/9</span>
        <span class="feature-tag">Bollinger Bands</span>
        <span class="feature-tag">ATR 14</span>
        <span class="feature-tag">Support &amp; Resistance</span>
        <span class="feature-tag">Buy / Sell Signals</span>
    </div>
</div>

{{-- ════════════════════════════════════════════════════ --}}
{{--  LIVE TICKER STRIP                                  --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="mb-8 rounded-xl overflow-hidden"
     style="background:#ffffff;border:1px solid #e2e8f0;">
    <div class="overflow-hidden py-3 px-2" style="white-space:nowrap;">
        <div class="ticker-inner">
            @php $tickerStocks = collect($stockList)->filter(fn($s) => !($s['is_delisted']??false))->sortBy('symbol')->take(40); @endphp
            @foreach($tickerStocks as $t)
            <a href="{{ route('stocks.show', $t['symbol']) }}"
               style="display:inline-flex;align-items:center;gap:0.5rem;padding:0 1.25rem;text-decoration:none;border-right:1px solid #f1f5f9;">
                <span style="font-weight:700;font-size:0.8125rem;color:#0f172a;font-family:'JetBrains Mono',monospace;">{{ $t['symbol'] }}</span>
                <span style="font-size:0.75rem;color:#94a3b8;">{{ Str::limit($t['name'],18) }}</span>
            </a>
            @endforeach
            @foreach($tickerStocks as $t)
            <a href="{{ route('stocks.show', $t['symbol']) }}"
               style="display:inline-flex;align-items:center;gap:0.5rem;padding:0 1.25rem;text-decoration:none;border-right:1px solid #f1f5f9;">
                <span style="font-weight:700;font-size:0.8125rem;color:#0f172a;font-family:'JetBrains Mono',monospace;">{{ $t['symbol'] }}</span>
                <span style="font-size:0.75rem;color:#94a3b8;">{{ Str::limit($t['name'],18) }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════ --}}
{{--  STAT CARDS                                         --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card blue glass p-5">
        <div style="color:#2563eb;font-size:0.7rem;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;margin-bottom:0.75rem;">Listed Stocks</div>
        <div style="font-size:2rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:#0f172a;line-height:1;">{{ number_format($totalStocks) }}</div>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.5rem;">Active on NEPSE</div>
    </div>
    <div class="stat-card purple glass p-5">
        <div style="color:#7c3aed;font-size:0.7rem;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;margin-bottom:0.75rem;">Sectors</div>
        <div style="font-size:2rem;font-weight:800;font-family:'JetBrains Mono',monospace;color:#0f172a;line-height:1;">{{ count($sectors) }}</div>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.5rem;">Market segments</div>
    </div>
    <div class="stat-card green glass p-5">
        <div style="color:#059669;font-size:0.7rem;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;margin-bottom:0.75rem;">Data Source</div>
        <div style="font-size:1.25rem;font-weight:700;color:#0f172a;line-height:1.3;margin-top:0.2rem;">Chukul.com</div>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.5rem;">Live API · No scraping</div>
    </div>
    <div class="stat-card orange glass p-5">
        <div style="color:#d97706;font-size:0.7rem;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;margin-bottom:0.75rem;">Analytics</div>
        <div style="font-size:1.25rem;font-weight:700;color:#0f172a;line-height:1.3;margin-top:0.2rem;">Real-time</div>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.5rem;">Computed on demand</div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════ --}}
{{--  SECTORS + STOCK PREVIEW SPLIT                      --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">

    {{-- Sector list --}}
    <div class="lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#0f172a;">Sectors</h2>
            <a href="{{ route('stocks.index') }}" style="font-size:0.8rem;color:#2563eb;text-decoration:none;">Browse all →</a>
        </div>
        <div class="space-y-2">
            @foreach($sectorStats->take(10) as $sec)
            <a href="{{ route('stocks.index', ['sector' => $sec['name']]) }}"
               class="sector-pill">
                <span style="font-size:0.875rem;font-weight:500;color:#1e293b;max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $sec['name'] }}
                </span>
                <span style="font-size:0.8125rem;font-weight:700;font-family:'JetBrains Mono',monospace;
                             color:#2563eb;background:#eff6ff;padding:0.2rem 0.6rem;border-radius:0.375rem;border:1px solid #bfdbfe;">
                    {{ $sec['count'] }}
                </span>
            </a>
            @endforeach
            @if($sectorStats->count() > 10)
            <a href="{{ route('stocks.index') }}" class="sector-pill" style="justify-content:center;">
                <span style="font-size:0.8rem;color:#64748b;">+ {{ $sectorStats->count() - 10 }} more sectors</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Stock list --}}
    <div class="lg:col-span-3">
        <div class="flex items-center justify-between mb-4">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#0f172a;">Listed Companies</h2>
            <a href="{{ route('stocks.index') }}" style="font-size:0.8rem;color:#2563eb;text-decoration:none;">
                View all {{ number_format($totalStocks) }} →
            </a>
        </div>
        <div class="glass overflow-hidden" style="border-radius:0.875rem;">
            <div style="padding:0.5rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <div style="display:grid;grid-template-columns:5.5rem 1fr auto;gap:0.75rem;
                            font-size:0.7rem;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#94a3b8;">
                    <span>Symbol</span><span>Company</span><span>Action</span>
                </div>
            </div>
            @foreach(collect($stockList)->filter(fn($s) => !($s['is_delisted']??false) && !($s['is_merged']??false))->sortBy('symbol')->take(12) as $s)
            <a href="{{ route('stocks.show', $s['symbol']) }}"
               class="stock-row"
               style="display:grid;grid-template-columns:5.5rem 1fr auto;gap:0.75rem;align-items:center;
                      padding:0.75rem 1rem;border-bottom:1px solid #f1f5f9;text-decoration:none;">
                <span style="font-weight:700;font-size:0.875rem;color:#0f172a;font-family:'JetBrains Mono',monospace;">{{ $s['symbol'] }}</span>
                <div>
                    <div style="font-size:0.8125rem;color:#374151;line-height:1.3;">{{ Str::limit($s['name'], 28) }}</div>
                    @if($s['sector'])
                    <div style="font-size:0.7rem;color:#94a3b8;margin-top:0.15rem;">{{ $s['sector'] }}</div>
                    @endif
                </div>
                <span style="font-size:0.7rem;padding:0.2rem 0.65rem;border-radius:9999px;white-space:nowrap;
                             background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">
                    Analyse →
                </span>
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════ --}}
{{--  HOW IT WORKS                                       --}}
{{-- ════════════════════════════════════════════════════ --}}
<h2 style="font-size:0.9375rem;font-weight:700;color:#0f172a;margin-bottom:1rem;">How it works</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="glass p-5">
        <div style="width:2.5rem;height:2.5rem;border-radius:0.625rem;background:#eff6ff;
                    display:flex;align-items:center;justify-content:center;margin-bottom:0.875rem;">
            <svg width="18" height="18" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <div style="font-weight:600;color:#0f172a;margin-bottom:0.375rem;font-size:0.9375rem;">1. Search any stock</div>
        <div style="font-size:0.8125rem;color:#64748b;line-height:1.6;">
            Type a symbol or company name. 674+ NEPSE-listed companies available instantly.
        </div>
    </div>
    <div class="glass p-5">
        <div style="width:2.5rem;height:2.5rem;border-radius:0.625rem;background:#f5f3ff;
                    display:flex;align-items:center;justify-content:center;margin-bottom:0.875rem;">
            <svg width="18" height="18" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <div style="font-weight:600;color:#0f172a;margin-bottom:0.375rem;font-size:0.9375rem;">2. Live analysis</div>
        <div style="font-size:0.8125rem;color:#64748b;line-height:1.6;">
            Price history fetched from Chukul.com. RSI, MACD, Bollinger Bands calculated in real-time — no stale data.
        </div>
    </div>
    <div class="glass p-5">
        <div style="width:2.5rem;height:2.5rem;border-radius:0.625rem;background:#f0fdf4;
                    display:flex;align-items:center;justify-content:center;margin-bottom:0.875rem;">
            <svg width="18" height="18" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div style="font-weight:600;color:#0f172a;margin-bottom:0.375rem;font-size:0.9375rem;">3. Signal + levels</div>
        <div style="font-size:0.8125rem;color:#64748b;line-height:1.6;">
            BUY / SELL / HOLD signal with confidence score, entry range, stop-loss and price targets.
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const heroSearch   = document.getElementById('heroSearch');
const heroDropdown = document.getElementById('heroDropdown');
let heroTimer;

heroSearch?.addEventListener('input', function () {
    clearTimeout(heroTimer);
    const q = this.value.trim();
    if (q.length < 1) { heroDropdown.classList.add('hidden'); return; }
    heroTimer = setTimeout(() => {
        fetch(`/api/search?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.length) { heroDropdown.classList.add('hidden'); return; }
                heroDropdown.innerHTML = data.map(s => `
                    <a href="${s.url}"
                       style="display:flex;align-items:center;justify-content:space-between;
                              padding:0.75rem 1.25rem;border-bottom:1px solid #f1f5f9;
                              text-decoration:none;transition:background 0.1s;"
                       onmouseover="this.style.background='#f8fafc'"
                       onmouseout="this.style.background='transparent'">
                        <div>
                            <span style="font-weight:700;color:#0f172a;font-size:0.9375rem;font-family:'JetBrains Mono',monospace;">${s.symbol}</span>
                            <span style="font-size:0.8125rem;color:#64748b;margin-left:0.75rem;">${s.name}</span>
                        </div>
                        <span style="font-size:0.75rem;color:#2563eb;padding:0.2rem 0.6rem;border-radius:9999px;
                                     background:#eff6ff;border:1px solid #bfdbfe;">
                            Analyse →
                        </span>
                    </a>`).join('');
                heroDropdown.classList.remove('hidden');
            });
    }, 220);
});

document.addEventListener('click', e => {
    if (!heroSearch?.contains(e.target) && !heroDropdown?.contains(e.target))
        heroDropdown?.classList.add('hidden');
});

heroSearch?.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        const first = heroDropdown.querySelector('a');
        if (first) first.click();
    }
});
</script>
@endpush


