<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — NEPSE Analytics</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #312e81 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', system-ui, sans-serif;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
      background-size: 28px 28px;
      pointer-events: none;
    }
    .blob1 {
      position: absolute; top: -100px; right: -100px;
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(99,102,241,.3), transparent 70%);
      pointer-events: none;
    }
    .blob2 {
      position: absolute; bottom: -80px; left: -80px;
      width: 320px; height: 320px; border-radius: 50%;
      background: radial-gradient(circle, rgba(59,130,246,.2), transparent 70%);
      pointer-events: none;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card {
      position: relative; z-index: 1;
      background: rgba(255, 255, 255, 0.97);
      border-radius: 1.25rem;
      padding: 2.25rem 2rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      animation: fadeUp .4s ease both;
    }
    .logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .75rem;
      margin-bottom: 1.75rem;
    }
    .logo-icon {
      width: 44px; height: 44px;
      border-radius: .75rem;
      background: linear-gradient(135deg, #1e3a8a, #7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.35rem;
    }
    .logo-text { font-size: 1.3rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; }
    .logo-text span { color: #2563eb; }
    h1 {
      font-size: 1.375rem; font-weight: 800; color: #0f172a;
      margin-bottom: .375rem; text-align: center;
    }
    .subtitle { font-size: .82rem; color: #94a3b8; text-align: center; margin-bottom: 1.75rem; }
    .field { margin-bottom: 1.125rem; }
    label {
      display: block; font-size: .78rem; font-weight: 600;
      color: #374151; margin-bottom: .35rem;
    }
    input[type=email], input[type=password] {
      width: 100%; padding: .65rem .875rem;
      font-size: .9rem; border: 1.5px solid #e2e8f0;
      border-radius: .625rem; outline: none; background: #f8fafc;
      color: #0f172a; transition: border-color .2s, background .2s;
      font-family: inherit;
    }
    input[type=email]:focus, input[type=password]:focus {
      border-color: #818cf8; background: #fff;
      box-shadow: 0 0 0 3px rgba(129,140,248,.15);
    }
    .remember-row {
      display: flex; align-items: center; gap: .5rem;
      margin-bottom: 1.375rem;
    }
    .remember-row input { width: 15px; height: 15px; accent-color: #2563eb; cursor: pointer; }
    .remember-row label { font-size: .78rem; color: #64748b; cursor: pointer; margin: 0; }
    .btn-login {
      width: 100%; padding: .75rem;
      font-size: .9375rem; font-weight: 700;
      background: linear-gradient(135deg, #1e40af, #7c3aed);
      color: #fff; border: none; border-radius: .75rem;
      cursor: pointer; font-family: inherit;
      transition: opacity .15s, transform .1s;
      display: flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .btn-login:hover  { opacity: .9; }
    .btn-login:active { transform: scale(.98); }
    .error-box {
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: .625rem; padding: .65rem .875rem;
      font-size: .78rem; color: #dc2626;
      margin-bottom: 1.125rem;
    }
    .status-box {
      background: #f0fdf4; border: 1px solid #bbf7d0;
      border-radius: .625rem; padding: .65rem .875rem;
      font-size: .78rem; color: #16a34a;
      margin-bottom: 1.125rem;
    }
    .footer-note {
      margin-top: 1.5rem; text-align: center;
      font-size: .72rem; color: #94a3b8;
      border-top: 1px solid #f1f5f9; padding-top: 1rem;
    }
    .footer-note strong { color: #374151; }
    @media (max-width: 460px) {
      .card { margin: 1rem; padding: 1.75rem 1.25rem; }
    }
  </style>
</head>
<body>
  <div class="blob1"></div>
  <div class="blob2"></div>

  <div class="card">
    {{-- Logo --}}
    <div class="logo-wrap">
      <div class="logo-icon">📈</div>
      <div class="logo-text">NEPSE<span>Analytics</span></div>
    </div>

    <h1>Welcome back</h1>
    <p class="subtitle">Sign in to access your analytics dashboard</p>

    {{-- Status message (e.g. password reset) --}}
    @if (session('status'))
    <div class="status-box">{{ session('status') }}</div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
    <div class="error-box">
      {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <div class="field">
        <label for="email">Email address</label>
        <input id="email" type="email" name="email"
               value="{{ old('email') }}"
               placeholder="Enter your email"
               required autofocus autocomplete="username">
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password"
               placeholder="••••••••"
               required autocomplete="current-password">
      </div>

      <div class="remember-row">
        <input id="remember_me" type="checkbox" name="remember">
        <label for="remember_me">Keep me signed in</label>
      </div>

      <button type="submit" class="btn-login">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
        </svg>
        Sign In
      </button>
    </form>

    <div class="footer-note">
      <strong>NEPSE Analytics</strong> &mdash; Real-time Nepal stock analysis &amp; signals
    </div>
  </div>
</body>
</html>
