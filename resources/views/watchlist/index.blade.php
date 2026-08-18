@extends('layouts.app')
@section('title', 'My Watchlist')

@section('content')
<div class="space-y-5" style="max-width:42rem;">

    <h1 class="text-2xl font-bold" style="color:#0f172a;">📌 My Watchlist</h1>

    {{-- Add to watchlist --}}
    <div class="bg-white border rounded-xl p-4" style="border-color:#e2e8f0;">
        <div class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:#94a3b8;">Add Stock</div>
        <form method="POST" action="{{ route('watchlist.store') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="relative flex-1">
                <input type="text" id="watchlistSearch" autocomplete="off" placeholder="Search by symbol or company name…"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                <input type="hidden" name="stock_id" id="watchlistStockId" required>
                <div id="watchlistDropdown" style="display:none;position:absolute;z-index:20;left:0;right:0;top:100%;margin-top:4px;background:#ffffff;border:1px solid #e2e8f0;border-radius:0.5rem;max-height:260px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,0.08);"></div>
            </div>
            <button type="submit" class="btn-primary justify-center">+ Add to Watchlist</button>
        </form>
    </div>

    @if($errors->any())
    <div class="px-4 py-3 rounded-lg text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    @if($watchlist->isEmpty())
        <div class="bg-white border rounded-xl p-12 text-center" style="border-color:#e2e8f0;color:#94a3b8;">
            <p class="mb-3">Your watchlist is empty. Search for a stock above to start tracking it.</p>
            <a href="{{ route('stocks.index') }}" class="btn-primary inline-flex">Browse Stocks</a>
        </div>
    @else
    <div class="space-y-2">
        @foreach($watchlist as $w)
        @php
            $sigColor = match($w['signal_type']) {
                'BUY' => '#16a34a', 'SELL' => '#dc2626', 'HOLD' => '#ca8a04', default => null,
            };
        @endphp
        <div class="bg-white border rounded-xl p-3" style="border-color:#e2e8f0;">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('stocks.show', $w['symbol']) }}" class="font-bold text-sm transition-colors" style="color:#0f172a;"
                           onmouseover="this.style.color='#14532D'" onmouseout="this.style.color='#0f172a'">
                            {{ $w['symbol'] }}
                        </a>
                        @if($w['signal_type'])
                            @if($w['signal_type'] === 'BUY')   <span class="badge-buy">BUY</span>
                            @elseif($w['signal_type'] === 'SELL') <span class="badge-sell">SELL</span>
                            @else <span class="badge-hold">HOLD</span>
                            @endif
                        @endif
                    </div>
                    <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($w['name'], 30) }}{{ $w['sector'] ? ' · '.$w['sector'] : '' }}</div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="font-mono font-bold text-sm" style="color:#0f172a;">{{ $w['ltp'] !== null ? number_format($w['ltp'], 2) : '—' }}</div>
                    <div class="font-mono text-xs font-semibold {{ $w['change_percent'] !== null && $w['change_percent'] >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $w['change_percent'] !== null ? ($w['change_percent'] >= 0 ? '+' : '') . number_format($w['change_percent'], 2) . '%' : '—' }}
                    </div>
                </div>
            </div>

            @if($w['signal_type'])
            <div class="flex items-center gap-2 mt-2">
                <div class="flex-1 rounded-full h-1" style="background:#f1f5f9;">
                    <div class="h-1 rounded-full" style="width:{{ $w['confidence'] }}%;background:{{ $sigColor }};"></div>
                </div>
                <span class="text-xs font-mono" style="color:#94a3b8;">{{ $w['confidence'] }}%</span>
            </div>
            @endif

            <div class="flex items-center gap-4 mt-2 pt-2 text-xs" style="border-top:1px solid #f1f5f9;color:#94a3b8;">
                <div><span style="color:#cbd5e1;">High</span> <span class="font-mono" style="color:#374151;">{{ $w['high'] ? number_format($w['high'], 2) : '—' }}</span></div>
                <div><span style="color:#cbd5e1;">Low</span> <span class="font-mono" style="color:#374151;">{{ $w['low'] ? number_format($w['low'], 2) : '—' }}</span></div>
                <div><span style="color:#cbd5e1;">Vol</span> <span class="font-mono" style="color:#374151;">{{ $w['volume'] ? number_format($w['volume']) : '—' }}</span></div>
                <div class="ml-auto flex items-center gap-2">
                    <a href="{{ route('stocks.show', $w['symbol']) }}" class="px-2 py-1 rounded" style="background:#DCFCE7;color:#14532D;font-weight:600;">View</a>
                    <form method="POST" action="{{ route('watchlist.destroy', $w['stock_id']) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Remove from watchlist?')"
                                class="px-2 py-1 rounded transition-colors" style="background:#fef2f2;color:#dc2626;font-weight:600;border:none;cursor:pointer;">
                            Remove
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function(){
    var inp = document.getElementById('watchlistSearch');
    var hidden = document.getElementById('watchlistStockId');
    var dd = document.getElementById('watchlistDropdown');
    var t;

    inp.addEventListener('input', function(){
        clearTimeout(t);
        hidden.value = '';
        var q = this.value.trim();
        if (q.length < 1) { dd.style.display = 'none'; return; }
        t = setTimeout(function(){
            fetch('{{ route('portfolio.search') }}?q=' + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (!data.length) { dd.style.display = 'none'; return; }
                    dd.innerHTML = data.map(function(s){
                        return '<div class="watchlist-option" data-id="'+s.id+'" data-symbol="'+s.symbol+'" data-name="'+s.name.replace(/"/g,'&quot;')+'" '+
                               'style="padding:0.625rem 0.875rem;cursor:pointer;border-bottom:1px solid #f1f5f9;" '+
                               'onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'transparent\'">'+
                               '<span style="font-weight:700;color:#0f172a;font-size:0.875rem;">'+s.symbol+'</span>'+
                               '<span style="font-size:0.75rem;color:#64748b;margin-left:0.5rem;">'+s.name+'</span></div>';
                    }).join('');
                    dd.style.display = 'block';
                });
        }, 220);
    });

    dd.addEventListener('click', function(e){
        var opt = e.target.closest('.watchlist-option');
        if (!opt) return;
        hidden.value = opt.dataset.id;
        inp.value = opt.dataset.symbol + ' — ' + opt.dataset.name;
        dd.style.display = 'none';
    });

    document.addEventListener('click', function(e){
        if (!inp.contains(e.target) && !dd.contains(e.target)) dd.style.display = 'none';
    });
})();
</script>
@endpush
