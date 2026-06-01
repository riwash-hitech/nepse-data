@extends('layouts.app')
@section('title', 'IPO Results - NEPSE Analytics')

@push('head')
<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)} }
.fade-up { animation:fadeUp .4s ease both; }
.fade-d1 { animation-delay:.06s; }
.fade-d2 { animation-delay:.13s; }
.ipo-row { transition:background .1s; }
.ipo-row:hover { background:#f8fafc; }
.status-badge { display:inline-flex;align-items:center;gap:.3rem;border-radius:9999px;
  padding:.25rem .7rem;font-size:.7rem;font-weight:700;letter-spacing:.03em; }
.status-Open      { background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0; }
.status-Upcoming  { background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe; }
.status-Closed    { background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0; }
.status-Listed    { background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe; }
.pill-input { border:1.5px solid #e2e8f0;border-radius:.625rem;padding:.6rem .875rem;
  font-size:.875rem;outline:none;background:#f8fafc;color:#0f172a;box-sizing:border-box;
  transition:border-color .2s; }
.pill-input:focus { border-color:#818cf8;background:#fff; }
</style>
@endpush

@section('content')
@php
  $open     = collect($ipoList)->where('status','Open');
  $upcoming = collect($ipoList)->where('status','Upcoming');
  $closed   = collect($ipoList)->filter(fn($i) => !in_array($i['status'],['Open','Upcoming']));
  $total    = count($ipoList);
@endphp

{{-- ════ HERO ══════════════════════════════════════════════════════════════ --}}
<div class="fade-up" style="background:linear-gradient(135deg,#1e3a8a 0%,#7c3aed 100%);
     border-radius:1.25rem;padding:2rem 1.75rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;opacity:.05;pointer-events:none;
       background-image:radial-gradient(circle,#fff 1px,transparent 1px);
       background-size:30px 30px;"></div>
  <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;
       flex-wrap:wrap;gap:1rem;">
    <div>
      <div style="display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.15);
             border:1px solid rgba(255,255,255,.25);border-radius:9999px;padding:.2rem .75rem;
             font-size:.7rem;font-weight:600;color:rgba(255,255,255,.9);margin-bottom:.75rem;">
        📋 IPO Dashboard
      </div>
      <h1 style="font-size:clamp(1.5rem,4vw,2.25rem);font-weight:900;color:#fff;
           margin:0 0 .5rem;line-height:1.2;">IPO Result Checker</h1>
      <p style="font-size:.9rem;color:rgba(255,255,255,.7);margin:0;max-width:42rem;line-height:1.6;">
        Browse all {{ $total }} Nepal IPOs — dates, status, and issue managers. To check your allotment,
        use your BOID on <a href="https://iporesult.cdsc.com.np" target="_blank" rel="noopener"
        style="color:#a5f3fc;text-decoration:underline;text-underline-offset:3px;">CDSC's official portal ↗</a>
      </p>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.625rem;flex-shrink:0;">
      @if($open->count())
      <div style="background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);
           border-radius:.75rem;padding:.625rem 1rem;text-align:center;">
        <div style="font-size:1.375rem;font-weight:800;color:#86efac;line-height:1;">{{ $open->count() }}</div>
        <div style="font-size:.68rem;color:rgba(255,255,255,.6);margin-top:.15rem;">Open Now</div>
      </div>
      @endif
      <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
           border-radius:.75rem;padding:.625rem 1rem;text-align:center;">
        <div style="font-size:1.375rem;font-weight:800;color:#fff;line-height:1;">{{ $total }}</div>
        <div style="font-size:.68rem;color:rgba(255,255,255,.5);margin-top:.15rem;">Total IPOs</div>
      </div>
    </div>
  </div>
</div>

{{-- ════ CDSC REDIRECT CARD ════════════════════════════════════════════════ --}}
<div class="fade-up fade-d1" style="background:linear-gradient(135deg,#fffbeb,#fef9c3);
     border:1.5px solid #fde68a;border-radius:1rem;padding:1.25rem 1.5rem;margin-bottom:1.5rem;
     display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
  <div style="width:44px;height:44px;border-radius:.75rem;background:#fef3c7;
       border:1px solid #fcd34d;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <svg width="22" height="22" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
  </div>
  <div style="flex:1;min-width:220px;">
    <div style="font-size:.875rem;font-weight:700;color:#92400e;margin-bottom:.2rem;">Check your IPO allotment result</div>
    <div style="font-size:.78rem;color:#a16207;line-height:1.5;">
      Allotment results are published by CDSC. Enter your 16-digit BOID on their official portal.
    </div>
  </div>
  <a href="https://iporesult.cdsc.com.np" target="_blank" rel="noopener"
     style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.25rem;
            background:#d97706;color:#fff;text-decoration:none;border-radius:.625rem;
            font-size:.8125rem;font-weight:700;flex-shrink:0;transition:background .15s;"
     onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">
    Open CDSC Portal
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
    </svg>
  </a>
</div>

{{-- ════ SEARCH / FILTER BAR ════════════════════════════════════════════════ --}}
<div class="fade-up fade-d1" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
     padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.875rem;flex-wrap:wrap;">
  <div style="position:relative;flex:1;min-width:200px;">
    <svg style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;"
         width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <input id="ipoSearch" type="text" placeholder="Search symbol or company…"
           class="pill-input" style="width:100%;padding-left:2.25rem;">
  </div>
  <select id="statusFilter" class="pill-input" style="min-width:145px;">
    <option value="">All Status</option>
    <option value="Open">Open</option>
    <option value="Upcoming">Upcoming</option>
    <option value="Closed">Closed</option>
    <option value="Listed">Listed</option>
  </select>
  <form method="POST" action="{{ route('ipo.refresh') }}">
    @csrf
    <button type="submit"
      style="padding:.6rem 1rem;font-size:.8rem;font-weight:600;color:#64748b;
             background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:.625rem;cursor:pointer;
             white-space:nowrap;transition:all .15s;"
      onmouseover="this.style.background='#eff6ff';this.style.borderColor='#bfdbfe';this.style.color='#2563eb'"
      onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0';this.style.color='#64748b'"
      onclick="this.disabled=true;this.textContent='Refreshing…'">
      ↻ Refresh
    </button>
  </form>
  <span id="ipoCount" style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">
    {{ $total }} IPOs
  </span>
</div>

{{-- ════ IPO TABLE ══════════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d2" style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;
     overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);margin-bottom:1.5rem;">

  <div style="overflow-x:auto;">
    <table id="ipoTable" style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
          <th style="text-align:left;padding:.75rem 1.25rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Symbol</th>
          <th style="text-align:left;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;">Company</th>
          <th style="text-align:left;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Status</th>
          <th style="text-align:right;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Units</th>
          <th style="text-align:left;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Opens</th>
          <th style="text-align:left;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Closes</th>
          <th style="text-align:left;padding:.75rem 1rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;white-space:nowrap;">Listed</th>
          <th style="text-align:left;padding:.75rem 1.25rem;font-size:.68rem;font-weight:700;
               text-transform:uppercase;letter-spacing:.08em;color:#64748b;">Issue Manager</th>
        </tr>
      </thead>
      <tbody id="ipoTbody">
        @forelse($ipoList as $ipo)
        @php
          $sym    = $ipo['symbol']        ?? '—';
          $co     = $ipo['company']       ?? 'Unknown';
          $status = $ipo['status']        ?? 'Closed';
          $units  = $ipo['units']         ?? null;
          $opens  = $ipo['opening_date']  ?? null;
          $closes = $ipo['closing_date']  ?? null;
          $listed = $ipo['listing_date']  ?? null;
          $mgr    = $ipo['issue_manager'] ?? '—';
          $dotColor = match($status) {
            'Open'     => '#22c55e',
            'Upcoming' => '#3b82f6',
            'Listed'   => '#a78bfa',
            default    => '#94a3b8',
          };
        @endphp
        <tr class="ipo-row"
            data-symbol="{{ strtolower($sym) }}"
            data-company="{{ strtolower($co) }}"
            data-status="{{ $status }}"
            style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:.75rem 1.25rem;white-space:nowrap;">
            <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:.8125rem;color:#0f172a;">
              {{ $sym }}
            </span>
          </td>
          <td style="padding:.75rem 1rem;">
            <span style="font-size:.8125rem;color:#374151;font-weight:500;">{{ $co }}</span>
          </td>
          <td style="padding:.75rem 1rem;white-space:nowrap;">
            <span class="status-badge status-{{ $status }}">
              <span style="width:5px;height:5px;border-radius:50%;background:{{ $dotColor }};display:inline-block;"></span>
              {{ $status }}
            </span>
          </td>
          <td style="padding:.75rem 1rem;text-align:right;white-space:nowrap;">
            <span style="font-size:.8rem;color:#374151;font-family:'JetBrains Mono',monospace;">
              {{ $units ? number_format($units) : '—' }}
            </span>
          </td>
          <td style="padding:.75rem 1rem;white-space:nowrap;">
            <span style="font-size:.78rem;color:#64748b;">
              {{ $opens ? \Carbon\Carbon::parse($opens)->format('d M Y') : '—' }}
            </span>
          </td>
          <td style="padding:.75rem 1rem;white-space:nowrap;">
            <span style="font-size:.78rem;color:#64748b;">
              {{ $closes ? \Carbon\Carbon::parse($closes)->format('d M Y') : '—' }}
            </span>
          </td>
          <td style="padding:.75rem 1rem;white-space:nowrap;">
            <span style="font-size:.78rem;color:#64748b;">
              {{ $listed ? \Carbon\Carbon::parse($listed)->format('d M Y') : '—' }}
            </span>
          </td>
          <td style="padding:.75rem 1.25rem;">
            <span style="font-size:.78rem;color:#64748b;">{{ $mgr }}</span>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="padding:3rem;text-align:center;color:#94a3b8;font-size:.875rem;">
            <div style="font-size:2rem;margin-bottom:.5rem;">📭</div>
            No IPO data available. Click Refresh to reload.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div id="noResults" style="display:none;padding:2.5rem;text-align:center;color:#94a3b8;font-size:.875rem;">
    <div style="font-size:2rem;margin-bottom:.5rem;">🔍</div>
    No IPOs match your search.
  </div>
</div>

{{-- ════ INFO CARDS ════════════════════════════════════════════════════════ --}}
<div class="fade-up fade-d2" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
     gap:1rem;margin-bottom:1rem;">
  @php
  $infoCards = [
    ['icon'=>'🔑','title'=>'What is a BOID?','body'=>'Your 16-digit Beneficiary Owner ID assigned by CDSC when you open a Demat account. Find it on your Demat certificate or MeroShare login.'],
    ['icon'=>'📊','title'=>'How allotment works','body'=>'When demand exceeds supply, SEBON/CDSC randomly selects applicants by lottery. Results are published within 7–14 days after the IPO closes.'],
    ['icon'=>'🌐','title'=>'Where to check','body'=>'Go to iporesult.cdsc.com.np, enter your 16-digit BOID, select the company and click Check. You can also check via MeroShare app.'],
    ['icon'=>'⏰','title'=>'Result timing','body'=>'Results are published 7-14 days after IPO closes. If a company is not on CDSC\'s list yet, allotment may not have happened. Check back later.'],
  ];
  @endphp
  @foreach($infoCards as $card)
  <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:1.25rem;
       box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <div style="font-size:1.5rem;margin-bottom:.5rem;">{{ $card['icon'] }}</div>
    <div style="font-size:.875rem;font-weight:700;color:#0f172a;margin-bottom:.3rem;">{{ $card['title'] }}</div>
    <p style="margin:0;font-size:.78rem;color:#64748b;line-height:1.6;">{{ $card['body'] }}</p>
  </div>
  @endforeach
</div>

@endsection

@push('scripts')
<script>
(function () {
  var searchEl = document.getElementById('ipoSearch');
  var filterEl = document.getElementById('statusFilter');
  var countEl  = document.getElementById('ipoCount');
  var noRes    = document.getElementById('noResults');
  var rows     = document.querySelectorAll('#ipoTbody .ipo-row');

  function applyFilter() {
    var q      = (searchEl.value || '').toLowerCase().trim();
    var status = (filterEl.value || '').toLowerCase();
    var visible = 0;
    rows.forEach(function (row) {
      var sym  = row.getAttribute('data-symbol') || '';
      var co   = row.getAttribute('data-company') || '';
      var st   = (row.getAttribute('data-status') || '').toLowerCase();
      var matchQ = !q || sym.includes(q) || co.includes(q);
      var matchS = !status || st === status;
      if (matchQ && matchS) {
        row.style.display = '';
        visible++;
      } else {
        row.style.display = 'none';
      }
    });
    countEl.textContent = visible + ' IPO' + (visible !== 1 ? 's' : '');
    noRes.style.display = visible === 0 ? 'block' : 'none';
  }

  searchEl && searchEl.addEventListener('input', applyFilter);
  filterEl && filterEl.addEventListener('change', applyFilter);
})();
</script>
@endpush
