@extends('layouts.app')
@section('title', 'NEPSE Analytics - Dashboard')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 20; vertical-align:middle; }
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
.card-hover:hover { box-shadow:0 8px 28px rgba(20,83,45,.14);transform:translateY(-2px); }
.sector-row { transition:background .12s; }
.sector-row:hover { background:#f0fdf4; }
.stock-row  { transition:background .1s; }
.stock-row:hover  { background:#f8fafc; }
.sector-volume-grid { display:grid; grid-template-columns:2fr 1fr; gap:1.25rem; }
@media (max-width: 860px) {
  .sector-volume-grid { grid-template-columns:1fr; }
}
.hero-stats-grid { display:grid; grid-template-columns:repeat(2,minmax(140px,1fr)); gap:1rem; width:100%; max-width:340px; }
@media (max-width: 560px) {
  .market-hero { padding:1.25rem !important; }
  .hero-stats-grid { grid-template-columns:1fr; max-width:100%; }
}
</style>
@endpush

@section('content')
@php
  $activeList = collect($stockList)->filter(fn($s) => !($s['is_delisted']??false) && !($s['is_merged']??false));
@endphp

{{-- ════ MARKET TECHNICALS MINI-HERO ══════════════════════════════════════ --}}
<div class="fade-up market-hero" style="border-radius:1.25rem;overflow:hidden;margin-bottom:1.75rem;
     background:linear-gradient(135deg,#14532D 0%,#166534 100%);
     position:relative;padding:1.75rem 2rem;">

  <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;
       background:radial-gradient(circle,rgba(255,255,255,.08),transparent 70%);pointer-events:none;"></div>
  <div style="position:absolute;bottom:-60px;left:-40px;width:180px;height:180px;border-radius:50%;
       background:radial-gradient(circle,rgba(0,0,0,.15),transparent 70%);pointer-events:none;"></div>

  <div style="position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:1.75rem;align-items:center;">
    <div style="flex:1;min-width:280px;">
      <h2 style="font-size:1.15rem;font-weight:700;color:#fff;margin:0 0 1rem;">Market Technicals</h2>

      <div style="background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.15);border-radius:.75rem;
           padding:.35rem;display:flex;max-width:640px;">
        <div style="position:relative;flex:1;">
          <svg style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);
               color:rgba(255,255,255,.45);pointer-events:none;" width="16" height="16" fill="none"
               stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input id="heroSearch" type="text" autocomplete="off"
                 placeholder="Search stocks, sectors, or technical indicators…"
                 style="width:100%;padding:.7rem 1rem .7rem 2.6rem;font-size:.85rem;
                        background:transparent;border:none;color:#fff;outline:none;box-sizing:border-box;">
          <div id="heroDropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;
               border-radius:.875rem;overflow:hidden;background:#fff;border:1px solid #e2e8f0;
               box-shadow:0 20px 60px rgba(0,0,0,.25);z-index:60;"></div>
        </div>
        <button type="button" onclick="document.getElementById('heroSearch').focus()"
                style="background:#fff;color:#14532D;padding:.5rem 1.1rem;border-radius:.5rem;border:none;
                       font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.35rem;flex-shrink:0;">
          <span class="material-symbols-outlined" style="font-size:16px;">search</span>
          Analyze
        </button>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem;">
        @foreach([['icon'=>'trending_up','label'=>'RSI > 70','route'=>'screener.index'],['icon'=>'show_chart','label'=>'MACD Crossover','route'=>'screener.index'],['icon'=>'candlestick_chart','label'=>'Volume Spikes','route'=>'screener.index']] as $chip)
        <a href="{{ route($chip['route']) }}" style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);
               border-radius:9999px;padding:.35rem .85rem;font-size:.7rem;color:#fff;text-decoration:none;
               display:inline-flex;align-items:center;gap:.3rem;">
          <span class="material-symbols-outlined" style="font-size:13px;">{{ $chip['icon'] }}</span>
          {{ $chip['label'] }}
        </a>
        @endforeach
      </div>
    </div>

    <div class="hero-stats-grid">
      <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border-radius:.75rem;padding:1rem;border:1px solid rgba(255,255,255,.15);">
        <p style="font-size:.7rem;color:rgba(255,255,255,.7);margin:0 0 .3rem;">NEPSE Index</p>
        @if($nepseIndex)
        <p style="font-size:1.3rem;font-weight:700;color:#fff;margin:0;font-family:'JetBrains Mono',monospace;">{{ number_format($nepseIndex['close'], 2) }}</p>
        <p style="font-size:.72rem;margin:.25rem 0 0;color:{{ $nepseIndex['change'] >= 0 ? '#86efac' : '#fca5a5' }};display:flex;align-items:center;gap:.15rem;">
          <span class="material-symbols-outlined" style="font-size:13px;">{{ $nepseIndex['change'] >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
          {{ $nepseIndex['change'] >= 0 ? '+' : '' }}{{ number_format($nepseIndex['change'], 2) }} ({{ number_format($nepseIndex['change_percent'], 2) }}%)
        </p>
        @else
        <p style="font-size:1.1rem;font-weight:700;color:rgba(255,255,255,.5);margin:0;">—</p>
        @endif
      </div>
      <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border-radius:.75rem;padding:1rem;border:1px solid rgba(255,255,255,.15);">
        <p style="font-size:.7rem;color:rgba(255,255,255,.7);margin:0 0 .3rem;">Market Status</p>
        <p style="font-size:1.05rem;font-weight:700;color:#fff;margin:0;display:flex;align-items:center;gap:.4rem;">
          <span style="width:9px;height:9px;border-radius:50%;background:{{ $marketStatus['open'] ? '#4ade80' : '#94a3b8' }};
                 display:inline-block;{{ $marketStatus['open'] ? 'animation:livePulse 1.5s infinite;' : '' }}"></span>
          {{ $marketStatus['open'] ? 'Open' : 'Closed' }}
        </p>
        <p style="font-size:.7rem;color:rgba(255,255,255,.65);margin:.3rem 0 0;">
          {{ $marketStatus['open'] ? 'Closes in ' . $marketStatus['closesIn'] : 'Sun–Thu, 11:00–15:00 NPT' }}
        </p>
      </div>
    </div>
  </div>
</div>

{{-- ════ YOUR PORTFOLIO ════════════════════════════════════════════════════ --}}
@if($portfolioOverview)
<div class="fade-up" style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;
     padding:1.5rem;margin-bottom:1.75rem;box-shadow:0 1px 3px rgba(0,0,0,.04);">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <div style="display:flex;align-items:center;gap:.5rem;font-size:1rem;font-weight:700;color:#0f172a;">
      <span class="material-symbols-outlined" style="color:#416550;font-size:20px;">business_center</span>
      Your Portfolio
    </div>
    <a href="{{ route('portfolio.overview') }}" style="font-size:.75rem;color:#14532D;text-decoration:none;
       padding:.3rem .75rem;border-radius:.5rem;background:#DCFCE7;border:1px solid #bbf7d0;font-weight:600;
       display:inline-flex;align-items:center;gap:.25rem;">
      Full Portfolio <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
    </a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;">
    @php
      $pStats = [
        ['label'=>'Investment', 'value'=>$portfolioOverview['investment'], 'signed'=>false],
        ['label'=>'Market Value', 'value'=>$portfolioOverview['market_value'], 'signed'=>false],
        ["label"=>"Day G/L", 'value'=>$portfolioOverview['day_gain_loss'], 'signed'=>true],
        ['label'=>'Unrealized G/L', 'value'=>$portfolioOverview['unrealized'], 'signed'=>true],
        ['label'=>'Realized G/L', 'value'=>$portfolioOverview['realized'], 'signed'=>true],
      ];
    @endphp
    @foreach($pStats as $ps)
    <div>
      <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-bottom:.3rem;">
        {{ $ps['label'] }}
      </div>
      <div style="font-size:1.15rem;font-weight:800;font-family:'JetBrains Mono',monospace;
           color:{{ $ps['signed'] ? ($ps['value'] >= 0 ? '#16a34a' : '#dc2626') : '#0f172a' }};">
        {{ $ps['signed'] && $ps['value'] > 0 ? '+' : '' }}{{ number_format($ps['value'], 2) }}
      </div>
    </div>
    @endforeach
  </div>
  @if($portfolioOverview['stock_count'] === 0)
  <div style="margin-top:1rem;font-size:.8rem;color:#94a3b8;">
    You haven't added any holdings yet. <a href="{{ route('portfolio.adjust') }}" style="color:#14532D;font-weight:600;">Add your first transaction →</a>
  </div>
  @endif
</div>
@endif

{{-- ════ YOUR WATCHLIST ════════════════════════════════════════════════════ --}}
@if($watchlistPreview && $watchlistPreview->isNotEmpty())
<div class="fade-up" style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;
     padding:1.5rem;margin-bottom:1.75rem;box-shadow:0 1px 3px rgba(0,0,0,.04);">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
    <div style="display:flex;align-items:center;gap:.5rem;font-size:1rem;font-weight:700;color:#0f172a;">
      <span class="material-symbols-outlined" style="color:#416550;font-size:20px;">star</span>
      Your Watchlist
    </div>
    <a href="{{ route('watchlist.index') }}" style="font-size:.75rem;color:#14532D;text-decoration:none;
       padding:.3rem .75rem;border-radius:.5rem;background:#DCFCE7;border:1px solid #bbf7d0;font-weight:600;
       display:inline-flex;align-items:center;gap:.25rem;">
      Full Watchlist <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
    </a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.875rem;">
    @foreach($watchlistPreview->take(6) as $w)
    <a href="{{ route('stocks.show', $w['symbol']) }}" class="card-hover"
       style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.75rem .875rem;
              background:#f8fafc;border:1px solid #e2e8f0;border-radius:.625rem;text-decoration:none;">
      <div style="min-width:0;">
        <div style="font-size:.85rem;font-weight:700;color:#0f172a;">{{ $w['symbol'] }}</div>
        <div style="font-size:.68rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($w['name'], 18) }}</div>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:.8rem;font-weight:700;font-family:'JetBrains Mono',monospace;color:#0f172a;">
          {{ $w['ltp'] !== null ? number_format($w['ltp'], 2) : '—' }}
        </div>
        @if($w['change_percent'] !== null)
        <div style="font-size:.7rem;font-weight:600;" class="{{ $w['change_percent'] >= 0 ? 'change-pos' : 'change-neg' }}">
          {{ $w['change_percent'] >= 0 ? '+' : '' }}{{ number_format($w['change_percent'], 2) }}%
        </div>
        @endif
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- ════ MARKET SUMMARY ════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d1" style="margin-bottom:1.75rem;">
  <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:1rem;font-weight:700;color:#0f172a;">
    <span class="material-symbols-outlined" style="color:#14532D;font-size:20px;">insert_chart</span>
    Market Summary
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
    @php
    $summaryCards = [
      ['label'=>'Total Turnover', 'value'=>$marketSummary['turnover'], 'icon'=>'currency_rupee', 'clr'=>'#14532D', 'bg'=>'#DCFCE7'],
      ['label'=>'Shares Traded',  'value'=>$marketSummary['volume'],   'icon'=>'pie_chart',       'clr'=>'#3c6755', 'bg'=>'#e8eeff'],
      ['label'=>'Active Stocks',  'value'=>number_format($totalStocks),'icon'=>'apartment',        'clr'=>'#4f46e5', 'bg'=>'#eef2ff'],
    ];
    @endphp
    @foreach($summaryCards as $sc)
    <div class="card-hover" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.25rem;">
      <div style="width:40px;height:40px;border-radius:9999px;background:{{ $sc['bg'] }};color:{{ $sc['clr'] }};
           display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
        <span class="material-symbols-outlined" style="font-size:20px;">{{ $sc['icon'] }}</span>
      </div>
      <div style="font-size:.8rem;color:#64748b;margin-bottom:.25rem;">{{ $sc['label'] }}</div>
      <div style="font-size:1.4rem;font-weight:700;color:#0f172a;font-family:'JetBrains Mono',monospace;">{{ $sc['value'] }}</div>
    </div>
    @endforeach
  </div>
</div>

{{-- ════ SECTOR PERFORMANCE + TOP VOLUME ═══════════════════════════════════ --}}
<div class="fade-up fade-d2 sector-volume-grid" style="margin-bottom:1.75rem;">

  {{-- Sector Performance --}}
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;
         border-bottom:1px solid #f1f5f9;padding-bottom:1rem;margin-bottom:1.25rem;">
      <div style="font-size:.95rem;font-weight:700;color:#0f172a;">Sector Performance</div>
      <a href="{{ route('stocks.index') }}" style="font-size:.75rem;color:#14532D;text-decoration:none;font-weight:600;
         display:inline-flex;align-items:center;gap:.15rem;">
        View All <span class="material-symbols-outlined" style="font-size:15px;">chevron_right</span>
      </a>
    </div>
    <div style="display:flex;flex-direction:column;gap:1.25rem;">
      @forelse($sectorPerformance as $sec)
      <div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:.4rem;">
          <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:32px;height:32px;border-radius:.4rem;background:#f0fdf4;color:#14532D;
                 display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <span class="material-symbols-outlined" style="font-size:16px;">account_balance</span>
            </div>
            <div>
              <div style="font-size:.85rem;color:#0f172a;font-weight:600;">{{ $sec['name'] }}</div>
              <div style="font-size:.72rem;color:#94a3b8;">{{ $sec['count'] }} companies</div>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:.85rem;color:#0f172a;font-weight:600;">{{ $sec['weight'] }}%</div>
            <div style="font-size:.72rem;font-weight:600;color:{{ $sec['change'] >= 0 ? '#16a34a' : '#dc2626' }};
                 display:flex;align-items:center;justify-content:flex-end;gap:.1rem;">
              <span class="material-symbols-outlined" style="font-size:12px;">{{ $sec['change'] >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
              {{ $sec['change'] >= 0 ? '+' : '' }}{{ $sec['change'] }}%
            </div>
          </div>
        </div>
        <div style="width:100%;background:#f1f5f9;height:6px;border-radius:9999px;overflow:hidden;">
          <div style="background:#14532D;height:100%;border-radius:9999px;width:{{ $sec['weight'] }}%;"></div>
        </div>
      </div>
      @empty
      <div style="font-size:.85rem;color:#94a3b8;text-align:center;padding:1rem 0;">Sector data is temporarily unavailable.</div>
      @endforelse
    </div>
  </div>

  {{-- Top Volume --}}
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.5rem;display:flex;flex-direction:column;">
    <div style="font-size:.95rem;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:1rem;margin-bottom:1.25rem;">
      Top Volume
    </div>
    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:1rem;flex:1;">
      @forelse($topVolume as $v)
      <li>
        <a href="{{ route('stocks.show', $v['symbol']) }}" class="stock-row"
           style="display:flex;justify-content:space-between;align-items:center;text-decoration:none;
                  padding:.4rem;margin:-.4rem;border-radius:.6rem;">
          <div style="display:flex;align-items:center;gap:.75rem;min-width:0;">
            <div style="width:36px;height:36px;border-radius:9999px;background:#DCFCE7;color:#14532D;
                 display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;">
              {{ Str::substr($v['symbol'], 0, 1) }}
            </div>
            <div style="min-width:0;">
              <div style="font-size:.85rem;font-weight:600;color:#0f172a;">{{ $v['symbol'] }}</div>
              <div style="font-size:.72rem;color:#94a3b8;">Rs. {{ number_format($v['ltp'], 2) }}</div>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:.72rem;color:#64748b;">Vol: {{ number_format($v['volume'] / 1000, 0) }}K</div>
            <div style="font-size:.72rem;font-weight:600;color:{{ $v['change_percent'] >= 0 ? '#16a34a' : '#dc2626' }};">
              {{ $v['change_percent'] >= 0 ? '+' : '' }}{{ number_format($v['change_percent'], 1) }}%
            </div>
          </div>
        </a>
      </li>
      @empty
      <li style="font-size:.85rem;color:#94a3b8;text-align:center;padding:1rem 0;">No data available.</li>
      @endforelse
    </ul>
    <a href="{{ route('screener.index') }}" style="display:block;text-align:center;margin-top:1rem;padding:.65rem;
       border:1px solid #e2e8f0;border-radius:.6rem;font-size:.8rem;font-weight:600;color:#0f172a;text-decoration:none;">
      View Full Screener
    </a>
  </div>
</div>

{{-- ════ LIVE TICKER ═══════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d3" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
     overflow:hidden;margin-bottom:1rem;display:flex;align-items:center;">
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
          '<span style="font-size:.7rem;color:#14532D;padding:.2rem .6rem;border-radius:9999px;background:#DCFCE7;border:1px solid #bbf7d0;">Analyse →</span>' +
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
