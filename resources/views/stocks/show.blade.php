@extends('layouts.app')
@section('title', $stock->symbol . ' — ' . $stock->name)

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
  --up:#16a34a;--dn:#dc2626;--neu:#64748b;
  --up-bg:#f0fdf4;--dn-bg:#fef2f2;--neu-bg:#f8fafc;
  --up-bd:#bbf7d0;--dn-bd:#fecaca;--neu-bd:#e2e8f0;
  --blue:#2563eb;--blue-bg:#eff6ff;--blue-bd:#bfdbfe;
}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05);}
.card-sm{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.625rem;padding:.875rem;}
.section-lbl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:.75rem;}
.sig-buy{background:var(--up-bg);border:2px solid var(--up-bd);}
.sig-sell{background:var(--dn-bg);border:2px solid var(--dn-bd);}
.sig-hold{background:#fefce8;border:2px solid #fde68a;}
.tr-up{background:var(--up-bg);color:var(--up);border:1px solid var(--up-bd);}
.tr-dn{background:var(--dn-bg);color:var(--dn);border:1px solid var(--dn-bd);}
.tr-sw{background:var(--neu-bg);color:var(--neu);border:1px solid var(--neu-bd);}
.mono{font-family:'JetBrains Mono',monospace;}
.tab-btn{padding:.35rem .8rem;border-radius:.5rem;font-size:.78rem;font-weight:500;border:1px solid #e2e8f0;background:#f8fafc;color:#64748b;cursor:pointer;transition:all .12s;}
.tab-btn.active,.tab-btn:hover{background:var(--blue);color:#fff;border-color:var(--blue);}
.stat-row{display:flex;justify-content:space-between;align-items:center;padding:.3rem 0;border-bottom:1px solid #f1f5f9;}
.stat-row:last-child{border-bottom:none;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
</style>
@endpush

@section('content')
@php
  $ms    = $marketSummary ?? [];
  $lp    = $prices->first();

  $ltp     = (float)($ms['close']  ?? $lp?->close ?? 0);
  $open    = (float)($ms['open']   ?? $lp?->open  ?? 0);
  $high    = (float)($ms['high']   ?? $lp?->high  ?? 0);
  $low     = (float)($ms['low']    ?? $lp?->low   ?? 0);
  $prevCls = (float)($ms['prev_close'] ?? 0);
  $chgPt   = (float)($ms['point_change'] ?? $lp?->change ?? 0);
  $chgPct  = (float)($ms['percentage_change'] ?? $lp?->change_percent ?? 0);
  $vol     = (int)($ms['volume'] ?? $lp?->volume ?? 0);
  $tdate   = $ms['date'] ?? $lp?->date?->toDateString() ?? '';
  $isUp    = $chgPct >= 0;

  $wkPct  = (float)($ms['percentage_change_weekly']  ?? 0);
  $moPct  = (float)($ms['percentage_change_monthly'] ?? 0);
  $wkPtChg = (float)($ms['point_change_weekly'] ?? 0);
  $moPtChg = (float)($ms['point_change_monthly'] ?? 0);

  $hl52     = $highLowStats ?? [];
  $hi52     = (float)($hl52['weeks_high_52']    ?? 0);
  $lo52     = (float)($hl52['weeks_low_52']     ?? 0);
  $avg120   = (float)($hl52['days_avg_120']     ?? 0);
  $avg180   = (float)($hl52['days_avg_180']     ?? 0);
  $avgVol50 = (int)  ($hl52['days_avg_volume_50'] ?? 0);

  $isBuy  = ($signal->signal_type ?? '') === 'BUY';
  $isSell = ($signal->signal_type ?? '') === 'SELL';
  $sigCls = $isBuy ? 'sig-buy' : ($isSell ? 'sig-sell' : 'sig-hold');
  $sigClr = $isBuy ? '#16a34a' : ($isSell ? '#dc2626' : '#ca8a04');

  $sup = $supportLevels    ?? [];
  $res = $resistanceLevels ?? [];
  $var = $varMonthly       ?? [];
  $ab  = $alphaBeta        ?? [];
@endphp

<div class="space-y-5">

{{-- ════ HERO ═══════════════════════════════════════════════════════════ --}}
<div class="card" style="padding:1.5rem;">
  <div class="flex flex-wrap items-start justify-between gap-4">

    <div>
      <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
        <a href="{{ route('stocks.index') }}" style="font-size:.78rem;color:#94a3b8;text-decoration:none;">← Markets</a>
        @if($stock->sector)
          <span style="color:#e2e8f0;">|</span>
          <span style="font-size:.75rem;padding:.2rem .65rem;border-radius:9999px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd);">{{ $stock->sector->name }}</span>
        @endif
      </div>
      <h1 class="mono" style="font-size:2rem;font-weight:800;color:#0f172a;line-height:1;">{{ $stock->symbol }}</h1>
      <p style="font-size:.9375rem;color:#64748b;margin-top:.3rem;">{{ $stock->name }}</p>
      @if($tdate)
      <p style="font-size:.78rem;color:#94a3b8;margin-top:.4rem;display:flex;align-items:center;gap:.5rem;">
        Last updated: <strong style="color:#64748b;">{{ \Carbon\Carbon::parse($tdate)->format('D, d M Y') }}</strong>
        <span style="display:inline-flex;align-items:center;gap:.3rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9999px;padding:.1rem .5rem;font-size:.7rem;color:#16a34a;">
          <span style="width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.5s infinite;"></span> Live
        </span>
      </p>
      @endif
    </div>

    <div style="text-align:right;">
      <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.2rem;">Last Traded Price</div>
      <div class="mono" style="font-size:2.5rem;font-weight:800;color:#0f172a;line-height:1.1;">NPR {{ number_format($ltp, 2) }}</div>
      <div style="margin-top:.4rem;font-size:1rem;font-weight:700;color:{{ $isUp ? '#16a34a' : '#dc2626' }};">
        {{ $isUp ? '▲' : '▼' }} {{ number_format(abs($chgPt), 2) }}
        &nbsp;({{ $isUp ? '+' : '' }}{{ number_format($chgPct, 2) }}%)
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.4rem;">
        <span style="font-size:.78rem;color:{{ $wkPct >= 0 ? '#16a34a' : '#dc2626' }};">
          1W: {{ $wkPct >= 0 ? '+' : '' }}{{ number_format($wkPct,2) }}%
        </span>
        <span style="font-size:.78rem;color:{{ $moPct >= 0 ? '#16a34a' : '#dc2626' }};">
          1M: {{ $moPct >= 0 ? '+' : '' }}{{ number_format($moPct,2) }}%
        </span>
      </div>
    </div>
  </div>

  {{-- Today OHLCV strip --}}
  <div class="grid grid-cols-3 md:grid-cols-6 gap-3" style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid #f1f5f9;">
    @foreach([
      ['Opening Price', $open,    '#2563eb'],
      ['Day High',      $high,    '#16a34a'],
      ['Day Low',       $low,     '#dc2626'],
      ['Prev Close',    $prevCls, '#64748b'],
      ['Volume',        $vol,     '#7c3aed'],
      ['Turnover',      (float)($ms['amount'] ?? 0), '#0891b2'],
    ] as [$lbl,$val,$clr])
    <div style="text-align:center;padding:.5rem .25rem;">
      <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;">{{ $lbl }}</div>
      <div class="mono" style="font-size:.9375rem;font-weight:700;color:{{ $clr }};">
        @if($lbl==='Volume') {{ number_format($val) }}
        @elseif($lbl==='Turnover') NPR {{ number_format($val/1000000,2) }}M
        @else NPR {{ number_format($val,2) }}
        @endif
      </div>
    </div>
    @endforeach
  </div>

  {{-- 52-week range --}}
  @if($hi52 || $lo52)
  <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
    <div style="font-size:.68rem;color:#94a3b8;margin-bottom:.5rem;font-weight:600;text-transform:uppercase;">52-Week Range</div>
    <div style="display:flex;align-items:center;gap:.75rem;">
      <span class="mono" style="font-size:.82rem;color:#dc2626;font-weight:600;min-width:60px;text-align:right;">{{ number_format($lo52,2) }}</span>
      <div style="flex:1;height:6px;background:#f1f5f9;border-radius:9999px;position:relative;">
        @php $rangePct = ($hi52 > $lo52) ? max(0,min(100,(($ltp-$lo52)/($hi52-$lo52))*100)) : 50; @endphp
        <div style="position:absolute;left:0;height:100%;width:{{ $rangePct }}%;background:linear-gradient(90deg,#ef4444,#22c55e);border-radius:9999px;"></div>
        <div style="position:absolute;top:-5px;left:calc({{ $rangePct }}% - 7px);width:16px;height:16px;background:#2563eb;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.2);"></div>
      </div>
      <span class="mono" style="font-size:.82rem;color:#16a34a;font-weight:600;min-width:60px;">{{ number_format($hi52,2) }}</span>
    </div>
    <div style="display:flex;gap:1.5rem;margin-top:.625rem;font-size:.78rem;flex-wrap:wrap;">
      @if($avg120)<span style="color:#64748b;">120D Avg: <strong class="mono" style="color:#0f172a;">{{ number_format($avg120,2) }}</strong></span>@endif
      @if($avg180)<span style="color:#64748b;">180D Avg: <strong class="mono" style="color:#0f172a;">{{ number_format($avg180,2) }}</strong></span>@endif
      @if($avgVol50)<span style="color:#64748b;">50D Avg Vol: <strong class="mono" style="color:#0f172a;">{{ number_format($avgVol50) }}</strong></span>@endif
    </div>
  </div>
  @endif
</div>

{{-- ════ SIGNAL ═══════════════════════════════════════════════════════════ --}}
@if($signal)
<div class="rounded-xl p-5 {{ $sigCls }}">
  <div class="flex flex-wrap gap-6 items-start">

    {{-- Signal type + why --}}
    <div style="min-width:220px;flex:1;">
      <div class="section-lbl">AI Analytics Signal</div>
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.625rem;">
        <span class="mono" style="font-size:1.75rem;font-weight:800;color:{{ $sigClr }};">
          {{ $isBuy ? '▲ BUY' : ($isSell ? '▼ SELL' : '◆ HOLD') }}
        </span>
        <span style="font-size:.8rem;font-weight:700;padding:.25rem .75rem;border-radius:9999px;
          background:{{ $isBuy ? '#dcfce7' : ($isSell ? '#fee2e2' : '#fef9c3') }};
          color:{{ $sigClr }};
          border:1px solid {{ $isBuy ? '#86efac' : ($isSell ? '#fca5a5' : '#fde68a') }};">
          {{ $signal->confidence ?? 0 }}% confidence
        </span>
      </div>

      {{-- Confidence bar --}}
      <div style="background:#e2e8f0;border-radius:9999px;height:5px;margin-bottom:.875rem;overflow:hidden;">
        <div style="height:5px;border-radius:9999px;width:{{ $signal->confidence ?? 0 }}%;background:{{ $isBuy ? '#22c55e' : ($isSell ? '#ef4444' : '#eab308') }};"></div>
      </div>

      {{-- WHY section --}}
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:.5rem;">
        Why this {{ $isBuy ? 'Buy' : ($isSell ? 'Sell' : 'Hold') }} signal?
      </div>
      <ul style="list-style:none;padding:0;margin:0 0 .5rem 0;">
        @foreach($signal->reasons ?? [] as $r)
        <li style="font-size:.8rem;color:#374151;display:flex;gap:.4rem;margin-bottom:.35rem;align-items:flex-start;line-height:1.4;">
          <span style="color:{{ $sigClr }};flex-shrink:0;font-size:.9rem;margin-top:1px;">{{ $isBuy ? '✓' : ($isSell ? '✗' : '·') }}</span>
          {{ $r }}
        </li>
        @endforeach
      </ul>
    </div>

    <div style="min-width:280px;flex:1.5;">
      <div class="section-lbl">{{ $isBuy ? 'Buy Zone & Targets' : ($isSell ? 'Sell Zone & Targets' : 'Key Levels') }}</div>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">

        <div class="card-sm" style="border-color:{{ $isBuy ? '#bbf7d0' : '#fecaca' }};">
          <div style="font-size:.65rem;color:{{ $sigClr }};margin-bottom:.3rem;">{{ $isBuy ? '✅ Buy Zone' : '⚠ Entry Zone' }}</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:{{ $sigClr }};">
            {{ number_format($signal->entry_min ?? 0, 2) }}
          </div>
          <div class="mono" style="font-size:.8rem;color:#64748b;">— {{ number_format($signal->entry_max ?? 0, 2) }}</div>
        </div>

        <div class="card-sm" style="border-color:#fecaca;background:#fff5f5;">
          <div style="font-size:.65rem;color:#dc2626;margin-bottom:.3rem;">🛑 Stop Loss</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:#dc2626;">
            NPR {{ number_format($signal->stop_loss ?? 0, 2) }}
          </div>
          @php $slPct = ($signal->price_at_signal??0) > 0 ? abs(($signal->stop_loss - $signal->price_at_signal) / $signal->price_at_signal * 100) : 0; @endphp
          <div style="font-size:.7rem;color:#ef4444;">-{{ number_format($slPct,1) }}% risk</div>
        </div>

        <div class="card-sm" style="border-color:#bbf7d0;background:#f0fdf4;">
          <div style="font-size:.65rem;color:#16a34a;margin-bottom:.3rem;">🎯 Target 1</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:#16a34a;">
            NPR {{ number_format($signal->target_1 ?? 0, 2) }}
          </div>
          @php $t1Pct = ($signal->price_at_signal??0) > 0 ? abs(($signal->target_1 - $signal->price_at_signal) / $signal->price_at_signal * 100) : 0; @endphp
          <div style="font-size:.7rem;color:#22c55e;">+{{ number_format($t1Pct,1) }}% upside</div>
        </div>

        <div class="card-sm" style="border-color:#bbf7d0;background:#f0fdf4;">
          <div style="font-size:.65rem;color:#16a34a;margin-bottom:.3rem;">🎯 Target 2</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:#16a34a;">
            NPR {{ number_format($signal->target_2 ?? 0, 2) }}
          </div>
          @php $t2Pct = ($signal->price_at_signal??0) > 0 ? abs(($signal->target_2 - $signal->price_at_signal) / $signal->price_at_signal * 100) : 0; @endphp
          <div style="font-size:.7rem;color:#22c55e;">+{{ number_format($t2Pct,1) }}% upside</div>
        </div>

        <div class="card-sm">
          <div style="font-size:.65rem;color:#64748b;margin-bottom:.3rem;">⚖ Risk : Reward</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:#0f172a;">1 : {{ number_format($signal->risk_reward??0,2) }}</div>
          <div style="font-size:.7rem;color:#94a3b8;">ratio</div>
        </div>

        <div class="card-sm">
          <div style="font-size:.65rem;color:#64748b;margin-bottom:.3rem;">📍 At Analysis</div>
          <div class="mono" style="font-size:1rem;font-weight:800;color:#0f172a;">NPR {{ number_format($signal->price_at_signal??0,2) }}</div>
          <div style="font-size:.7rem;color:#94a3b8;">signal price</div>
        </div>

      </div>
    </div>
  </div>
  <div style="margin-top:.875rem;padding-top:.75rem;border-top:1px solid rgba(0,0,0,.07);font-size:.7rem;color:#94a3b8;">
    ⚠ Algorithmic signal — educational only. Not financial/investment advice. Always do your own research before trading.
  </div>
</div>
@endif

{{-- ════ MULTI-TIMEFRAME TREND ANALYSIS ══════════════════════════════════ --}}
@auth
@if($trend)
@php
  $tfItems = [
    ['Very Short Term', 'EMA5 vs EMA10', '~2 weeks', $trend['very_short'] ?? []],
    ['Short Term',      'Price vs SMA20', '~1 month', $trend['short']      ?? []],
    ['Mid Term',        'SMA20 vs SMA50', '~3 months',$trend['mid']        ?? []],
    ['Long Term',       'Price vs SMA200','~1 year',  $trend['long']       ?? []],
  ];
  $consensus = $trend['consensus'] ?? [];
  $conSig    = $consensus['signal'] ?? 'NEUTRAL';
  $conBuy    = (int)($consensus['buy_count'] ?? 0);
  $conSell   = (int)($consensus['sell_count'] ?? 0);
  $conDesc   = $consensus['description'] ?? '';
  $conClr    = in_array($conSig, ['BUY','MILD BUY'])    ? '#16a34a'
             : (in_array($conSig, ['SELL','MILD SELL']) ? '#dc2626' : '#64748b');
  $conBg     = in_array($conSig, ['BUY','MILD BUY'])    ? '#f0fdf4'
             : (in_array($conSig, ['SELL','MILD SELL']) ? '#fef2f2' : '#f8fafc');
  $conBd     = in_array($conSig, ['BUY','MILD BUY'])    ? '#bbf7d0'
             : (in_array($conSig, ['SELL','MILD SELL']) ? '#fecaca' : '#e2e8f0');
@endphp

{{-- Consensus bar --}}
<div class="card" style="padding:1.25rem 1.5rem;border-color:{{ $conBd }};background:{{ $conBg }};">
  <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
    <div>
      <div class="section-lbl" style="margin-bottom:.35rem;">Overall Trend Consensus</div>
      <div style="display:flex;align-items:center;gap:.875rem;">
        <span class="mono" style="font-size:1.5rem;font-weight:800;color:{{ $conClr }};">
          {{ in_array($conSig,['BUY','MILD BUY']) ? '▲' : (in_array($conSig,['SELL','MILD SELL']) ? '▼' : '◆') }}
          {{ $conSig }}
        </span>
        <div style="display:flex;gap:.375rem;">
          @for($i=0;$i<4;$i++)
          @php
            $filled = $i < $conBuy ? '#22c55e' : ($i >= (4-$conSell) ? '#ef4444' : '#e2e8f0');
          @endphp
          <div style="width:28px;height:8px;border-radius:9999px;background:{{ $filled }};"></div>
          @endfor
        </div>
        <span style="font-size:.8rem;color:#64748b;">{{ $conBuy }}/4 Buy &nbsp;·&nbsp; {{ $conSell }}/4 Sell</span>
      </div>
    </div>
    <p style="font-size:.82rem;color:#374151;max-width:420px;line-height:1.5;">{{ $conDesc }}</p>
  </div>
</div>

{{-- Per-timeframe cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
  @foreach($tfItems as [$tfLabel, $tfBasis, $tfPeriod, $tf])
  @php
    $dir    = $tf['direction']   ?? 'N/A';
    $sig    = $tf['signal']      ?? 'HOLD';
    $str    = $tf['strength']    ?? 'N/A';
    $qual   = $tf['quality']     ?? '';
    $desc   = $tf['description'] ?? '';
    $pct    = $tf['pct_diff']    ?? 0;
    $isUp2  = $dir === 'Uptrend';
    $isDn2  = $dir === 'Downtrend';
    $isSide = $dir === 'Sideways';
    $clr    = $isUp2 ? '#16a34a' : ($isDn2 ? '#dc2626' : '#64748b');
    $bg     = $isUp2 ? '#f0fdf4' : ($isDn2 ? '#fef2f2' : '#f8fafc');
    $bd     = $isUp2 ? '#bbf7d0' : ($isDn2 ? '#fecaca' : '#e2e8f0');
    $arrow  = $isUp2 ? '↑' : ($isDn2 ? '↓' : '→');
    $sigClr2= $sig === 'BUY' ? '#16a34a' : ($sig === 'SELL' ? '#dc2626' : '#ca8a04');
    $sigBg2 = $sig === 'BUY' ? '#dcfce7' : ($sig === 'SELL' ? '#fee2e2' : '#fef9c3');
    $sigBd2 = $sig === 'BUY' ? '#86efac' : ($sig === 'SELL' ? '#fca5a5' : '#fde68a');
    $strClr = $str === 'Strong' ? $clr : ($str === 'Moderate' ? $clr : '#94a3b8');
  @endphp
  <div class="card" style="border-color:{{ $bd }};padding:1rem 1.125rem;">
    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.625rem;">
      <div>
        <div style="font-size:.65rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.07em;">{{ $tfLabel }}</div>
        <div style="font-size:.7rem;color:#64748b;margin-top:.1rem;">{{ $tfBasis }}</div>
      </div>
      <span style="font-size:.7rem;padding:.15rem .5rem;border-radius:9999px;background:{{ $sigBg2 }};color:{{ $sigClr2 }};border:1px solid {{ $sigBd2 }};font-weight:700;white-space:nowrap;">{{ $sig }}</span>
    </div>

    {{-- Direction badge --}}
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
      <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:1rem;font-weight:800;color:{{ $clr }};">
        {{ $arrow }} {{ $dir }}
      </span>
      @if($str !== 'N/A')
      <span style="font-size:.65rem;padding:.15rem .4rem;border-radius:.25rem;background:{{ $bg }};color:{{ $strClr }};font-weight:600;">{{ $str }}</span>
      @endif
    </div>

    {{-- Quality label --}}
    @if($qual && $qual !== 'N/A')
    <div style="font-size:.75rem;font-weight:600;color:{{ $clr }};margin-bottom:.5rem;display:flex;align-items:center;gap:.35rem;">
      @if(str_contains($qual,'Good') || str_contains($qual,'Strong') || str_contains($qual,'Sustained'))
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></span>
      @elseif(str_contains($qual,'Few') || str_contains($qual,'Just') || str_contains($qual,'Early') || str_contains($qual,'Short-term Rally'))
        <span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:{{ $clr }};flex-shrink:0;"></span>
      @else
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e2e8f0;flex-shrink:0;"></span>
      @endif
      {{ $qual }}
    </div>
    @endif

    {{-- % diff pill --}}
    @if($pct != 0)
    <div style="font-size:.7rem;color:#94a3b8;margin-bottom:.625rem;" class="mono">
      Gap: <strong style="color:{{ $clr }};">{{ $pct >= 0 ? '+' : '' }}{{ number_format($pct,2) }}%</strong>
      &nbsp;·&nbsp; {{ $tfPeriod }}
    </div>
    @endif

    {{-- Description --}}
    <p style="font-size:.75rem;color:#374151;line-height:1.55;border-top:1px solid {{ $bd }};padding-top:.5rem;margin:0;">
      {{ $desc }}
    </p>
  </div>
  @endforeach
</div>
@endif
@else
{{-- Guest lock: trend analysis --}}
<div style="position:relative;border-radius:1rem;overflow:hidden;margin-bottom:1.25rem;">
  <div style="filter:blur(5px);user-select:none;pointer-events:none;display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;padding:.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:1rem;">
    @foreach(['Very Short Term','Short Term','Mid Term','Long Term'] as $tl)
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem;">
      <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;width:60%;margin-bottom:.5rem;"></div>
      <div style="height:1.2rem;background:#dcfce7;border-radius:.375rem;width:80%;margin-bottom:.5rem;"></div>
      <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;width:90%;"></div>
      <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;width:70%;margin-top:.375rem;"></div>
    </div>
    @endforeach
  </div>
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.75rem;background:rgba(255,255,255,.72);backdrop-filter:blur(2px);border-radius:1rem;">
    <div style="width:2.5rem;height:2.5rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;">
      <svg style="width:1.1rem;height:1.1rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <div style="text-align:center;">
      <div style="font-weight:700;font-size:.9375rem;color:#0f172a;margin-bottom:.25rem;">Login to view Trend Analysis</div>
      <div style="font-size:.8125rem;color:#64748b;">Multi-timeframe signals: Very Short · Short · Mid · Long</div>
    </div>
    <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.25rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:.875rem;font-weight:600;border-radius:.625rem;text-decoration:none;box-shadow:0 2px 8px rgba(79,70,229,.35);">
      <svg style="width:.875rem;height:.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
      Sign in free
    </a>
  </div>
</div>
@endauth

{{-- ════ INDICATORS ════════════════════════════════════════════════════════ --}}
@if($indicator)
<div class="card">
  <div class="section-lbl">Indicators</div>
  @php $rsi = $indicator->rsi_14 ?? 50; @endphp
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="card-sm">
      <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.3rem;">RSI (14)</div>
      <div class="mono" style="font-size:1.5rem;font-weight:800;color:{{ $rsi<30?'#16a34a':($rsi>70?'#dc2626':'#0f172a') }};">{{ number_format($rsi,1) }}</div>
      <div style="background:#e2e8f0;height:4px;border-radius:9999px;margin:.375rem 0;overflow:hidden;">
        <div style="height:4px;border-radius:9999px;width:{{ min(100,$rsi) }}%;background:{{ $rsi<30?'#22c55e':($rsi>70?'#ef4444':'#3b82f6') }};"></div>
      </div>
      <div style="font-size:.7rem;color:#64748b;">{{ $rsi<30?'Oversold — buy zone':($rsi>70?'Overbought — caution':($rsi>=50?'Bullish momentum':'Neutral')) }}</div>
    </div>
    <div class="card-sm">
      <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.3rem;">MACD</div>
      @php $hist=$indicator->macd_histogram??0; @endphp
      <div class="mono" style="font-size:1.1rem;font-weight:800;color:{{ $hist>=0?'#16a34a':'#dc2626' }};">{{ number_format($indicator->macd??0,4) }}</div>
      <div class="mono" style="font-size:.75rem;color:#64748b;margin-top:.2rem;">Sig: {{ number_format($indicator->macd_signal??0,4) }}</div>
      <div class="mono" style="font-size:.7rem;color:{{ $hist>=0?'#16a34a':'#dc2626' }};">Hist: {{ $hist>=0?'+':'' }}{{ number_format($hist,4) }}</div>
    </div>
    <div class="card-sm">
      <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.3rem;">Moving Averages</div>
      <div class="stat-row"><span style="font-size:.75rem;color:#64748b;">SMA 20</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#0f172a;">{{ number_format($indicator->sma_20??0,2) }}</span></div>
      <div class="stat-row"><span style="font-size:.75rem;color:#64748b;">SMA 50</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#0f172a;">{{ number_format($indicator->sma_50??0,2) }}</span></div>
      @if($indicator->sma_200)<div class="stat-row"><span style="font-size:.75rem;color:#64748b;">SMA 200</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#0f172a;">{{ number_format($indicator->sma_200,2) }}</span></div>@endif
    </div>
    <div class="card-sm">
      <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.3rem;">Bollinger Bands</div>
      <div class="stat-row"><span style="font-size:.75rem;color:#dc2626;">Upper</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#dc2626;">{{ number_format($indicator->bb_upper??0,2) }}</span></div>
      <div class="stat-row"><span style="font-size:.75rem;color:#64748b;">Middle</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#64748b;">{{ number_format($indicator->bb_middle??0,2) }}</span></div>
      <div class="stat-row"><span style="font-size:.75rem;color:#16a34a;">Lower</span><span class="mono" style="font-size:.8rem;font-weight:600;color:#16a34a;">{{ number_format($indicator->bb_lower??0,2) }}</span></div>
    </div>
  </div>
</div>
@endif

{{-- ════ 7-DAY PRICE PREDICTION ══════════════════════════════════════════ --}}
@auth
@if(!empty($prediction7d))
<div class="card" style="overflow:hidden;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
    <div>
      <div class="section-lbl" style="margin-bottom:.2rem;">7-Day Price Prediction</div>
      <p style="font-size:.75rem;color:#94a3b8;margin:0;">AI forecast using trend, RSI, MACD, day-of-week patterns &amp; momentum</p>
    </div>
    <span style="font-size:.68rem;padding:.2rem .6rem;border-radius:9999px;background:#fef9c3;color:#92400e;border:1px solid #fde68a;font-weight:600;">
      ⚠ For educational purposes only
    </span>
  </div>

  {{-- Timeline strip --}}
  <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem;">
    @foreach($prediction7d as $day)
    @php
      $isUp   = $day['direction'] === 'up';
      $isDn   = $day['direction'] === 'down';
      $bgClr  = $isUp ? '#f0fdf4' : ($isDn ? '#fef2f2' : '#f8fafc');
      $bdClr  = $isUp ? '#bbf7d0' : ($isDn ? '#fecaca' : '#e2e8f0');
      $txClr  = $isUp ? '#16a34a' : ($isDn ? '#dc2626' : '#64748b');
      $arrow  = $isUp ? '▲' : ($isDn ? '▼' : '▬');
      $label  = $isUp ? 'UP' : ($isDn ? 'DOWN' : 'SIDE');
      $confW  = $day['confidence'];
    @endphp
    <div style="background:{{ $bgClr }};border:1px solid {{ $bdClr }};border-radius:.75rem;padding:.75rem .5rem;text-align:center;">
      {{-- Day label --}}
      <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:.6rem;">
        {{ $day['day_display'] }}
      </div>
      {{-- Arrow direction --}}
      <div style="font-size:1.5rem;color:{{ $txClr }};font-weight:800;line-height:1;margin-bottom:.25rem;">{{ $arrow }}</div>
      {{-- UP/DOWN label --}}
      <div style="font-size:.65rem;font-weight:800;color:{{ $txClr }};letter-spacing:.05em;margin-bottom:.5rem;">{{ $label }}</div>
      {{-- Predicted price --}}
      <div style="font-family:'JetBrains Mono',monospace;font-size:.7rem;font-weight:700;color:#0f172a;margin-bottom:.2rem;">
        {{ number_format($day['predicted_price'], 2) }}
      </div>
      {{-- Change % --}}
      <div style="font-size:.65rem;font-weight:600;color:{{ $txClr }};">
        {{ $day['change_pct'] >= 0 ? '+' : '' }}{{ number_format($day['change_pct'], 2) }}%
      </div>
      {{-- Confidence bar --}}
      <div style="margin:.5rem 0 .25rem;background:#e2e8f0;border-radius:9999px;height:3px;overflow:hidden;">
        <div style="height:3px;width:{{ $confW }}%;border-radius:9999px;background:{{ $isUp ? '#22c55e' : ($isDn ? '#ef4444' : '#94a3b8') }};"></div>
      </div>
      <div style="font-size:.6rem;color:#94a3b8;">{{ $confW }}% conf</div>
    </div>
    @endforeach
  </div>

  {{-- Reason rows (expandable) --}}
  <div style="margin-top:1rem;border-top:1px solid #f1f5f9;padding-top:.875rem;">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:.625rem;">
      Key factors driving this forecast
    </div>
    <div id="predReasons" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.375rem .75rem;">
      @foreach($prediction7d as $i => $day)
        @foreach($day['reasons'] as $r)
        <div style="display:flex;align-items:flex-start;gap:.4rem;font-size:.75rem;color:#374151;line-height:1.4;">
          <span style="flex-shrink:0;font-size:.85rem;color:{{ $day['direction']==='up'?'#16a34a':($day['direction']==='down'?'#dc2626':'#94a3b8') }};">
            {{ $day['direction']==='up' ? '▲' : ($day['direction']==='down' ? '▼' : '◆') }}
          </span>
          <span><strong style="color:#64748b;">{{ $day['day_display'] }}:</strong> {{ $r }}</span>
        </div>
        @endforeach
      @endforeach
    </div>
  </div>

  {{-- High/Low range table — auth only --}}
  @auth
  <div style="margin-top:.875rem;border-top:1px solid #f1f5f9;padding-top:.875rem;">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:.625rem;">Predicted Daily Price Ranges</div>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.78rem;">
        <thead>
          <tr style="border-bottom:1px solid #e2e8f0;">
            <th style="text-align:left;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Day</th>
            <th style="text-align:center;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Direction</th>
            <th style="text-align:right;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Predicted Low</th>
            <th style="text-align:right;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Predicted Price</th>
            <th style="text-align:right;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Predicted High</th>
            <th style="text-align:right;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Change%</th>
            <th style="text-align:right;padding:.375rem .5rem;font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Confidence</th>
          </tr>
        </thead>
        <tbody>
          @foreach($prediction7d as $day)
          @php
            $dUp  = $day['direction'] === 'up';
            $dDn  = $day['direction'] === 'down';
            $dClr = $dUp ? '#16a34a' : ($dDn ? '#dc2626' : '#64748b');
          @endphp
          <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
            <td style="padding:.5rem;font-weight:600;color:#0f172a;">{{ $day['day_display'] }}</td>
            <td style="padding:.5rem;text-align:center;">
              <span style="display:inline-flex;align-items:center;gap:.25rem;font-size:.72rem;font-weight:700;padding:.15rem .55rem;border-radius:9999px;
                background:{{ $dUp ? '#dcfce7' : ($dDn ? '#fee2e2' : '#f1f5f9') }};
                color:{{ $dClr }};
                border:1px solid {{ $dUp ? '#86efac' : ($dDn ? '#fca5a5' : '#e2e8f0') }};">
                {{ $dUp ? '▲ UP' : ($dDn ? '▼ DOWN' : '▬ SIDE') }}
              </span>
            </td>
            <td class="mono" style="padding:.5rem;text-align:right;color:#dc2626;font-size:.77rem;">{{ number_format($day['predicted_low'], 2) }}</td>
            <td class="mono" style="padding:.5rem;text-align:right;font-weight:700;color:#0f172a;font-size:.77rem;">{{ number_format($day['predicted_price'], 2) }}</td>
            <td class="mono" style="padding:.5rem;text-align:right;color:#16a34a;font-size:.77rem;">{{ number_format($day['predicted_high'], 2) }}</td>
            <td class="mono" style="padding:.5rem;text-align:right;font-weight:600;color:{{ $dClr }};font-size:.77rem;">
              {{ $day['change_pct'] >= 0 ? '+' : '' }}{{ number_format($day['change_pct'], 2) }}%
            </td>
            <td style="padding:.5rem;text-align:right;">
              <div style="display:flex;align-items:center;justify-content:flex-end;gap:.35rem;">
                <div style="width:45px;height:4px;border-radius:9999px;background:#e2e8f0;overflow:hidden;">
                  <div style="height:4px;background:{{ $dUp ? '#22c55e' : ($dDn ? '#ef4444' : '#94a3b8') }};width:{{ $day['confidence'] }}%;border-radius:9999px;"></div>
                </div>
                <span class="mono" style="font-size:.7rem;color:#64748b;min-width:2.5rem;text-align:right;">{{ $day['confidence'] }}%</span>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @else
  <div style="margin-top:.875rem;border-top:1px solid #f1f5f9;padding-top:.875rem;">
    <a href="{{ route('login') }}" style="display:flex;align-items:center;justify-content:center;gap:.625rem;
       padding:.875rem;border-radius:.75rem;background:#f8fafc;border:1.5px dashed #e2e8f0;
       text-decoration:none;color:#64748b;font-size:.82rem;">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
      <span><strong style="color:#2563eb;">Login</strong> to view detailed daily price ranges</span>
    </a>
  </div>
  @endauth
</div>
@endif
@else
{{-- Guest lock: prediction --}}
<div style="position:relative;border-radius:1rem;overflow:hidden;margin-bottom:1.25rem;">
  <div style="filter:blur(5px);user-select:none;pointer-events:none;padding:1.25rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:1rem;">
    <div style="height:.75rem;background:#e2e8f0;border-radius:9999px;width:40%;margin-bottom:1rem;"></div>
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem;margin-bottom:.875rem;">
      @foreach(range(1,7) as $_)
      <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem .5rem;text-align:center;">
        <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;margin-bottom:.4rem;"></div>
        <div style="height:1rem;background:#dcfce7;border-radius:.25rem;margin-bottom:.4rem;"></div>
        <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;"></div>
      </div>
      @endforeach
    </div>
    <div style="height:.6rem;background:#e2e8f0;border-radius:9999px;width:70%;"></div>
  </div>
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.75rem;background:rgba(255,255,255,.72);backdrop-filter:blur(2px);border-radius:1rem;">
    <div style="width:2.5rem;height:2.5rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;display:flex;align-items:center;justify-content:center;">
      <svg style="width:1.1rem;height:1.1rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <div style="text-align:center;">
      <div style="font-weight:700;font-size:.9375rem;color:#0f172a;margin-bottom:.25rem;">Login to view 7-Day Price Prediction</div>
      <div style="font-size:.8125rem;color:#64748b;">AI forecast · Price targets · Confidence scores</div>
    </div>
    <a href="{{ route('login') }}" style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.25rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:.875rem;font-weight:600;border-radius:.625rem;text-decoration:none;box-shadow:0 2px 8px rgba(79,70,229,.35);">
      <svg style="width:.875rem;height:.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
      Sign in free
    </a>
  </div>
</div>
@endauth

{{-- ════ PRICE CHART ══════════════════════════════════════════════════════ --}}
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <div class="section-lbl" style="margin-bottom:0;">Price Chart</div>
    <div style="display:flex;gap:.375rem;">
      @foreach(['1W','1M','3M','1Y','ALL'] as $p)
      <button onclick="loadChart('{{ $p }}')" class="tab-btn {{ $p==='3M'?'active':'' }}" data-period="{{ $p }}">{{ $p }}</button>
      @endforeach
    </div>
  </div>
  <div style="height:320px;position:relative;"><canvas id="priceChart"></canvas></div>
</div>

{{-- ════ SUPPORT/RESISTANCE + VOLUME ═════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <div class="card">
    <div class="section-lbl">Support &amp; Resistance</div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <div style="font-size:.72rem;font-weight:700;color:#16a34a;margin-bottom:.5rem;">▲ Support</div>
        @forelse($sup as $sl)
        <div class="card-sm" style="margin-bottom:.5rem;border-color:#bbf7d0;background:#f0fdf4;">
          <div style="display:flex;justify-content:space-between;align-items:baseline;">
            <span style="font-size:.65rem;color:#94a3b8;">{{ \Carbon\Carbon::parse($sl['date'])->format('d M') }}</span>
            <span class="mono" style="font-size:.875rem;font-weight:700;color:#16a34a;">{{ number_format($sl['low'],2) }}</span>
          </div>
          <div style="font-size:.68rem;color:#64748b;">Close: {{ number_format($sl['close'],2) }}</div>
        </div>
        @empty
        <div style="font-size:.8rem;color:#94a3b8;padding:.5rem 0;">No support data</div>
        @endforelse
      </div>
      <div>
        <div style="font-size:.72rem;font-weight:700;color:#dc2626;margin-bottom:.5rem;">▼ Resistance</div>
        @forelse($res as $rl)
        <div class="card-sm" style="margin-bottom:.5rem;border-color:#fecaca;background:#fef2f2;">
          <div style="display:flex;justify-content:space-between;align-items:baseline;">
            <span style="font-size:.65rem;color:#94a3b8;">{{ \Carbon\Carbon::parse($rl['date'])->format('d M') }}</span>
            <span class="mono" style="font-size:.875rem;font-weight:700;color:#dc2626;">{{ number_format($rl['high'],2) }}</span>
          </div>
          <div style="font-size:.68rem;color:#64748b;">Close: {{ number_format($rl['close'],2) }}</div>
        </div>
        @empty
        <div style="font-size:.8rem;color:#94a3b8;padding:.5rem 0;">No resistance data</div>
        @endforelse
      </div>
    </div>
  </div>

  @if(!empty($volumeAnalytics))
  @php $va=$volumeAnalytics; @endphp
  <div class="card">
    <div class="section-lbl">Volume Analytics — Last 20 Sessions</div>
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="card-sm">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.25rem;">Buy Pressure</div>
        <div class="mono" style="font-size:1.5rem;font-weight:800;color:#16a34a;">{{ $va['buy_pct'] }}%</div>
        <div style="font-size:.7rem;color:#64748b;">{{ $va['buy_candles'] }} bull candles</div>
      </div>
      <div class="card-sm">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.25rem;">Sell Pressure</div>
        <div class="mono" style="font-size:1.5rem;font-weight:800;color:#dc2626;">{{ $va['sell_pct'] }}%</div>
        <div style="font-size:.7rem;color:#64748b;">{{ $va['sell_candles'] }} bear candles</div>
      </div>
      <div class="card-sm">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.25rem;">20D Avg Volume</div>
        <div class="mono" style="font-size:.9375rem;font-weight:700;color:#0f172a;">{{ number_format($va['avg_volume']) }}</div>
      </div>
      <div class="card-sm">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.25rem;">Last Volume</div>
        @php $vr=$va['avg_volume']>0?$va['last_volume']/$va['avg_volume']:1; @endphp
        <div class="mono" style="font-size:.9375rem;font-weight:700;color:{{ $vr>1.5?'#2563eb':'#0f172a' }};">{{ number_format($va['last_volume']) }}</div>
        <div style="font-size:.68rem;color:{{ $vr>1.5?'#2563eb':'#94a3b8' }};">{{ number_format($vr,1) }}× avg{{ $vr>1.5?' — High!':'' }}</div>
      </div>
    </div>
    <div style="font-size:.72rem;display:flex;justify-content:space-between;margin-bottom:.35rem;">
      <span style="color:#16a34a;font-weight:600;">▲ {{ $va['buy_pct'] }}% Buy Vol</span>
      <span style="color:#dc2626;font-weight:600;">{{ $va['sell_pct'] }}% Sell Vol ▼</span>
    </div>
    <div style="height:10px;border-radius:9999px;background:#fee2e2;overflow:hidden;">
      <div style="height:100%;background:linear-gradient(90deg,#22c55e,#4ade80);width:{{ $va['buy_pct'] }}%;border-radius:9999px;"></div>
    </div>
  </div>
  @endif
</div>

{{-- ════ RISK ANALYTICS ══════════════════════════════════════════════════ --}}
@if(!empty($ab) || !empty($var))
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  @if(!empty($ab))
  <div class="card">
    <div class="section-lbl">Alpha &amp; Beta Risk Metrics</div>
    <table class="w-full">
      <thead><tr style="border-bottom:1px solid #f1f5f9;">
        <th class="text-left py-2" style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;">Period</th>
        <th class="text-right py-2" style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;">Beta</th>
        <th class="text-right py-2" style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;">Alpha</th>
        <th class="text-left py-2 pl-3" style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;">Risk</th>
      </tr></thead>
      <tbody>
        @foreach([['1 Month','beta_1_months','alpha_1_months'],['3 Months','beta_3_months','alpha_3_months'],['12 Months','beta_12_months','alpha_12_months']] as [$pl,$bk,$ak])
        @if(isset($ab[$bk]))
        @php $bv=(float)$ab[$bk]; $av=(float)($ab[$ak]??0); @endphp
        <tr style="border-bottom:1px solid #f8fafc;" class="hover:bg-slate-50">
          <td class="py-2 text-sm" style="color:#374151;">{{ $pl }}</td>
          <td class="text-right py-2 mono text-sm font-semibold" style="color:{{ $bv>1.2?'#dc2626':($bv<0.8?'#16a34a':'#ca8a04') }};">{{ number_format($bv,2) }}</td>
          <td class="text-right py-2 mono text-sm font-semibold" style="color:{{ $av>=0?'#16a34a':'#dc2626' }};">{{ number_format($av,2) }}</td>
          <td class="pl-3 py-2">
            <span style="font-size:.68rem;padding:.15rem .5rem;border-radius:9999px;
              background:{{ $bv>1.2?'#fef2f2':($bv<0.8?'#f0fdf4':'#fefce8') }};
              color:{{ $bv>1.2?'#dc2626':($bv<0.8?'#16a34a':'#ca8a04') }};
              border:1px solid {{ $bv>1.2?'#fecaca':($bv<0.8?'#bbf7d0':'#fde68a') }};">
              {{ $bv>1.2?'High Risk':($bv<0.8?'Low Risk':'Moderate') }}
            </span>
          </td>
        </tr>
        @endif
        @endforeach
      </tbody>
    </table>
    <div style="margin-top:.75rem;font-size:.68rem;color:#94a3b8;line-height:1.4;">
      Beta &gt; 1 = more volatile than market. Alpha &gt; 0 = outperformed market benchmark.
    </div>
  </div>
  @endif

  @if(!empty($var))
  <div class="card">
    <div class="section-lbl">Value at Risk &amp; Volatility (Monthly)</div>
    <div class="grid grid-cols-3 gap-3 mb-4">
      @foreach([['VaR 90%','var_90_cf','Mild risk'],['VaR 95%','var_95_cf','Moderate'],['VaR 99%','var_99_cf','Extreme']] as [$vl,$vk,$vh])
      <div class="card-sm" style="text-align:center;border-color:#fecaca;background:#fff5f5;">
        <div style="font-size:.65rem;color:#dc2626;margin-bottom:.25rem;">{{ $vl }}</div>
        <div class="mono" style="font-size:1.1rem;font-weight:800;color:#dc2626;">{{ number_format($var[$vk]??0,2) }}%</div>
        <div style="font-size:.65rem;color:#94a3b8;">{{ $vh }}</div>
      </div>
      @endforeach
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="card-sm" style="text-align:center;">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.2rem;">Std Deviation (Monthly)</div>
        <div class="mono" style="font-size:1.1rem;font-weight:700;color:#7c3aed;">{{ number_format($var['std_deviation_monthly']??0,2) }}%</div>
      </div>
      <div class="card-sm" style="text-align:center;">
        <div style="font-size:.65rem;color:#94a3b8;margin-bottom:.2rem;">Mean Return (Monthly)</div>
        <div class="mono" style="font-size:1.1rem;font-weight:700;color:{{ ($var['mean_return_month']??0)>=0?'#16a34a':'#dc2626' }};">
          {{ ($var['mean_return_month']??0)>=0?'+':'' }}{{ number_format($var['mean_return_month']??0,2) }}%
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
@endif

{{-- ════ BROKER ACTIVITY (FLOORSHEET) ══════════════════════════════════ --}}
@if(!empty($brokerActivity))
@php
  $ba      = $brokerActivity;
  $baDate  = $ba['date'] ?? '';
  $tBuyQ   = (float)($ba['total_buy_qty']    ?? 0);
  $tSellQ  = (float)($ba['total_sell_qty']   ?? 0);
  $tBuyA   = (float)($ba['total_buy_amount'] ?? 0);
  $tSellA  = (float)($ba['total_sell_amount']?? 0);
  $txnRows = (int)($ba['rows'] ?? 0);
@endphp
<div class="card">
  {{-- Header --}}
  <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem;">
    <div>
      <div class="section-lbl" style="margin-bottom:.2rem;">Broker-wise Buy / Sell Activity</div>
      <span style="font-size:.72rem;color:#64748b;">
        {{ $baDate }} &nbsp;·&nbsp; {{ number_format($txnRows) }} transactions &nbsp;·&nbsp;
        Total Buy: <strong style="color:#16a34a;">{{ number_format($tBuyQ) }} shares</strong> &nbsp;·&nbsp;
        Total Sell: <strong style="color:#dc2626;">{{ number_format($tSellQ) }} shares</strong>
      </span>
    </div>
    <div style="display:flex;gap:.5rem;">
      <span style="font-size:.72rem;padding:.2rem .65rem;border-radius:9999px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-weight:700;">
        Buy NPR {{ number_format($tBuyA/1e6, 2) }}M
      </span>
      <span style="font-size:.72rem;padding:.2rem .65rem;border-radius:9999px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-weight:700;">
        Sell NPR {{ number_format($tSellA/1e6, 2) }}M
      </span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- TOP BUYERS --}}
    <div>
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#16a34a;margin-bottom:.5rem;">
        ▲ Top Buyers
      </div>
      <table class="w-full">
        <thead>
          <tr style="border-bottom:2px solid #dcfce7;">
            <th class="text-left py-1.5" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Broker</th>
            <th class="text-right py-1.5 px-1" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Qty</th>
            <th class="text-right py-1.5 px-1" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">% of Buy</th>
            <th class="text-right py-1.5" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($ba['buys'] as $br)
          <tr style="border-bottom:1px solid #f0fdf4;" class="hover:bg-green-50">
            <td class="py-1.5" style="font-size:.76rem;">
              <span class="mono font-bold" style="color:#2563eb;font-size:.7rem;">{{ $br['broker_no'] }}</span>
              <span style="color:#374151;margin-left:.35rem;font-size:.75rem;">{{ $br['broker_name'] }}</span>
            </td>
            <td class="text-right py-1.5 px-1 mono font-semibold" style="font-size:.76rem;color:#16a34a;">
              {{ number_format($br['qty']) }}
            </td>
            <td class="text-right py-1.5 px-1" style="font-size:.76rem;">
              <div style="display:flex;align-items:center;justify-content:flex-end;gap:.35rem;">
                <div style="width:40px;height:4px;border-radius:9999px;background:#dcfce7;overflow:hidden;">
                  <div style="height:4px;border-radius:9999px;width:{{ min(100,$br['qty_pct']) }}%;background:#22c55e;"></div>
                </div>
                <span class="mono" style="color:#16a34a;font-weight:700;min-width:3rem;text-align:right;">{{ $br['qty_pct'] }}%</span>
              </div>
            </td>
            <td class="text-right py-1.5 mono" style="font-size:.73rem;color:#64748b;">
              {{ $br['amount'] >= 1e6 ? 'NPR '.number_format($br['amount']/1e6,2).'M' : 'NPR '.number_format($br['amount']) }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- TOP SELLERS --}}
    <div>
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#dc2626;margin-bottom:.5rem;">
        ▼ Top Sellers
      </div>
      <table class="w-full">
        <thead>
          <tr style="border-bottom:2px solid #fecaca;">
            <th class="text-left py-1.5" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Broker</th>
            <th class="text-right py-1.5 px-1" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Qty</th>
            <th class="text-right py-1.5 px-1" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">% of Sell</th>
            <th class="text-right py-1.5" style="font-size:.63rem;color:#94a3b8;font-weight:700;text-transform:uppercase;">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($ba['sells'] as $br)
          <tr style="border-bottom:1px solid #fef2f2;" class="hover:bg-red-50">
            <td class="py-1.5" style="font-size:.76rem;">
              <span class="mono font-bold" style="color:#2563eb;font-size:.7rem;">{{ $br['broker_no'] }}</span>
              <span style="color:#374151;margin-left:.35rem;font-size:.75rem;">{{ $br['broker_name'] }}</span>
            </td>
            <td class="text-right py-1.5 px-1 mono font-semibold" style="font-size:.76rem;color:#dc2626;">
              {{ number_format($br['qty']) }}
            </td>
            <td class="text-right py-1.5 px-1" style="font-size:.76rem;">
              <div style="display:flex;align-items:center;justify-content:flex-end;gap:.35rem;">
                <div style="width:40px;height:4px;border-radius:9999px;background:#fecaca;overflow:hidden;">
                  <div style="height:4px;border-radius:9999px;width:{{ min(100,$br['qty_pct']) }}%;background:#ef4444;"></div>
                </div>
                <span class="mono" style="color:#dc2626;font-weight:700;min-width:3rem;text-align:right;">{{ $br['qty_pct'] }}%</span>
              </div>
            </td>
            <td class="text-right py-1.5 mono" style="font-size:.73rem;color:#64748b;">
              {{ $br['amount'] >= 1e6 ? 'NPR '.number_format($br['amount']/1e6,2).'M' : 'NPR '.number_format($br['amount']) }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>
</div>
@endif

{{-- ════ OHLC TABLE + BROKER DIRECTORY ═══════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <div class="card">
    <div class="section-lbl">Recent Price History</div>
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead><tr style="border-bottom:1px solid #f1f5f9;">
          <th class="text-left py-2" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Date</th>
          <th class="text-right py-2 px-1" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Open</th>
          <th class="text-right py-2 px-1" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">High</th>
          <th class="text-right py-2 px-1" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Low</th>
          <th class="text-right py-2 px-1" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Close</th>
          <th class="text-right py-2 px-1" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Chg%</th>
          <th class="text-right py-2" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Volume</th>
        </tr></thead>
        <tbody>
          @foreach($prices->take(20) as $p)
          <tr style="border-bottom:1px solid #f8fafc;" class="hover:bg-slate-50">
            <td class="py-1.5" style="font-size:.78rem;color:#64748b;">{{ $p->date->format('d M Y') }}</td>
            <td class="text-right py-1.5 px-1 mono" style="font-size:.78rem;color:#374151;">{{ number_format($p->open,2) }}</td>
            <td class="text-right py-1.5 px-1 mono font-semibold" style="font-size:.78rem;color:#16a34a;">{{ number_format($p->high,2) }}</td>
            <td class="text-right py-1.5 px-1 mono font-semibold" style="font-size:.78rem;color:#dc2626;">{{ number_format($p->low,2) }}</td>
            <td class="text-right py-1.5 px-1 mono font-bold" style="font-size:.78rem;color:#0f172a;">{{ number_format($p->close,2) }}</td>
            <td class="text-right py-1.5 px-1 mono" style="font-size:.78rem;color:{{ $p->change_percent>=0?'#16a34a':'#dc2626' }};">
              {{ $p->change_percent>=0?'+':'' }}{{ number_format($p->change_percent,2) }}%
            </td>
            <td class="text-right py-1.5 mono" style="font-size:.78rem;color:#64748b;">{{ number_format($p->volume) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
      <div class="section-lbl" style="margin-bottom:0;">NEPSE Registered Brokers</div>
      <span style="font-size:.7rem;color:#94a3b8;">{{ count($brokers??[]) }} total</span>
    </div>
    <div style="max-height:380px;overflow-y:auto;">
      <table class="w-full">
        <thead style="position:sticky;top:0;background:#fff;z-index:1;">
          <tr style="border-bottom:1px solid #f1f5f9;">
            <th class="text-left py-2" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;min-width:3rem;">No.</th>
            <th class="text-left py-2" style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;font-weight:600;">Broker Name</th>
          </tr>
        </thead>
        <tbody>
          @forelse($brokers??[] as $b)
          <tr style="border-bottom:1px solid #f8fafc;" class="hover:bg-slate-50">
            <td class="py-1.5 mono font-bold" style="font-size:.78rem;color:#2563eb;">{{ $b['broker_no']??'' }}</td>
            <td class="py-1.5" style="font-size:.78rem;color:#374151;">{{ $b['broker_name']??'' }}</td>
          </tr>
          @empty
          <tr><td colspan="2" style="padding:1rem;color:#94a3b8;font-size:.8rem;">No broker data available.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
@endsection

@push('scripts')
<script>
const symbol  = '{{ $stock->symbol }}';
let   chart   = null;
const allData = @json($chartData);

function buildChart(data) {
  const labels = data.map(d => d.date);
  const closes = data.map(d => parseFloat(d.close));
  const ctx    = document.getElementById('priceChart').getContext('2d');
  if (chart) chart.destroy();
  const up = closes.length < 2 || closes[closes.length-1] >= closes[0];
  chart = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{
      label: 'Close', data: closes,
      borderColor: up ? '#16a34a' : '#dc2626',
      backgroundColor: up ? 'rgba(22,163,74,.07)' : 'rgba(220,38,38,.07)',
      borderWidth: 2.5, pointRadius: 0, fill: true, tension: 0.3
    }]},
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode:'index', intersect:false },
      plugins: {
        legend: { display:false },
        tooltip: {
          backgroundColor:'#fff', borderColor:'#e2e8f0', borderWidth:1,
          titleColor:'#64748b', bodyColor:'#0f172a',
          callbacks: { label: c => ` NPR ${parseFloat(c.parsed.y).toFixed(2)}` }
        }
      },
      scales: {
        x: { grid:{ color:'#f1f5f9' }, ticks:{ color:'#94a3b8', maxTicksLimit:8, font:{size:10} } },
        y: { grid:{ color:'#f1f5f9' }, position:'right',
             ticks:{ color:'#94a3b8', callback: v => 'NPR '+v.toFixed(0), font:{size:10} } }
      }
    }
  });
}

async function loadChart(period) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.period === period));
  try {
    const res  = await fetch(`/api/stocks/${symbol}/chart?period=${period}`);
    const data = await res.json();
    buildChart(data);
  } catch(e) { console.error('Chart load failed', e); }
}

buildChart(allData);
loadChart('3M');
</script>
@endpush
