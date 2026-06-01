@extends('layouts.app')
@section('title', 'NEPSE Analytics - Dashboard')

@push('head')
<style>
@keyframes ticker {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
@keyframes fadeUp {
  from { opacity:0;transform:translateY(12px); }
  to   { opacity:1;transform:translateY(0); }
}
@keyframes livePulse { 0%,100%{opacity:1;}50%{opacity:.4;} }
.ticker-wrap  { overflow:hidden;flex:1; }
.ticker-inner { display:flex;animation:ticker 55s linear infinite;width:max-content; }
.ticker-inner:hover { animation-play-state:paused; }
.fade-up    { animation:fadeUp .4s ease both; }
.fade-d1    { animation-delay:.06s; }
.fade-d2    { animation-delay:.13s; }
.fade-d3    { animation-delay:.20s; }
.fade-d4    { animation-delay:.27s; }
.card-hover { transition:box-shadow .18s,transform .18s; }
.card-hover:hover { box-shadow:0 8px 28px rgba(37,99,235,.12);transform:translateY(-2px); }
.sector-row { transition:background .12s; }
.sector-row:hover { background:#eff6ff; }
.stock-row  { transition:background .1s; }
.stock-row:hover  { background:#f8fafc; }
</style>
@endpush

@section('content')
@php
  $activeList = collect($stockList)->filter(fn($s) => !($s['is_delisted']??false) && !($s['is_merged']??false));
@endphp

{{-- ════ HERO ══════════════════════════════════════════════════════════════ --}}
<div class="fade-up" style="border-radius:1.25rem;overflow:hidden;margin-bottom:1.75rem;
     background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#312e81 100%);
     position:relative;padding:2.5rem 2rem;">

  <div style="position:absolute;inset:0;opacity:.05;pointer-events:none;
       background-image:radial-gradient(circle,#fff 1px,transparent 1px);
       background-size:28px 28px;"></div>
  <div style="position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;
       background:radial-gradient(circle,rgba(99,102,241,.3),transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;bottom:-40px;left:8%;width:220px;height:220px;border-radius:50%;
       background:radial-gradient(circle,rgba(59,130,246,.2),transparent 70%);pointer-events:none;"></div>

  <div style="position:relative;z-index:1;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem;">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <span style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(34,197,94,.15);
               border:1px solid rgba(34,197,94,.3);border-radius:9999px;padding:.25rem .75rem;
               font-size:.72rem;font-weight:600;color:#86efac;">
          <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;
                 animation:livePulse 1.5s infinite;"></span>
          Live Market Data
        </span>
        <span style="font-size:.78rem;color:rgba(255,255,255,.4);">{{ now()->format('D, d M Y') }}</span>
      </div>
      <form method="POST" action="{{ route('dashboard.sync') }}">
        @csrf
        <button type="submit"
          style="font-size:.75rem;color:rgba(255,255,255,.5);background:rgba(255,255,255,.08);
                 border:1px solid rgba(255,255,255,.12);border-radius:.5rem;padding:.3rem .75rem;
                 cursor:pointer;transition:all .15s;"
          onmouseover="this.style.background='rgba(255,255,255,.15)'"
          onmouseout="this.style.background='rgba(255,255,255,.08)'"
          onclick="this.disabled=true;this.textContent='Refreshing…'">
          ↻ Refresh Data
        </button>
      </form>
    </div>

    <h1 style="font-size:clamp(1.75rem,5vw,2.875rem);font-weight:900;color:#fff;
         line-height:1.1;letter-spacing:-.02em;margin:0 0 .75rem;">
      Nepal Stock Exchange<br>
      <span style="background:linear-gradient(90deg,#60a5fa,#a78bfa);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
        Analytics Platform
      </span>
    </h1>
    <p style="font-size:.9375rem;color:rgba(255,255,255,.6);max-width:42rem;line-height:1.65;margin:0 0 2rem;">
      Live technical analysis for every NEPSE stock — RSI, MACD, Bollinger Bands, buy/sell signals
      and 7-day forecasts powered by real-time market data.
    </p>

    {{-- Search bar (all users) --}}
    <div style="position:relative;max-width:560px;">
      <svg style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);
           color:rgba(255,255,255,.4);pointer-events:none;" width="18" height="18" fill="none"
           stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input id="heroSearch" type="text" autocomplete="off"
             placeholder="Search symbol or company — e.g. NABIL, NICA, Hydropower…"
             style="width:100%;padding:.875rem 1rem .875rem 3rem;font-size:.9375rem;
                    background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
                    border-radius:.875rem;color:#fff;outline:none;box-sizing:border-box;
                    backdrop-filter:blur(8px);transition:border-color .2s,background .2s;"
             onfocus="this.style.background='rgba(255,255,255,.16)';this.style.borderColor='rgba(129,140,248,.8)'"
             onblur="this.style.background='rgba(255,255,255,.1)';this.style.borderColor='rgba(255,255,255,.18)'">
      <div id="heroDropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;
           border-radius:.875rem;overflow:hidden;background:#fff;border:1px solid #e2e8f0;
           box-shadow:0 20px 60px rgba(0,0,0,.25);z-index:60;"></div>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1.5rem;">
      @foreach(['RSI 14','MACD 12/26','Bollinger Bands','ATR 14','Support/Resistance','Buy/Sell Signals','7-Day Forecast'] as $f)
      <span style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);
             border-radius:9999px;padding:.2rem .7rem;font-size:.7rem;color:rgba(255,255,255,.65);">
        {{ $f }}
      </span>
      @endforeach
    </div>
  </div>
</div>

{{-- ════ QUICK NAV ═════════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d1" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));
     gap:.875rem;margin-bottom:1.75rem;">
  @php
  $quickNav = [
    ['route'=>'stocks.index',   'icon'=>'📈', 'label'=>'Markets',     'desc'=>$totalStocks.' stocks',    'clr'=>'#2563eb','bg'=>'#eff6ff','bd'=>'#bfdbfe', 'auth'=>false],
    ['route'=>'screener.index', 'icon'=>'🔍', 'label'=>'Screener',    'desc'=>'Filter & screen',         'clr'=>'#7c3aed','bg'=>'#f5f3ff','bd'=>'#ddd6fe', 'auth'=>false],
    ['route'=>'signals.index',  'icon'=>'⚡', 'label'=>'Signals',     'desc'=>'Buy/Sell alerts',         'clr'=>'#d97706','bg'=>'#fffbeb','bd'=>'#fde68a', 'auth'=>false],
    ['route'=>'top-picks.index','icon'=>'⭐', 'label'=>'Top Picks',   'desc'=>'Best 5 uptrend stocks',   'clr'=>'#16a34a','bg'=>'#f0fdf4','bd'=>'#bbf7d0'],
    ['route'=>'ipo.index',      'icon'=>'📋', 'label'=>'IPO Results', 'desc'=>'Check allotment',         'clr'=>'#0891b2','bg'=>'#ecfeff','bd'=>'#a5f3fc', 'auth'=>false],
  ];
  @endphp
  @foreach($quickNav as $nav)
  @if(!($nav['auth'] ?? false) || auth()->check())
  <a href="{{ route($nav['route']) }}" class="card-hover"
     style="display:flex;align-items:center;gap:.875rem;padding:1rem 1.125rem;
            background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
            text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <div style="width:40px;height:40px;border-radius:.625rem;flex-shrink:0;font-size:1.2rem;
         background:{{ $nav['bg'] }};border:1px solid {{ $nav['bd'] }};
         display:flex;align-items:center;justify-content:center;">
      {{ $nav['icon'] }}
    </div>
    <div style="min-width:0;">
      <div style="font-size:.875rem;font-weight:700;color:#0f172a;">{{ $nav['label'] }}</div>
      <div style="font-size:.7rem;color:#94a3b8;margin-top:.1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        {{ $nav['desc'] }}
      </div>
    </div>
  </a>
  @endif
  @endforeach
</div> ════════════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d2" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
     overflow:hidden;margin-bottom:1.75rem;display:flex;align-items:center;">
  <div style="flex-shrink:0;padding:.625rem 1rem;background:#0f172a;font-size:.65rem;
       font-weight:700;color:#fff;letter-spacing:.07em;text-transform:uppercase;white-space:nowrap;">
    NEPSE
  </div>
  <div class="ticker-wrap">
    <div class="ticker-inner" style="padding:.625rem 0;">
      @php $tickerStocks = $activeList->sortBy('symbol')->take(50); @endphp
      @foreach($tickerStocks as $t)
      <a href="{{ route('stocks.show', $t['symbol']) }}"
         style="display:inline-flex;align-items:center;gap:.5rem;padding:0 1.25rem;
                text-decoration:none;border-right:1px solid #f1f5f9;white-space:nowrap;">
        <span style="font-weight:700;font-size:.78rem;color:#0f172a;
               font-family:'JetBrains Mono',monospace;">{{ $t['symbol'] }}</span>
        <span style="font-size:.7rem;color:#94a3b8;">{{ Str::limit($t['name'],15) }}</span>
      </a>
      @endforeach
      @foreach($tickerStocks as $t)
      <a href="{{ route('stocks.show', $t['symbol']) }}"
         style="display:inline-flex;align-items:center;gap:.5rem;padding:0 1.25rem;
                text-decoration:none;border-right:1px solid #f1f5f9;white-space:nowrap;">
        <span style="font-weight:700;font-size:.78rem;color:#0f172a;
               font-family:'JetBrains Mono',monospace;">{{ $t['symbol'] }}</span>
        <span style="font-size:.7rem;color:#94a3b8;">{{ Str::limit($t['name'],15) }}</span>
      </a>
      @endforeach
    </div>
  </div>
</div>

{{-- ════ STAT CARDS ════════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d2" style="display:grid;grid-template-columns:repeat(2,1fr);
     gap:1rem;margin-bottom:1.75rem;">
  @php
  $stats = [
    ['label'=>'Listed Stocks','value'=>number_format($totalStocks),'sub'=>'Active on NEPSE',
     'top'=>'#2563eb','ic'=>'#2563eb','ib'=>'#eff6ff',
     'svg'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
    ['label'=>'Sectors','value'=>count($sectors),'sub'=>'Market segments',
     'top'=>'#7c3aed','ic'=>'#7c3aed','ib'=>'#f5f3ff',
     'svg'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
    ['label'=>'Data Source','value'=>'Live API','sub'=>'Real-time · No scraping',
     'top'=>'#059669','ic'=>'#059669','ib'=>'#f0fdf4',
     'svg'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
    ['label'=>'Analytics','value'=>'Real-time','sub'=>'Computed on demand',
     'top'=>'#d97706','ic'=>'#d97706','ib'=>'#fffbeb',
     'svg'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>'],
  ];
  @endphp
  @foreach($stats as $s)
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.25rem;
       box-shadow:0 1px 3px rgba(0,0,0,.04);position:relative;overflow:hidden;">
    <div style="position:absolute;top:0;left:0;right:0;height:3px;
         background:linear-gradient(90deg,{{ $s['top'] }},{{ $s['top'] }}44);
         border-radius:.875rem .875rem 0 0;"></div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;">
      <div>
        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
             color:#94a3b8;margin-bottom:.5rem;">{{ $s['label'] }}</div>
        <div style="font-size:1.625rem;font-weight:800;color:#0f172a;
             font-family:'JetBrains Mono',monospace;line-height:1;">{{ $s['value'] }}</div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:.375rem;">{{ $s['sub'] }}</div>
      </div>
      <div style="width:38px;height:38px;border-radius:.625rem;flex-shrink:0;
           background:{{ $s['ib'] }};display:flex;align-items:center;justify-content:center;">
        <svg width="18" height="18" fill="none" stroke="{{ $s['ic'] }}" stroke-width="2" viewBox="0 0 24 24">
          {!! $s['svg'] !!}
        </svg>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- ════ SECTORS + STOCK LIST ══════════════════════════════════════════════ --}}
<div class="fade-up fade-d3" style="display:grid;grid-template-columns:1fr;
     gap:1.25rem;margin-bottom:1.75rem;">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

  {{-- Sectors --}}
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
       overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;justify-content:space-between;
         padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;">
      <div>
        <div style="font-size:.875rem;font-weight:700;color:#0f172a;">Market Sectors</div>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.1rem;">{{ $sectorStats->count() }} segments</div>
      </div>
      <a href="{{ route('stocks.index') }}" style="font-size:.72rem;color:#2563eb;text-decoration:none;
         padding:.25rem .6rem;border-radius:.375rem;background:#eff6ff;border:1px solid #bfdbfe;">
        All stocks →
      </a>
    </div>
    @php
    $sectorColors = ['#2563eb','#7c3aed','#059669','#d97706','#0891b2','#dc2626','#4f46e5','#0d9488','#b45309','#6d28d9','#065f46','#9f1239'];
    $maxCount = $sectorStats->first()['count'] ?? 1;
    @endphp
    @foreach($sectorStats->take(12) as $i => $sec)
    @php $clr = $sectorColors[$i % count($sectorColors)]; @endphp
    <a href="{{ route('stocks.index', ['sector' => $sec['name']]) }}"
       class="sector-row"
       style="display:flex;align-items:center;gap:.875rem;padding:.65rem 1.25rem;
              border-bottom:1px solid #f8fafc;text-decoration:none;">
      <div style="width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.8rem;color:#374151;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ $sec['name'] }}
        </div>
        <div style="height:3px;background:#f1f5f9;border-radius:9999px;overflow:hidden;margin-top:.25rem;">
          <div style="height:3px;width:{{ round($sec['count']/$maxCount*100) }}%;
               background:{{ $clr }};border-radius:9999px;opacity:.5;"></div>
        </div>
      </div>
      <span style="font-size:.75rem;font-weight:700;font-family:'JetBrains Mono',monospace;
             color:{{ $clr }};min-width:2rem;text-align:right;">{{ $sec['count'] }}</span>
    </a>
    @endforeach
  </div>

  {{-- Stock list --}}
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
       overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;justify-content:space-between;
         padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;">
      <div>
        <div style="font-size:.875rem;font-weight:700;color:#0f172a;">Listed Companies</div>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.1rem;">{{ number_format($totalStocks) }} active stocks</div>
      </div>
      <a href="{{ route('stocks.index') }}" style="font-size:.72rem;color:#2563eb;text-decoration:none;
         padding:.25rem .6rem;border-radius:.375rem;background:#eff6ff;border:1px solid #bfdbfe;">
        View all →
      </a>
    </div>
    @foreach($activeList->sortBy('symbol')->take(15) as $s)
    <a href="{{ route('stocks.show', $s['symbol']) }}"
       class="stock-row"
       style="display:flex;align-items:center;gap:.875rem;padding:.625rem 1.25rem;
              border-bottom:1px solid #f8fafc;text-decoration:none;">
      <div style="width:38px;height:38px;border-radius:.625rem;flex-shrink:0;
           background:linear-gradient(135deg,#eff6ff,#e0e7ff);
           display:flex;align-items:center;justify-content:center;">
        <span style="font-size:.6rem;font-weight:800;color:#2563eb;
               font-family:'JetBrains Mono',monospace;letter-spacing:-.02em;">
          {{ Str::limit($s['symbol'],4) }}
        </span>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.8rem;font-weight:600;color:#0f172a;
             overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ Str::limit($s['name'], 28) }}
        </div>
        @if($s['sector'])
        <div style="font-size:.68rem;color:#94a3b8;margin-top:.1rem;">{{ $s['sector'] }}</div>
        @endif
      </div>
      <span style="font-size:.68rem;color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;
             border-radius:9999px;padding:.2rem .55rem;flex-shrink:0;">→</span>
    </a>
    @endforeach
  </div>

  </div>
</div>

{{-- ════ HOW IT WORKS ══════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d4" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
     padding:1.5rem;margin-bottom:1rem;box-shadow:0 1px 3px rgba(0,0,0,.04);">
  <div style="text-align:center;margin-bottom:1.375rem;">
    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
         color:#94a3b8;margin-bottom:.35rem;">GET STARTED IN 3 STEPS</div>
    <div style="font-size:1.0625rem;font-weight:700;color:#0f172a;">How it works</div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:1rem;">
    @php
    $steps = [
      ['n'=>'1','emoji'=>'🔍','title'=>'Search any stock','desc'=>'Type a symbol or company name. 674+ NEPSE-listed companies available instantly.','clr'=>'#2563eb','bg'=>'#eff6ff','bd'=>'#bfdbfe22'],
      ['n'=>'2','emoji'=>'⚡','title'=>'Live analysis','desc'=>'RSI, MACD, Bollinger Bands computed in real-time from live NEPSE market data.','clr'=>'#7c3aed','bg'=>'#f5f3ff','bd'=>'#ddd6fe22'],
      ['n'=>'3','emoji'=>'📊','title'=>'Signal + levels','desc'=>'BUY/SELL/HOLD signal with confidence score, entry range, stop-loss and price targets.','clr'=>'#16a34a','bg'=>'#f0fdf4','bd'=>'#bbf7d022'],
      ['n'=>'4','emoji'=>'🔮','title'=>'7-Day forecast','desc'=>'Next 7 trading days predicted using momentum, RSI, MACD and day-of-week historical patterns.','clr'=>'#d97706','bg'=>'#fffbeb','bd'=>'#fde68a22'],
    ];
    @endphp
    @foreach($steps as $step)
    <div style="padding:1.125rem;border-radius:.75rem;background:{{ $step['bg'] }};border:1px solid {{ $step['bd'] }};">
      <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:.625rem;">
        <div style="width:26px;height:26px;border-radius:50%;background:{{ $step['clr'] }};
             display:flex;align-items:center;justify-content:center;
             font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;">{{ $step['n'] }}</div>
        <span style="font-size:1.2rem;">{{ $step['emoji'] }}</span>
      </div>
      <div style="font-size:.875rem;font-weight:700;color:#0f172a;margin-bottom:.375rem;">{{ $step['title'] }}</div>
      <div style="font-size:.775rem;color:#64748b;line-height:1.6;">{{ $step['desc'] }}</div>
    </div>
    @endforeach
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
  if (q.length < 1) { heroDropdown.style.display = 'none'; return; }
  heroTimer = setTimeout(() => {
    fetch('/api/search?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        if (!data.length) { heroDropdown.style.display = 'none'; return; }
        heroDropdown.innerHTML = data.map(s =>
          '<a href="' + s.url + '" style="display:flex;align-items:center;justify-content:space-between;' +
          'padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;text-decoration:none;" ' +
          'onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'transparent\'">' +
          '<div><span style="font-weight:700;color:#0f172a;font-size:.875rem;font-family:monospace;">' + s.symbol + '</span>' +
          '<span style="font-size:.8rem;color:#64748b;margin-left:.75rem;">' + s.name + '</span></div>' +
          '<span style="font-size:.7rem;color:#2563eb;padding:.2rem .6rem;border-radius:9999px;background:#eff6ff;border:1px solid #bfdbfe;">Analyse →</span>' +
          '</a>'
        ).join('');
        heroDropdown.style.display = 'block';
      });
  }, 220);
});

document.addEventListener('click', e => {
  if (!heroSearch?.contains(e.target) && !heroDropdown?.contains(e.target))
    heroDropdown.style.display = 'none';
});

heroSearch?.addEventListener('keydown', e => {
  if (e.key === 'Enter') { const a = heroDropdown.querySelector('a'); if (a) a.click(); }
});
</script>
@endpush
