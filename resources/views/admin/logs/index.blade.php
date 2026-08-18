@extends('layouts.app')
@section('title', 'Logs')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold" style="color:#0f172a;">📜 Scheduler &amp; App Logs</h1>
        <a href="{{ route('admin.logs', ['file' => $file, 'lines' => $lines]) }}" class="btn-primary">↻ Refresh</a>
    </div>

    <div class="bg-white border rounded-xl p-4" style="border-color:#e2e8f0;">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Log file</label>
                <div class="flex gap-1">
                    @foreach($files as $key => $filename)
                    <a href="{{ route('admin.logs', ['file' => $key, 'lines' => $lines]) }}"
                       class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                       style="background:{{ $file === $key ? '#14532D' : '#f8fafc' }};
                              color:{{ $file === $key ? '#fff' : '#64748b' }};
                              border:1px solid {{ $file === $key ? '#14532D' : '#e2e8f0' }};">
                        {{ $filename }}
                    </a>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#64748b;">Lines</label>
                <div class="flex gap-1">
                    @foreach([100, 300, 1000] as $n)
                    <a href="{{ route('admin.logs', ['file' => $file, 'lines' => $n]) }}"
                       class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                       style="background:{{ $lines === $n ? '#14532D' : '#f8fafc' }};
                              color:{{ $lines === $n ? '#fff' : '#64748b' }};
                              border:1px solid {{ $lines === $n ? '#14532D' : '#e2e8f0' }};">
                        {{ $n }}
                    </a>
                    @endforeach
                </div>
            </div>
            <div class="ml-auto text-xs" style="color:#94a3b8;">
                @if($modified)
                    File size: {{ number_format($size / 1024, 1) }} KB · Last modified: {{ \Carbon\Carbon::createFromTimestamp($modified)->diffForHumans() }}
                @else
                    File not found yet — nothing has been logged here.
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white border rounded-xl overflow-hidden" style="border-color:#e2e8f0;">
        <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wider" style="background:#0f172a;color:#94a3b8;">
            storage/logs/{{ $files[$file] }} — last {{ $lines }} lines
        </div>
        <pre style="margin:0;padding:1rem;background:#0f172a;color:#e2e8f0;font-size:.78rem;line-height:1.6;overflow-x:auto;max-height:70vh;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">{{ $content !== null && trim($content) !== '' ? $content : 'Nothing logged here yet.' }}</pre>
    </div>

</div>
@endsection
