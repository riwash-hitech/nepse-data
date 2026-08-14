@extends('layouts.app')
@section('title', 'Adjust Holdings')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">✏️ Adjust Holdings</h1>
        <a href="{{ route('portfolio.transactions') }}" class="btn-ghost">Transaction History →</a>
    </div>

    @if($errors->any())
    <div class="px-4 py-3 rounded-lg text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="glass p-5">
        <div class="flex items-center gap-2 mb-4 pb-4" style="border-bottom:1px solid #f1f5f9;">
            <span class="text-xs font-semibold uppercase tracking-wide" style="color:#94a3b8;">Transaction Date</span>
        </div>

        <form method="POST" action="{{ route('portfolio.adjust.store') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Transaction Date *</label>
                    <input type="date" name="txn_date" value="{{ old('txn_date', now()->format('Y-m-d')) }}" required
                           max="{{ now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Transaction Type *</label>
                    <select name="type" required
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                        <option value="buy" {{ old('type') === 'buy' ? 'selected' : '' }}>Buy (Add)</option>
                        <option value="sell" {{ old('type') === 'sell' ? 'selected' : '' }}>Sell (Deduct)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pb-4 mb-4" style="border-bottom:1px solid #f1f5f9;">
                <div class="md:col-span-4 relative">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Company *</label>
                    <div class="relative">
                        <input type="text" id="companySearch" autocomplete="off" placeholder="Select Company"
                               value="{{ old('symbol_display') }}"
                               class="w-full pl-3 pr-9 py-2 text-sm rounded-lg"
                               style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                        <svg class="w-4 h-4 absolute" style="right:0.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="hidden" name="symbol" id="symbolInput" value="{{ old('symbol') }}" required>
                    <div id="companyDropdown" style="display:none;position:absolute;z-index:20;left:0;right:0;top:100%;margin-top:4px;background:#ffffff;border:1px solid #e2e8f0;border-radius:0.5rem;max-height:260px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,0.08);"></div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Qty to Add *</label>
                    <input type="number" name="quantity" min="1" required value="{{ old('quantity') }}"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Rate per Share *</label>
                    <input type="number" name="rate" min="0" step="0.01" required value="{{ old('rate') }}"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold mb-1" style="color:#334155;">Remarks</label>
                    <input type="text" name="remarks" maxlength="255" value="{{ old('remarks') }}"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Add</button>
                <button type="reset" class="btn-ghost">Reset</button>
            </div>
        </form>
    </div>

    <div class="p-4 rounded-xl text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
        Buying updates your average cost (weighted). Selling realizes gain/loss against your current average cost —
        you cannot sell more shares than you currently hold.
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var inp = document.getElementById('companySearch');
    var hidden = document.getElementById('symbolInput');
    var dd = document.getElementById('companyDropdown');
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
                        return '<div class="company-option" data-symbol="'+s.symbol+'" data-name="'+s.name.replace(/"/g,'&quot;')+'" '+
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
        var opt = e.target.closest('.company-option');
        if (!opt) return;
        hidden.value = opt.dataset.symbol;
        inp.value = opt.dataset.symbol + ' — ' + opt.dataset.name;
        dd.style.display = 'none';
    });

    document.addEventListener('click', function(e){
        if (!inp.contains(e.target) && !dd.contains(e.target)) dd.style.display = 'none';
    });
})();
</script>
@endpush
