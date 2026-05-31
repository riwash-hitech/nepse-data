@extends('layouts.app')
@section('title', 'Signals')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">🎯 Trade Signals</h1>
    </div>

    {{-- Filters --}}
    <form method="GET" class="glass p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs mb-1" style="color:#64748b;">Signal Type</label>
            <div class="flex gap-1">
                @foreach(['all' => 'All', 'buy' => 'BUY', 'sell' => 'SELL'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $val]) }}"
                   class="px-3 py-1.5 text-xs rounded-md transition-colors"
                   style="background:{{ $type === $val ? 'rgba(37,99,235,0.3)' : 'rgba(255,255,255,0.06)' }};
                          color:{{ $type === $val ? '#93c5fd' : '#94a3b8' }};
                          border:1px solid {{ $type === $val ? 'rgba(37,99,235,0.5)' : 'rgba(255,255,255,0.08)' }};">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-xs mb-1" style="color:#64748b;">Min Confidence</label>
            <select name="confidence" onchange="this.form.submit()"
                    class="px-3 py-2 text-sm rounded-lg"
                    style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#f1f5f9;outline:none;">
                @foreach([50, 60, 70, 80, 90] as $c)
                <option value="{{ $c }}" {{ $minConfidence == $c ? 'selected' : '' }}>{{ $c }}%+</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Signals Grid --}}
    @if($signals->isEmpty())
        <div class="glass p-12 text-center" style="color:#64748b;">
            No signals found. Run <code class="text-blue-400">php artisan nepse:signals</code> after market data is available.
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($signals as $signal)
        <div class="glass p-5 border transition-all hover:bg-white/5"
             style="border-color:{{ $signal->signal_type === 'BUY' ? 'rgba(34,197,94,0.2)' : ($signal->signal_type === 'SELL' ? 'rgba(239,68,68,0.2)' : 'rgba(234,179,8,0.2)') }};">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-3">
                <div>
                    <a href="{{ route('stocks.show', $signal->stock->symbol) }}"
                       class="text-lg font-bold text-white hover:text-blue-400 transition-colors">
                        {{ $signal->stock->symbol }}
                    </a>
                    <div class="text-xs mt-0.5" style="color:#64748b;">{{ Str::limit($signal->stock->name, 25) }}</div>
                </div>
                @if($signal->signal_type === 'BUY')   <span class="badge-buy">BUY</span>
                @elseif($signal->signal_type === 'SELL') <span class="badge-sell">SELL</span>
                @else <span class="badge-hold">HOLD</span>
                @endif
            </div>

            {{-- Price --}}
            <div class="flex items-center gap-4 text-sm mb-3">
                <span>NPR <strong class="font-mono">{{ number_format($signal->price_at_signal, 2) }}</strong></span>
                @if($signal->rsi_value)
                <span style="color:#64748b;">RSI: <span class="{{ $signal->rsi_value < 30 ? 'change-pos' : ($signal->rsi_value > 70 ? 'change-neg' : '') }}">{{ number_format($signal->rsi_value, 1) }}</span></span>
                @endif
                <span style="color:#64748b;" class="ml-auto text-xs">{{ $signal->date->format('d M') }}</span>
            </div>

            {{-- Entry/Exit mini --}}
            <div class="grid grid-cols-3 gap-2 text-xs mb-3">
                <div class="rounded-md p-2 text-center" style="background:rgba(255,255,255,0.04);">
                    <div style="color:#64748b;">Entry</div>
                    <div class="font-mono text-white mt-0.5">{{ number_format($signal->entry_min, 0) }}–{{ number_format($signal->entry_max, 0) }}</div>
                </div>
                <div class="rounded-md p-2 text-center" style="background:rgba(239,68,68,0.08);">
                    <div style="color:#f87171;">SL</div>
                    <div class="font-mono change-neg mt-0.5">{{ number_format($signal->stop_loss, 0) }}</div>
                </div>
                <div class="rounded-md p-2 text-center" style="background:rgba(34,197,94,0.08);">
                    <div style="color:#4ade80;">T1</div>
                    <div class="font-mono change-pos mt-0.5">{{ number_format($signal->target_1, 0) }}</div>
                </div>
            </div>

            {{-- Confidence bar --}}
            <div class="flex items-center gap-2">
                <div class="flex-1 rounded-full h-1.5" style="background:rgba(255,255,255,0.08);">
                    <div class="h-1.5 rounded-full transition-all"
                         style="width:{{ $signal->confidence }}%;background:{{ $signal->signal_type === 'BUY' ? '#22c55e' : ($signal->signal_type === 'SELL' ? '#ef4444' : '#eab308') }};"></div>
                </div>
                <span class="text-xs font-mono" style="color:#94a3b8;">{{ $signal->confidence }}%</span>
            </div>

            {{-- Reasons --}}
            @if(!empty($signal->reasons))
            <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06);">
                @foreach(array_slice($signal->reasons, 0, 2) as $reason)
                <div class="text-xs flex items-center gap-1.5 mb-1" style="color:#64748b;">
                    <span style="color:#334155;">•</span> {{ $reason }}
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
