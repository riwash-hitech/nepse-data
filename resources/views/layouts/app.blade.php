<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NEPSE Analytics') — Nepal Stock Exchange</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex" style="background:#f5f7fa;color:#0f172a;font-family:Inter,sans-serif;">

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col"
           style="background:#ffffff;border-right:1px solid #e2e8f0;">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-5 py-5 border-b" style="border-color:#e2e8f0;">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                 style="background:linear-gradient(135deg,#2563eb,#7c3aed);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <div class="font-bold text-base leading-tight" style="color:#0f172a;">NEPSE</div>
                <div class="text-xs" style="color:#94a3b8;">Analytics Platform</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('stocks.index') }}"
               class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Markets
            </a>
            <a href="{{ route('screener.index') }}"
               class="nav-link {{ request()->routeIs('screener.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/>
                </svg>
                Screener
            </a>
            <a href="{{ route('signals.index') }}"
               class="nav-link {{ request()->routeIs('signals.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Signals
            </a>
            @auth
            <a href="{{ route('watchlist.index') }}"
               class="nav-link {{ request()->routeIs('watchlist.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Watchlist
            </a>
            @endauth
        </nav>

        {{-- User section --}}
        <div class="p-3 border-t" style="border-color:#e2e8f0;">
            @auth
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                         style="background:linear-gradient(135deg,#2563eb,#7c3aed);">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:#0f172a;">{{ Auth::user()->name }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" style="color:#94a3b8;" class="transition-colors" onmouseover="this.style.color='#64748b'" onmouseout="this.style.color='#94a3b8'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-primary w-full justify-center">Login</a>
            @endauth
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col" style="margin-left:16rem;">

        {{-- Topbar --}}
        <header class="sticky top-0 z-40 flex items-center justify-between px-6 py-3"
                style="background:rgba(255,255,255,0.95);backdrop-filter:blur(8px);border-bottom:1px solid #e2e8f0;">
            <div class="relative w-72">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                     style="color:#475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="globalSearch" type="text" placeholder="Search stocks (e.g. NABIL, NICA)…"
                       autocomplete="off"
                       class="w-full pl-9 pr-4 py-2 text-sm rounded-lg outline-none"
                       style="background:#f1f5f9;border:1px solid #e2e8f0;color:#0f172a;">
                <div id="searchDropdown"
                     class="absolute top-full left-0 right-0 mt-1 rounded-lg shadow-xl hidden z-50 overflow-hidden"
                     style="background:#ffffff;border:1px solid #e2e8f0;">
                </div>
            </div>

            <div class="flex items-center gap-5">
                <span id="marketTime" class="text-xs font-mono" style="color:#94a3b8;"></span>
                @if(isset($summary) && $summary)
                <div class="flex items-center gap-2 text-sm">
                    <span style="color:#94a3b8;">NEPSE</span>
                    <span class="font-mono font-semibold" style="color:#0f172a;">{{ number_format($summary->nepse_index, 2) }}</span>
                    @if($summary->nepse_change >= 0)
                        <span class="change-pos text-xs">▲ {{ number_format($summary->nepse_change, 2) }}</span>
                    @else
                        <span class="change-neg text-xs">▼ {{ number_format(abs($summary->nepse_change), 2) }}</span>
                    @endif
                </div>
                @endif
            </div>
        </header>

        {{-- Page --}}
        <main class="flex-1 p-6">
            @if(session('success'))
            <div class="mb-5 px-4 py-3 rounded-lg text-sm"
                 style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;">
                {{ session('success') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        // NPT clock
        function updateTime() {
            const el = document.getElementById('marketTime');
            if (!el) return;
            const now = new Date();
            const offset = (5 * 60 + 45) * 60000;
            const npt = new Date(now.getTime() + offset - now.getTimezoneOffset() * 60000);
            el.textContent = 'NPT ' + npt.toTimeString().slice(0, 8);
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Search autocomplete
        const searchInput    = document.getElementById('globalSearch');
        const searchDropdown = document.getElementById('searchDropdown');
        let searchTimeout;

        searchInput?.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const q = this.value.trim();
            if (q.length < 1) { searchDropdown.classList.add('hidden'); return; }
            searchTimeout = setTimeout(() => {
                fetch(`/api/search?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.length) { searchDropdown.classList.add('hidden'); return; }
                        searchDropdown.innerHTML = data.map(s => `
                            <a href="${s.url}"
                               style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid #f1f5f9;text-decoration:none;"
                               onmouseover="this.style.background='#f8fafc'"
                               onmouseout="this.style.background='transparent'">
                                <div>
                                    <span style="font-weight:700;color:#0f172a;font-size:0.875rem;">${s.symbol}</span>
                                    <span style="font-size:0.75rem;color:#64748b;margin-left:0.5rem;">${s.name}</span>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:0.8125rem;font-family:monospace;color:#0f172a;">
                                        ${s.close ? 'NPR ' + parseFloat(s.close).toFixed(2) : '—'}
                                    </div>
                                    ${s.change_percent != null ? `<div style="font-size:0.75rem;color:${parseFloat(s.change_percent) >= 0 ? '#16a34a' : '#dc2626'}">${parseFloat(s.change_percent) >= 0 ? '+' : ''}${parseFloat(s.change_percent).toFixed(2)}%</div>` : ''}
                                </div>
                            </a>`).join('');
                        searchDropdown.classList.remove('hidden');
                    });
            }, 250);
        });

        document.addEventListener('click', e => {
            if (!searchInput?.contains(e.target) && !searchDropdown?.contains(e.target)) {
                searchDropdown?.classList.add('hidden');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>

