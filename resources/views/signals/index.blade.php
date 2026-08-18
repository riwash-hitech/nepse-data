@extends('layouts.app')
@section('title', 'Signals')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">🎯 Trade Signals</h1>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white border rounded-xl p-4 flex flex-wrap gap-3 items-end" style="border-color:#e2e8f0;">
        <div>
            <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Signal Type</label>
            <div class="flex gap-1">
                @foreach(['all' => 'All', 'buy' => 'BUY', 'sell' => 'SELL'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $val]) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                   style="background:{{ $type === $val ? '#14532D' : '#f8fafc' }};
                          color:{{ $type === $val ? '#fff' : '#64748b' }};
                          border:1px solid {{ $type === $val ? '#14532D' : '#e2e8f0' }};">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Min Confidence</label>
            <select name="confidence" onchange="this.form.submit()" class="market-input"
                    style="padding:.5rem .75rem;font-size:.85rem;border-radius:.5rem;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;outline:none;">
                @foreach([50, 60, 70, 80, 90] as $c)
                <option value="{{ $c }}" {{ $minConfidence == $c ? 'selected' : '' }}>{{ $c }}%+</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Signals Grid --}}
    @if($signals->isEmpty())
        <div class="bg-white border rounded-xl p-12 text-center" style="border-color:#e2e8f0;color:#94a3b8;">
            No signals match these filters right now. Try lowering the minimum confidence or switching the signal type.
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($signals as $signal)
        <div class="bg-white border rounded-xl p-5 transition-all hover:shadow-md"
             style="border-color:{{ $signal->signal_type === 'BUY' ? '#bbf7d0' : ($signal->signal_type === 'SELL' ? '#fecaca' : '#fef08a') }};">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-3">
                <div>
                    <a href="{{ route('stocks.show', $signal->stock->symbol) }}"
                       class="text-lg font-bold transition-colors" style="color:#0f172a;"
                       onmouseover="this.style.color='#14532D'" onmouseout="this.style.color='#0f172a'">
                        {{ $signal->stock->symbol }}
                    </a>
                    <div class="text-xs mt-0.5" style="color:#94a3b8;">{{ Str::limit($signal->stock->name, 25) }}</div>
                </div>
                @if($signal->signal_type === 'BUY')   <span class="badge-buy">BUY</span>
                @elseif($signal->signal_type === 'SELL') <span class="badge-sell">SELL</span>
                @else <span class="badge-hold">HOLD</span>
                @endif
            </div>

            {{-- Price --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm mb-3">
                <span style="color:#0f172a;">NPR <strong class="font-mono">{{ number_format($signal->price_at_signal, 2) }}</strong></span>
                @if($signal->rsi_value)
                <span style="color:#64748b;">RSI: <span class="{{ $signal->rsi_value < 30 ? 'change-pos' : ($signal->rsi_value > 70 ? 'change-neg' : '') }}">{{ number_format($signal->rsi_value, 1) }}</span></span>
                @endif
                <span style="color:#94a3b8;" class="ml-auto text-xs">{{ $signal->date->format('d M') }}</span>
            </div>

            {{-- Entry/Exit mini --}}
            <div class="grid grid-cols-3 gap-2 text-xs mb-3">
                <div class="rounded-md p-2 text-center" style="background:#f8fafc;">
                    <div style="color:#94a3b8;">Entry</div>
                    <div class="font-mono mt-0.5" style="color:#0f172a;">{{ number_format($signal->entry_min, 0) }}–{{ number_format($signal->entry_max, 0) }}</div>
                </div>
                <div class="rounded-md p-2 text-center" style="background:#fef2f2;">
                    <div style="color:#dc2626;">SL</div>
                    <div class="font-mono change-neg mt-0.5">{{ number_format($signal->stop_loss, 0) }}</div>
                </div>
                <div class="rounded-md p-2 text-center" style="background:#f0fdf4;">
                    <div style="color:#16a34a;">T1</div>
                    <div class="font-mono change-pos mt-0.5">{{ number_format($signal->target_1, 0) }}</div>
                </div>
            </div>

            {{-- Confidence bar --}}
            <div class="flex items-center gap-2">
                <div class="flex-1 rounded-full h-1.5" style="background:#f1f5f9;">
                    <div class="h-1.5 rounded-full transition-all"
                         style="width:{{ $signal->confidence }}%;background:{{ $signal->signal_type === 'BUY' ? '#16a34a' : ($signal->signal_type === 'SELL' ? '#dc2626' : '#ca8a04') }};"></div>
                </div>
                <span class="text-xs font-mono" style="color:#64748b;">{{ $signal->confidence }}%</span>
            </div>

            {{-- Reasons --}}
            @if(!empty($signal->reasons))
            <div class="mt-3 pt-3" style="border-top:1px solid #f1f5f9;">
                @foreach(array_slice($signal->reasons, 0, 2) as $reason)
                <div class="text-xs flex items-center gap-1.5 mb-1" style="color:#64748b;">
                    <span style="color:#cbd5e1;">•</span> {{ $reason }}
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    <div>{{ $signals->links() }}</div>
    @endif

</div>
@endsection
