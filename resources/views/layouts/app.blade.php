<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NEPSE Analytics') — Nepal Stock Exchange</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <style>
        #sidebar { transition: transform 0.26s cubic-bezier(0.4,0,0.2,1); }
        #sidebarOverlay { display:none;position:fixed;inset:0;z-index:40;background:rgba(0,0,0,0.45); }
        .nav-link.active { color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe; }
    </style>
</head>
<body style="background:#f5f7fa;color:#0f172a;font-family:Inter,sans-serif;margin:0;padding:0;">

    <div id="sidebarOverlay" onclick="closeSidebar()"></div>

    <aside id="sidebar"
           style="position:fixed;top:0;left:0;bottom:0;width:256px;z-index:50;
                  background:#ffffff;border-right:1px solid #e2e8f0;
                  display:flex;flex-direction:column;
                  transform:translateX(-100%);">

        <div style="display:flex;align-items:center;gap:0.75rem;padding:1.25rem;border-bottom:1px solid #e2e8f0;">
            <button id="sidebarCloseBtn" onclick="closeSidebar()"
                    style="display:none;align-items:center;justify-content:center;
                           width:32px;height:32px;border-radius:0.5rem;border:none;
                           background:#f1f5f9;cursor:pointer;color:#64748b;flex-shrink:0;"
                    aria-label="Close menu">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div style="width:36px;height:36px;border-radius:0.5rem;flex-shrink:0;
                        background:linear-gradient(135deg,#2563eb,#7c3aed);
                        display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <div style="font-weight:700;font-size:0.9375rem;color:#0f172a;line-height:1.2;">NEPSE</div>
                <div style="font-size:0.7rem;color:#94a3b8;">Analytics Platform</div>
            </div>
        </div>

        <nav style="flex:1;overflow-y:auto;padding:0.75rem;">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('stocks.index') }}" class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Markets
            </a>
            <a href="{{ route('screener.index') }}" class="nav-link {{ request()->routeIs('screener.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/>
                </svg>
                Screener
            </a>
            <a href="{{ route('signals.index') }}" class="nav-link {{ request()->routeIs('signals.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Signals
            </a>
            <a href="{{ route('top-picks.index') }}" class="nav-link {{ request()->routeIs('top-picks.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <span>Top Picks</span>
                <span style="margin-left:auto;font-size:0.65rem;font-weight:700;padding:0.15rem 0.45rem;border-radius:9999px;background:#dcfce7;color:#15803d;">5</span>
            </a>
            <a href="{{ route('ipo.index') }}" class="nav-link {{ request()->routeIs('ipo.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                IPO Results
            </a>
            @auth
            <a href="{{ route('watchlist.index') }}" class="nav-link {{ request()->routeIs('watchlist.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Watchlist
            </a>
            @endauth
        </nav>

        <div style="padding:0.75rem;border-top:1px solid #e2e8f0;">
            @auth
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem;">
                <div style="width:32px;height:32px;border-radius:9999px;flex-shrink:0;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:700;color:#fff;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;font-weight:500;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;cursor:pointer;color:#94a3b8;padding:0.25rem;" onmouseover="this.style.color='#64748b'" onmouseout="this.style.color='#94a3b8'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
            @else
            <a href="{{ route('login') }}" class="btn-primary" style="width:100%;justify-content:center;">Login</a>
            @endauth
        </div>
    </aside>

    <div id="mainContent" style="min-height:100vh;display:flex;flex-direction:column;margin-left:0;transition:margin-left 0.26s cubic-bezier(0.4,0,0.2,1);">

        @guest
        {{-- Free trial countdown banner --}}
        <div id="trialBanner" style="display:flex;align-items:center;justify-content:center;gap:.625rem;padding:.35rem 1rem;background:linear-gradient(90deg,#dc2626,#b91c1c);color:#fff;font-size:.78rem;font-weight:600;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:.35rem;">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Free trial expires in
            </span>
            <span id="trialCountdown" style="font-family:'JetBrains Mono',monospace;font-size:.85rem;letter-spacing:.04em;background:rgba(0,0,0,.2);padding:.1rem .5rem;border-radius:.375rem;">10:00</span>
            <span style="color:rgba(255,255,255,.75);">— <a href="{{ route('login') }}" style="color:#fbbf24;font-weight:700;text-decoration:none;padding:.15rem .6rem;background:rgba(255,255,255,.15);border-radius:.375rem;border:1px solid rgba(255,255,255,.3);">⭐ Go Premium</a> for full access</span>
        </div>
        @endguest

        <header style="position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;padding:0.625rem 1rem;background:rgba(255,255,255,0.96);backdrop-filter:blur(8px);border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;gap:0.5rem;flex:1;min-width:0;">
                <button id="hamburgerBtn" onclick="openSidebar()" aria-label="Open menu"
                        style="display:none;align-items:center;justify-content:center;width:36px;height:36px;border-radius:0.5rem;border:none;background:#f1f5f9;cursor:pointer;color:#374151;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div style="position:relative;max-width:280px;width:100%;">
                    <svg style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="globalSearch" type="text" placeholder="Search stocks…" autocomplete="off"
                           style="width:100%;padding:0.5rem 0.75rem 0.5rem 2rem;font-size:0.875rem;border-radius:0.5rem;outline:none;background:#f1f5f9;border:1px solid #e2e8f0;color:#0f172a;box-sizing:border-box;">
                    <div id="searchDropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:60;border-radius:0.625rem;overflow:hidden;background:#fff;border:1px solid #e2e8f0;box-shadow:0 8px 24px rgba(0,0,0,0.1);"></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;margin-left:0.75rem;">
                <span id="marketTime" style="font-size:0.75rem;font-family:'JetBrains Mono',monospace;color:#94a3b8;white-space:nowrap;"></span>
                @if(isset($summary) && $summary)
                <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.8125rem;">
                    <span style="color:#94a3b8;">NEPSE</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#0f172a;">{{ number_format($summary->nepse_index, 2) }}</span>
                    @if($summary->nepse_change >= 0)
                        <span class="change-pos" style="font-size:0.75rem;">▲ {{ number_format($summary->nepse_change, 2) }}</span>
                    @else
                        <span class="change-neg" style="font-size:0.75rem;">▼ {{ number_format(abs($summary->nepse_change), 2) }}</span>
                    @endif
                </div>
                @endif
            </div>
        </header>

        <main style="flex:1;padding:1.25rem 1rem;">
            @if(session('success'))
            <div style="margin-bottom:1.25rem;padding:0.75rem 1rem;border-radius:0.5rem;font-size:0.875rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;">
                {{ session('success') }}
            </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
    (function(){
        var sidebar   = document.getElementById('sidebar');
        var overlay   = document.getElementById('sidebarOverlay');
        var main      = document.getElementById('mainContent');
        var hamburger = document.getElementById('hamburgerBtn');
        var closeBtn  = document.getElementById('sidebarCloseBtn');

        function mobile(){ return window.innerWidth < 768; }

        function applyLayout(){
            if(mobile()){
                sidebar.style.transform = 'translateX(-100%)';
                overlay.style.display   = 'none';
                main.style.marginLeft   = '0';
                hamburger.style.display = 'flex';
                closeBtn.style.display  = 'flex';
            } else {
                sidebar.style.transform = 'translateX(0)';
                overlay.style.display   = 'none';
                main.style.marginLeft   = '256px';
                hamburger.style.display = 'none';
                closeBtn.style.display  = 'none';
            }
        }

        window.openSidebar = function(){
            sidebar.style.transform = 'translateX(0)';
            overlay.style.display   = 'block';
        };
        window.closeSidebar = function(){
            sidebar.style.transform = 'translateX(-100%)';
            overlay.style.display   = 'none';
        };

        sidebar.querySelectorAll('a').forEach(function(a){
            a.addEventListener('click', function(){ if(mobile()) closeSidebar(); });
        });

        applyLayout();
        var t;
        window.addEventListener('resize', function(){ clearTimeout(t); t=setTimeout(applyLayout,60); });
    })();

    (function(){
        function tick(){
            var el=document.getElementById('marketTime');
            if(!el)return;
            var n=new Date();
            var npt=new Date(n.getTime()+(5*60+45)*60000-n.getTimezoneOffset()*60000);
            el.textContent='NPT '+npt.toTimeString().slice(0,8);
        }
        tick(); setInterval(tick,1000);
    })();

    (function(){
        var inp=document.getElementById('globalSearch');
        var dd=document.getElementById('searchDropdown');
        var t;
        if(!inp)return;
        inp.addEventListener('input',function(){
            clearTimeout(t);
            var q=this.value.trim();
            if(q.length<1){dd.style.display='none';return;}
            t=setTimeout(function(){
                fetch('/api/search?q='+encodeURIComponent(q))
                .then(function(r){return r.json();})
                .then(function(data){
                    if(!data.length){dd.style.display='none';return;}
                    dd.innerHTML=data.map(function(s){
                        return '<a href="'+s.url+'" style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0.875rem;border-bottom:1px solid #f1f5f9;text-decoration:none;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'transparent\'">'+
                        '<div><span style="font-weight:700;color:#0f172a;font-size:0.875rem;">'+s.symbol+'</span>'+
                        '<span style="font-size:0.75rem;color:#64748b;margin-left:0.5rem;">'+s.name+'</span></div>'+
                        '<div style="text-align:right;">'+(s.close?'<div style="font-size:0.8rem;font-family:monospace;color:#0f172a;">NPR '+parseFloat(s.close).toFixed(2)+'</div>':'')+
                        (s.change_percent!=null?'<div style="font-size:0.7rem;color:'+(parseFloat(s.change_percent)>=0?'#16a34a':'#dc2626')+'">'+(parseFloat(s.change_percent)>=0?'+':'')+parseFloat(s.change_percent).toFixed(2)+'%</div>':'')+
                        '</div></a>';
                    }).join('');
                    dd.style.display='block';
                });
            },240);
        });
        document.addEventListener('click',function(e){
            if(!inp.contains(e.target)&&!dd.contains(e.target)) dd.style.display='none';
        });
    })();

    (function(){
        var el=document.getElementById('trialCountdown');
        if(!el)return;
        var secs=600; // 10 minutes
        function fmt(s){
            var m=Math.floor(s/60);
            var ss=s%60;
            return (m<10?'0':'')+m+':'+(ss<10?'0':'')+ss;
        }
        el.textContent=fmt(secs);
        setInterval(function(){
            secs--;
            if(secs<0) secs=599; // reset to 9:59 after 0:00
            el.textContent=fmt(secs);
        },1000);
    })();
    </script>
    @stack('scripts')
</body>
</html>
