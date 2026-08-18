<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirm password — Riwash Money</title>
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
      max-width: 420px;
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
    h1 {
      font-size: 1.375rem; font-weight: 800; color: var(--text);
      margin-bottom: .375rem; text-align: center;
    }
    .subtitle { font-size: .85rem; color: var(--text-2); text-align: center; margin-bottom: 1.75rem; line-height: 1.55; }
    .field { margin-bottom: 1.25rem; }
    label {
      display: block; font-size: .8rem; font-weight: 600;
      color: var(--text); margin-bottom: .35rem;
    }
    input[type=password] {
      width: 100%; padding: .65rem .875rem;
      font-size: .9rem; border: 1.5px solid var(--border);
      border-radius: .625rem; outline: none; background: var(--bg);
      color: var(--text); transition: border-color .2s, background .2s;
      font-family: inherit;
    }
    input[type=password]:focus {
      border-color: var(--accent); background: #fff;
      box-shadow: 0 0 0 3px rgba(22,163,74,.15);
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
    .error-box {
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: .625rem; padding: .65rem .875rem;
      font-size: .8rem; color: #dc2626;
      margin-bottom: 1.125rem;
    }
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

    <h1>Confirm your password</h1>
    <p class="subtitle">This is a secure area — please confirm your password before continuing.</p>

    @if ($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
      @csrf

      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password"
               placeholder="••••••••"
               required autofocus autocomplete="current-password">
      </div>

      <button type="submit" class="btn-login">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        Confirm
      </button>
    </form>
  </div>
</body>
</html>
