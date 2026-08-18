<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwash Money — NEPSE Portfolio & Market Analytics</title>
    <meta name="description" content="A NEPSE portfolio management platform — track your holdings, watchlist and live market data on web and mobile. Buy/sell signals are informational indicators only, not investment advice.">
    <meta name="keywords" content="NEPSE, Nepal Stock Exchange, NEPSE portfolio tracker, NEPSE app, stock market Nepal, share market Nepal, NEPSE watchlist, NEPSE buy sell signals, NEPSE index, portfolio management Nepal, IPO result Nepal, stock screener Nepal, Riwash Money">
    <meta name="author" content="Riwash Money">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Riwash Money">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="Riwash Money — NEPSE Portfolio & Market Analytics">
    <meta property="og:description" content="A NEPSE portfolio management platform — track your holdings, watchlist and live market data on web and mobile. Buy/sell signals are informational indicators only, not investment advice.">
    <meta property="og:image" content="{{ asset('images/bs.png') }}">
    <meta property="og:image:alt" content="Riwash Money — NEPSE portfolio tracking app">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Riwash Money — NEPSE Portfolio & Market Analytics">
    <meta name="twitter:description" content="A NEPSE portfolio management platform — track your holdings, watchlist and live market data on web and mobile. Buy/sell signals are informational indicators only, not investment advice.">
    <meta name="twitter:image" content="{{ asset('images/bs.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{
            --brand:#14532D; --accent:#16A34A; --accent-dim:#DCFCE7;
            --bg:#F3F6F4; --surface:#FFFFFF; --border:#E1E8E3;
            --text:#10171A; --text-2:#6B7684;
        }
        *{box-sizing:border-box;}
        body{margin:0;font-family:Inter,ui-sans-serif,system-ui,sans-serif;background:var(--bg);color:var(--text);}
        a{text-decoration:none;color:inherit;}
        .wrap{max-width:1120px;margin:0 auto;padding:0 1.5rem;}
        header{position:sticky;top:0;z-index:20;background:rgba(243,246,244,.85);backdrop-filter:blur(8px);border-bottom:1px solid var(--border);}
        .nav{display:flex;align-items:center;justify-content:space-between;padding:1rem 0;}
        .brand{display:flex;align-items:center;gap:.625rem;font-weight:700;font-size:1.05rem;}
        .brand img{width:34px;height:34px;border-radius:8px;object-fit:cover;}
        .nav-links{display:flex;align-items:center;gap:2rem;}
        .nav-links a{font-size:.9rem;font-weight:600;color:var(--text-2);transition:color .15s;}
        .nav-links a:hover{color:var(--brand);}
        .nav-actions{display:flex;align-items:center;gap:.75rem;}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:.6rem 1.15rem;border-radius:.6rem;font-weight:600;font-size:.9rem;border:1px solid transparent;transition:.15s;}
        .btn-ghost{color:var(--brand);}
        .btn-ghost:hover{background:var(--accent-dim);}
        .btn-primary{background:var(--brand);color:#fff;}
        .btn-primary:hover{background:#0f3f22;}
        .btn-lg{padding:.85rem 1.75rem;font-size:1rem;border-radius:.75rem;}

        .hero-band{background:linear-gradient(135deg,#ede9fe 0%,#eafaf0 55%,#f3f6f4 100%);margin:0 0 3.5rem;padding:3.5rem 0;}
        .hero-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:3rem;align-items:center;}
        .hero{text-align:left;padding:0;}
        .hero .badge{display:inline-flex;align-items:center;gap:.4rem;background:var(--surface);color:var(--brand);font-size:.8rem;font-weight:600;padding:.4rem .85rem;border-radius:999px;margin-bottom:1.5rem;box-shadow:0 1px 2px rgba(16,23,26,.06);}
        .hero h1{font-size:clamp(2rem,4.2vw,3.1rem);line-height:1.14;margin:0 0 1.1rem;font-weight:800;letter-spacing:-.02em;}
        .hero h1 span{color:var(--accent);}
        .hero p{max-width:520px;margin:0 0 2rem;color:var(--text-2);font-size:1.05rem;line-height:1.6;}
        .hero-actions{display:flex;gap:.85rem;justify-content:flex-start;flex-wrap:wrap;}
        .hero-visual{display:flex;align-items:center;justify-content:center;}
        .hero-visual .hero-photo{max-width:340px;width:100%;display:block;border-radius:1.5rem;box-shadow:0 30px 70px rgba(16,23,26,.18);}
        @media(max-width:820px){
            .hero-grid{grid-template-columns:1fr;}
            .hero{text-align:center;}
            .hero p{margin:0 auto 2rem;}
            .hero-actions{justify-content:center;}
            .hero-visual{margin-top:1rem;}
        }

        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;padding:2rem 0 3.5rem;}
        .stat{background:var(--surface);border:1px solid var(--border);border-radius:1rem;padding:1.25rem;text-align:center;}
        .stat .n{font-size:1.6rem;font-weight:800;color:var(--brand);}
        .stat .l{font-size:.8rem;color:var(--text-2);margin-top:.15rem;}

        section{padding:3.5rem 0;}
        .section-head{text-align:center;max-width:640px;margin:0 auto 2.5rem;}
        .section-head h2{font-size:clamp(1.5rem,3vw,2rem);font-weight:800;margin:0 0 .6rem;letter-spacing:-.01em;}
        .section-head p{color:var(--text-2);margin:0;line-height:1.6;}

        .features{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;}
        .feature{background:var(--surface);border:1px solid var(--border);border-radius:1.1rem;padding:1.75rem;transition:.15s;}
        .feature:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 8px 24px rgba(20,83,45,.08);}
        .feature .icon{width:44px;height:44px;border-radius:.75rem;background:var(--accent-dim);color:var(--brand);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;}
        .feature h3{margin:0 0 .5rem;font-size:1.05rem;font-weight:700;}
        .feature p{margin:0;color:var(--text-2);font-size:.9rem;line-height:1.55;}

        .checklist{list-style:none;margin:0 auto 2rem;padding:0;display:flex;flex-wrap:wrap;justify-content:center;gap:.75rem 1.75rem;max-width:760px;}
        .checklist li{display:flex;gap:.5rem;align-items:center;color:var(--text);font-size:.9rem;font-weight:500;}
        .checklist li svg{flex-shrink:0;color:var(--accent);}
        .up{color:var(--accent);font-weight:700;}
        .down{color:#DC2626;font-weight:700;}

        .showcase{display:flex;align-items:center;justify-content:center;gap:1.5rem;padding:2.5rem 0 1rem;flex-wrap:wrap;}
        .phone{position:relative;width:220px;border-radius:2rem;background:#0f172a;padding:.45rem;box-shadow:0 30px 60px rgba(16,23,26,.16);transition:transform .25s;flex-shrink:0;}
        .phone:hover{transform:translateY(-8px);}
        .phone img{width:100%;display:block;border-radius:1.6rem;}
        .phone.main{width:250px;z-index:2;}
        .phone.side{margin-top:2.5rem;opacity:.92;}
        .phone.side:nth-child(1){transform:rotate(-4deg);}
        .phone.side:nth-child(3){transform:rotate(4deg);}
        .phone.side:hover{transform:translateY(-8px) rotate(0deg);opacity:1;}
        .phone-caption{text-align:center;font-size:.8rem;font-weight:600;color:var(--text-2);margin-top:.85rem;}
        @media(max-width:760px){
            .showcase{gap:.75rem;}
            .phone{width:150px;}
            .phone.main{width:170px;}
            .phone.side{margin-top:1.5rem;}
        }
        @media(max-width:480px){
            .phone.side{display:none;}
        }

        .mobile-promo{display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;background:linear-gradient(135deg,#eef2f0,#e6efe8);border-radius:1.75rem;padding:3rem;}
        .mobile-promo .copy h2{font-size:1.75rem;font-weight:800;margin:0 0 .75rem;letter-spacing:-.01em;}
        .mobile-promo .copy p{color:var(--text-2);line-height:1.65;margin:0 0 1.5rem;font-size:.95rem;}
        .checklist.left{flex-direction:column;align-items:flex-start;justify-content:flex-start;margin:0 0 1.75rem;max-width:none;}
        .checklist.left li{font-size:.925rem;}
        .mobile-promo .visual{display:flex;align-items:center;justify-content:center;}
        .mobile-promo .visual-frame{background:var(--surface);border-radius:1.5rem;padding:1.5rem 2rem;box-shadow:0 24px 48px rgba(16,23,26,.1);display:flex;align-items:center;justify-content:center;}
        .promo-duo{display:flex;align-items:center;}
        .promo-duo img{width:170px;display:block;border-radius:1.25rem;box-shadow:0 20px 40px rgba(16,23,26,.16);transition:transform .2s;}
        .promo-duo img:first-child{margin-right:-2.5rem;margin-top:1.75rem;transform:rotate(-6deg);z-index:1;}
        .promo-duo img:last-child{transform:rotate(5deg);z-index:2;}
        .promo-duo img:hover{transform:translateY(-6px) rotate(0deg);z-index:3;}
        @media(max-width:480px){
            .promo-duo img{width:130px;}
        }
        @media(max-width:820px){
            .mobile-promo{grid-template-columns:1fr;padding:2rem;text-align:center;}
            .checklist.left{align-items:center;}
        }

        .live-dot{display:inline-block;width:7px;height:7px;border-radius:999px;background:var(--accent);margin-right:.4rem;animation:pulse 1.6s infinite;}
        @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.35;}}

        .ticker{background:var(--surface);border:1px solid var(--border);border-radius:1.25rem;padding:1.5rem 1.75rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;margin-bottom:1.25rem;}
        .ticker .idx-main .lbl{font-size:.8rem;color:var(--text-2);font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
        .ticker .idx-main .val{font-size:2.25rem;font-weight:800;margin-top:.2rem;letter-spacing:-.01em;}
        .ticker .idx-main .chg{font-size:.95rem;font-weight:700;margin-top:.25rem;}
        .ticker .idx-side{display:flex;gap:2.25rem;flex-wrap:wrap;}
        .ticker .idx-side .item{text-align:right;}
        .ticker .idx-side .item .l{font-size:.72rem;color:var(--text-2);font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
        .ticker .idx-side .item .v{font-size:1.05rem;font-weight:700;margin-top:.2rem;}

        .movers-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.1rem;}
        .movers-card{background:var(--surface);border:1px solid var(--border);border-radius:1.1rem;padding:1.15rem 1.3rem;}
        .movers-card h4{display:flex;align-items:center;gap:.4rem;margin:0 0 .85rem;font-size:.85rem;font-weight:700;color:var(--text);}
        .movers-card h4 svg{flex-shrink:0;}
        .mv-row{display:flex;justify-content:space-between;align-items:baseline;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.875rem;}
        .mv-row:last-child{border-bottom:none;}
        .mv-row .sym{font-weight:700;}
        .mv-row .px{color:var(--text-2);font-size:.78rem;margin-left:.4rem;}
        .market-empty{text-align:center;color:var(--text-2);font-size:.9rem;padding:1.5rem;background:var(--surface);border:1px dashed var(--border);border-radius:1rem;}

        .plans{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;}
        .plan{background:var(--surface);border:1px solid var(--border);border-radius:1.25rem;padding:2rem 1.75rem;display:flex;flex-direction:column;}
        .plan.featured{border-color:var(--accent);box-shadow:0 12px 32px rgba(20,83,45,.1);position:relative;}
        .plan.featured::before{content:'Most Popular';position:absolute;top:-.7rem;left:50%;transform:translateX(-50%);background:var(--accent);color:#fff;font-size:.7rem;font-weight:700;padding:.3rem .8rem;border-radius:999px;}
        .plan h3{margin:0 0 .35rem;font-size:1.1rem;font-weight:700;}
        .plan .price{font-size:2rem;font-weight:800;margin:.5rem 0 .25rem;}
        .plan .price span{font-size:.9rem;font-weight:500;color:var(--text-2);}
        .plan .desc{color:var(--text-2);font-size:.85rem;margin-bottom:1.25rem;}
        .plan ul{list-style:none;margin:0 0 1.5rem;padding:0;display:flex;flex-direction:column;gap:.55rem;flex:1;}
        .plan li{display:flex;gap:.55rem;align-items:flex-start;font-size:.875rem;}
        .plan li svg{flex-shrink:0;margin-top:.15rem;color:var(--accent);}
        .plan .btn{width:100%;}

        .cta{background:var(--brand);border-radius:1.5rem;padding:3rem 2rem;text-align:center;color:#fff;margin:1rem 0 0;}
        .cta h2{font-size:1.75rem;font-weight:800;margin:0 0 .75rem;}
        .cta p{color:#dcfce7;margin:0 0 1.75rem;}
        .cta .btn-primary{background:#fff;color:var(--brand);}
        .cta .btn-primary:hover{background:#f0fdf4;}

        footer{border-top:1px solid var(--border);padding:2rem 0;margin-top:2rem;}
        .disclaimer{display:flex;gap:.75rem;align-items:flex-start;font-size:.82rem;color:#7f1d1d;line-height:1.65;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:.75rem;padding:1rem 1.25rem;margin:0 0 1.5rem;}
        .disclaimer svg{flex-shrink:0;margin-top:.15rem;color:#dc2626;}
        .disclaimer p{margin:0;}
        .disclaimer strong{color:#b91c1c;font-weight:700;}
        .foot-row{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;color:var(--text-2);font-size:.85rem;}

        @media(max-width:860px){
            .features{grid-template-columns:repeat(2,1fr);}
            .split{grid-template-columns:1fr;}
            .stats{grid-template-columns:repeat(2,1fr);}
            .movers-grid{grid-template-columns:1fr;}
            .ticker{justify-content:flex-start;}
            .ticker .idx-side{gap:1.5rem;}
            .ticker .idx-side .item{text-align:left;}
        }
        @media(max-width:560px){
            .features{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

<header>
    <div class="wrap nav">
        <a href="{{ route('landing') }}" class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="Riwash Money">
            Riwash Money
        </a>
        <nav class="nav-links">
            <a href="#live-market">Dashboard</a>
            <a href="{{ route('stocks.index') }}">Market</a>
            <a href="#portfolio">Portfolio</a>
            <a href="#pricing">Pricing</a>
        </nav>
        <div class="nav-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
            @endauth
        </div>
    </div>
</header>

<div class="hero-band">
    <div class="wrap hero-grid">
        <div class="hero">
            <span class="badge">📈 Live NEPSE data</span>
            <h1>Your NEPSE portfolio,<br><span>tracked properly.</span></h1>
            <p>Riwash Money is built specially for NEPSE <strong>portfolio management</strong> — live market data, portfolio P&amp;L, watchlists and IPO tracking in one clean dashboard, on the web and in your pocket. Buy/sell signals are technical indicators for information only, not trading recommendations.</p>
            <div class="hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Log in</a>
                @endauth
            </div>
        </div>
        <div class="hero-visual">
            <img class="hero-photo" src="{{ asset('images/bs.png') }}" alt="Riwash Money app showing buy/sell signals" loading="lazy">
        </div>
    </div>
</div>

<div class="wrap">

    <section id="live-market">
        <div class="section-head" style="margin-bottom:1.5rem;">
            <h2><span class="live-dot"></span>Live Market</h2>
            <p>Straight from the exchange — refreshed every few minutes.</p>
        </div>
        @php $nepse = $indices->firstWhere('symbol', 'NEPSE'); @endphp
        @if($nepse)
            <div class="ticker">
                <div class="idx-main">
                    <div class="lbl">NEPSE Index</div>
                    <div class="val">{{ number_format($nepse['close'], 2) }}</div>
                    <div class="chg {{ $nepse['change'] >= 0 ? 'up' : 'down' }}">
                        {{ $nepse['change'] >= 0 ? '▲' : '▼' }} {{ number_format(abs($nepse['change']), 2) }}
                        ({{ number_format($nepse['change_percent'], 2) }}%)
                    </div>
                </div>
                <div class="idx-side">
                    <div class="item">
                        <div class="l">Turnover</div>
                        <div class="v">{{ $totalTurnover }}</div>
                    </div>
                    @foreach($indices->where('symbol', '!=', 'NEPSE') as $idx)
                        <div class="item">
                            <div class="l">{{ $idx['label'] }}</div>
                            <div class="v {{ $idx['change'] >= 0 ? 'up' : 'down' }}">{{ number_format($idx['close'], 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="market-empty" style="margin-bottom:1.25rem;">Live index data is temporarily unavailable.</div>
        @endif

        <div class="movers-grid">
            <div class="movers-card">
                <h4><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent);"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8L11 17l-4-4-6 6"/></svg> Top Gainers</h4>
                @forelse($gainers as $g)
                    <div class="mv-row">
                        <span><span class="sym">{{ $g['symbol'] }}</span><span class="px">Rs. {{ number_format($g['ltp'], 2) }}</span></span>
                        <span class="up">+{{ number_format($g['change_percent'], 2) }}%</span>
                    </div>
                @empty
                    <div class="mv-row"><span>No data available</span></div>
                @endforelse
            </div>

            <div class="movers-card">
                <h4><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:#DC2626;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0v-8m0 8L11 7l-4 4-6-6"/></svg> Top Losers</h4>
                @forelse($losers as $l)
                    <div class="mv-row">
                        <span><span class="sym">{{ $l['symbol'] }}</span><span class="px">Rs. {{ number_format($l['ltp'], 2) }}</span></span>
                        <span class="down">{{ number_format($l['change_percent'], 2) }}%</span>
                    </div>
                @empty
                    <div class="mv-row"><span>No data available</span></div>
                @endforelse
            </div>

            <div class="movers-card">
                <h4><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--brand);"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10m6 10V4m6 16v-7"/></svg> Volume Leaders</h4>
                @forelse($volumeLeaders as $v)
                    <div class="mv-row">
                        <span><span class="sym">{{ $v['symbol'] }}</span><span class="px">Rs. {{ number_format($v['ltp'], 2) }}</span></span>
                        <span class="px">{{ number_format($v['volume'] / 1000, 0) }}k vol</span>
                    </div>
                @empty
                    <div class="mv-row"><span>No data available</span></div>
                @endforelse
            </div>
        </div>
    </section>

    <div class="stats">
        <div class="stat"><div class="n">Live</div><div class="l">Market Indices</div></div>
        <div class="stat"><div class="n">WACC</div><div class="l">Portfolio Accounting</div></div>
        <div class="stat"><div class="n">Daily</div><div class="l">Buy/Sell Signals</div></div>
        <div class="stat"><div class="n">Free</div><div class="l">To get started</div></div>
    </div>

    <section id="portfolio">
        <div class="mobile-promo">
            <div class="copy">
                <h2>Your portfolio, always in your pocket</h2>
                <p>Live NEPSE index, portfolio summary and watchlist — the Riwash Money app keeps your positions one tap away, wherever you are.</p>
                <ul class="checklist left">
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Live NEPSE index chart with 1D–5Y ranges</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Portfolio value &amp; P&amp;L at a glance</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Watchlist with live price &amp; change%</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-primary">Get the app — create your account</a>
            </div>
            <div class="visual">
                <div class="visual-frame">
                    <div class="promo-duo">
                        <img src="{{ asset('images/sshow.png') }}" alt="Riwash Money app — live NEPSE index and portfolio">
                        <img src="{{ asset('images/bs.png') }}" alt="Riwash Money app — buy/sell signals">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features">
        <div class="section-head">
            <h2>Everything you need to follow the market</h2>
            <p>Built for NEPSE investors who want more than a static quote page.</p>
        </div>
        <div class="features">
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
                </div>
                <h3>Live Market Dashboard</h3>
                <p>NEPSE, Sensitive, Float and Sen. Float indices, top gainers/losers and turnover — refreshed straight from the exchange.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                </div>
                <h3>Portfolio Tracking</h3>
                <p>Log every buy/sell, get weighted-average cost, unrealized &amp; realized gains, sector allocation and day-by-day P&amp;L.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m3-9a9 9 0 100 18 9 9 0 000-18z"/></svg>
                </div>
                <h3>Watchlist</h3>
                <p>Track LTP, high, low, volume and % change for the stocks you care about, without owning them yet.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <h3>Buy/Sell Signals</h3>
                <p>Support/resistance levels, alpha/beta, volume analytics and value-at-risk on every stock detail page. <em>Informational only — not investment advice; do your own research before trading.</em></p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3>IPO Result Checker</h3>
                <p>Check IPO allotment results across companies without hunting down each registrar's site individually.</p>
            </div>
            <div class="feature">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l7-7 3 3-7 7-3-3zM18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                </div>
                <h3>Screener &amp; Top Picks</h3>
                <p>Filter the whole exchange by sector, price, and performance to shortlist stocks worth a closer look.</p>
            </div>
        </div>
    </section>

    <section id="app">
        <div class="section-head">
            <h2>Now available on mobile</h2>
            <p>The same live dashboard, portfolio and watchlist as the web app — in the Riwash Money Android app.</p>
        </div>

        <ul class="checklist">
            <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Live dashboard with pull-to-refresh</li>
            <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Full portfolio &amp; watchlist parity with web</li>
            <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Stock detail charts, signals &amp; risk metrics</li>
        </ul>

        <div class="showcase">
            <div class="phone side">
                <img src="{{ asset('images/a-watchlist.png') }}" alt="Riwash Money app — Watchlist screen" loading="lazy">
            </div>
            <div class="phone main">
                <img src="{{ asset('images/a-dash.png') }}" alt="Riwash Money app — Dashboard screen" loading="lazy">
            </div>
            <div class="phone side">
                <img src="{{ asset('images/a-portfolio.png') }}" alt="Riwash Money app — Portfolio screen" loading="lazy">
            </div>
        </div>
        <div class="phone-caption">Watchlist · Dashboard · Portfolio — live on your phone</div>

        <div style="text-align:center;margin-top:2.25rem;">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create account to get started</a>
        </div>
    </section>

    <section id="pricing">
        <div class="section-head">
            <h2>Simple, transparent pricing</h2>
            <p>Start free. Upgrade when you need deeper analytics.</p>
        </div>
        <div class="plans">
            <div class="plan">
                <h3>Free</h3>
                <div class="price">Rs. 0<span>/month</span></div>
                <div class="desc">For casual investors keeping an eye on the market.</div>
                <ul>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Live market dashboard</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Watchlist (up to 10 stocks)</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Basic portfolio tracking</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Mobile app access</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-ghost" style="border:1px solid var(--border);">Get started</a>
            </div>
            <div class="plan featured">
                <h3>Pro</h3>
                <div class="price">Rs. 499<span>/month</span></div>
                <div class="desc">For active traders who want signals and deeper analytics.</div>
                <ul>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Everything in Free</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Unlimited watchlist</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Buy/sell signals &amp; predictions</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Support/resistance, alpha/beta, VaR</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Screener &amp; Top Picks</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-primary">Get started</a>
            </div>
            <div class="plan">
                <h3>Elite</h3>
                <div class="price">Rs. 999<span>/month</span></div>
                <div class="desc">For serious investors managing larger portfolios.</div>
                <ul>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Everything in Pro</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> IPO result checker</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> 30-day price outlook</li>
                    <li><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Priority support</li>
                </ul>
                <a href="{{ route('register') }}" class="btn btn-ghost" style="border:1px solid var(--border);">Get started</a>
            </div>
        </div>
    </section>

    <section>
        <div class="cta">
            <h2>Start tracking your NEPSE portfolio today</h2>
            <p>Free to sign up — no credit card required.</p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create free account</a>
        </div>
    </section>
</div>

<footer>
    <div class="wrap">
        <div class="disclaimer">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <p>Riwash Money is built specially for NEPSE portfolio management. Buy/sell signals, predictions and technical indicators shown on this site and app are for informational purposes only — they are <strong>not investment advice or a recommendation to trade</strong>. Do your own research and consult a licensed advisor before making any investment decision.</p>
        </div>
        <div class="foot-row">
            <div class="brand"><img src="{{ asset('images/logo.png') }}" alt="Riwash Money"> Riwash Money</div>
            <div>Powered by <a href="https://riwash.com" target="_blank" rel="noopener" style="color:var(--brand);font-weight:600;">riwash.com</a></div>
        </div>
    </div>
</footer>

</body>
</html>
