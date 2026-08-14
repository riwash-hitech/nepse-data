@extends('layouts.app')
@section('title', 'My Watchlist')

@section('content')
<div class="space-y-5">

    <h1 class="text-2xl font-bold" style="color:#0f172a;">📌 My Watchlist</h1>

    {{-- Add to watchlist --}}
    <div class="glass p-4">
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
        <div class="glass p-12 text-center" style="color:#64748b;">
            <p class="mb-3">Your watchlist is empty. Search for a stock above to start tracking it.</p>
            <a href="{{ route('stocks.index') }}" class="btn-primary inline-flex">Browse Stocks</a>
        </div>
    @else
    <div class="glass overflow-hidden">
        <div style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Symbol</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">LTP</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Change%</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Signal</th>
                    <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Confidence</th>
                    <th class="text-center px-4 py-3 text-xs font-medium uppercase tracking-wider" style="color:#475569;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($watchlist as $w)
                @php $p = $w->stock->latestPrice; $sig = $w->stock->latestSignal; @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('stocks.show', $w->stock->symbol) }}"
                           class="font-bold hover:text-blue-600 transition-colors" style="color:#0f172a;">
                            {{ $w->stock->symbol }}
                        </a>
                        <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($w->stock->name, 28) }}</div>
                    </td>
                    <td class="text-right font-mono px-4 py-3" style="color:#0f172a;">{{ $p ? number_format($p->close, 2) : '—' }}</td>
                    <td class="text-right font-mono px-4 py-3 {{ $p && $p->change_percent >= 0 ? 'change-pos' : 'change-neg' }}">
                        {{ $p ? ($p->change_percent >= 0 ? '+' : '') . number_format($p->change_percent, 2) . '%' : '—' }}
                    </td>
                    <td class="text-center px-4 py-3">
                        @if($sig)
                            @if($sig->signal_type === 'BUY')   <span class="badge-buy">BUY</span>
                            @elseif($sig->signal_type === 'SELL') <span class="badge-sell">SELL</span>
                            @else <span class="badge-hold">HOLD</span>
                            @endif
                        @else <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-right px-4 py-3">
                        @if($sig)
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-16 rounded-full h-1.5" style="background:#e2e8f0;">
                                <div class="h-1.5 rounded-full"
                                     style="width:{{ $sig->confidence }}%;background:{{ $sig->signal_type === 'BUY' ? '#22c55e' : ($sig->signal_type === 'SELL' ? '#ef4444' : '#eab308') }};"></div>
                            </div>
                            <span class="text-xs font-mono" style="color:#64748b;">{{ $sig->confidence }}%</span>
                        </div>
                        @else <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="text-center px-4 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('stocks.show', $w->stock->symbol) }}"
                               class="text-xs px-3 py-1 rounded-md"
                               style="background:#eff6ff;color:#2563eb;">View</a>
                            <form method="POST" action="{{ route('watchlist.destroy', $w->stock_id) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Remove from watchlist?')"
                                        class="text-xs px-3 py-1 rounded-md transition-colors"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
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
