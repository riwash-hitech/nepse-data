<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify email — Riwash Money</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    :root{
        --brand:#14532D; --accent:#16A34A; --accent-dim:#DCFCE7;
        --bg:#F3F6F4; --surface:#FFFFFF; --border:#E1E8E3;
        --text:#10171A; --text-2:#6B7684;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Inter, ui-sans-serif, system-ui, sans-serif;
      position: relative;
      overflow: hidden;
      padding: 1.5rem;
    }
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(20,83,45,.06) 1px, transparent 1px);
      background-size: 28px 28px;
      pointer-events: none;
    }
    .blob1 {
      position: absolute; top: -100px; right: -100px;
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(22,163,74,.16), transparent 70%);
      pointer-events: none;
    }
    .blob2 {
      position: absolute; bottom: -80px; left: -80px;
      width: 320px; height: 320px; border-radius: 50%;
      background: radial-gradient(circle, rgba(20,83,45,.12), transparent 70%);
      pointer-events: none;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card {
      position: relative; z-index: 1;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 1.25rem;
      padding: 2.25rem 2rem;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 24px 60px rgba(16,23,26,.08);
      animation: fadeUp .4s ease both;
    }
    .logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .625rem;
      margin-bottom: 1.75rem;
    }
    .logo-wrap img { width: 40px; height: 40px; border-radius: .75rem; object-fit: cover; }
    .logo-text { font-size: 1.2rem; font-weight: 800; color: var(--text); letter-spacing: -.02em; }
    .icon-circle {
      width: 56px; height: 56px; border-radius: 9999px; margin: 0 auto 1.25rem;
      background: var(--accent-dim); color: var(--brand);
      display: flex; align-items: center; justify-content: center;
    }
    h1 {
      font-size: 1.25rem; font-weight: 800; color: var(--text);
      margin-bottom: .5rem; text-align: center;
    }
    .subtitle { font-size: .875rem; color: var(--text-2); text-align: center; margin-bottom: 1.75rem; line-height: 1.6; }
    .status-box {
      background: var(--accent-dim); border: 1px solid #bbf7d0;
      border-radius: .625rem; padding: .65rem .875rem;
      font-size: .8rem; color: var(--brand);
      margin-bottom: 1.125rem; text-align: center;
    }
    .btn-login {
      width: 100%; padding: .75rem;
      font-size: .9375rem; font-weight: 700;
      background: var(--brand);
      color: #fff; border: none; border-radius: .75rem;
      cursor: pointer; font-family: inherit;
      transition: background .15s, transform .1s;
      display: flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .btn-login:hover  { background: #0f3f22; }
    .btn-login:active { transform: scale(.98); }
    .footer-note {
      margin-top: 1.5rem; text-align: center;
      font-size: .8rem; color: var(--text-2);
      border-top: 1px solid var(--border); padding-top: 1.1rem;
    }
    .footer-note button {
      background: none; border: none; cursor: pointer; font-family: inherit;
      color: var(--brand); font-weight: 700; font-size: .8rem;
    }
    .footer-note button:hover { text-decoration: underline; }
    @media (max-width: 460px) {
      .card { margin: 1rem; padding: 1.75rem 1.25rem; }
    }
  </style>
</head>
<body>
  <div class="blob1"></div>
  <div class="blob2"></div>

  <div class="card">
    <a href="{{ route('landing') }}" class="logo-wrap">
      <img src="{{ asset('images/logo.png') }}" alt="Riwash Money">
      <div class="logo-text">Riwash Money</div>
    </a>

    <div class="icon-circle">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
    </div>

    <h1>Verify your email</h1>
    <p class="subtitle">
      Thanks for signing up! Before getting started, click the link we emailed you to verify your address.
      Didn't get it? We'll gladly send another.
    </p>

    @if (session('status') == 'verification-link-sent')
    <div class="status-box">A new verification link has been sent to the email address you provided.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn-login">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Resend verification email
      </button>
    </form>

    <div class="footer-note">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
      </form>
    </div>
  </div>
</body>
</html>
