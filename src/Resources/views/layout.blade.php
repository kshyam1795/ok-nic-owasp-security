<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OWASP Security') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #0f1419;
            --panel: #1a2332;
            --panel-2: #243044;
            --text: #e8eef7;
            --muted: #8b9bb4;
            --accent: #3d9cf0;
            --accent-2: #2dd4a8;
            --danger: #f07178;
            --warn: #e6b450;
            --border: #2e3a4f;
            --ok: #7fd962;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "IBM Plex Sans", system-ui, sans-serif;
            background: radial-gradient(1200px 600px at 10% -10%, #1b3a5a 0%, transparent 50%),
                        radial-gradient(900px 500px at 100% 0%, #16352f 0%, transparent 45%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        a { color: var(--accent); text-decoration: none; }
        .shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        nav {
            background: rgba(26, 35, 50, 0.92);
            border-right: 1px solid var(--border);
            padding: 1.5rem 1rem;
            position: sticky; top: 0; height: 100vh;
        }
        .brand {
            font-size: 1.05rem; font-weight: 700; letter-spacing: 0.02em;
            margin-bottom: 1.75rem; padding: 0 0.5rem;
        }
        .brand span { color: var(--accent-2); }
        nav a {
            display: block; color: var(--muted); padding: 0.65rem 0.75rem;
            border-radius: 8px; margin-bottom: 0.25rem;
        }
        nav a:hover, nav a.active { background: var(--panel-2); color: var(--text); }
        main { padding: 1.75rem 2rem 3rem; }
        h1 { margin: 0 0 0.35rem; font-size: 1.65rem; }
        .sub { color: var(--muted); margin-bottom: 1.5rem; }
        .flash {
            background: rgba(45, 212, 168, 0.12); border: 1px solid rgba(45, 212, 168, 0.35);
            color: var(--accent-2); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
        }
        .errors {
            background: rgba(240, 113, 120, 0.12); border: 1px solid rgba(240, 113, 120, 0.35);
            color: var(--danger); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
        }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .card {
            background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.1rem;
        }
        .card .n { font-size: 1.75rem; font-weight: 700; }
        .card .l { color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem; }
        .panel {
            background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        th, td { text-align: left; padding: 0.7rem 0.55rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .badge {
            display: inline-block; font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 999px;
            background: var(--panel-2); color: var(--muted);
        }
        .badge.on { background: rgba(127, 217, 98, 0.15); color: var(--ok); }
        .badge.off { background: rgba(240, 113, 120, 0.15); color: var(--danger); }
        .tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .tabs a {
            padding: 0.45rem 0.85rem; border-radius: 999px; border: 1px solid var(--border);
            color: var(--muted); background: transparent;
        }
        .tabs a.active { background: var(--accent); color: #041018; border-color: var(--accent); font-weight: 600; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0; background: #3a4558; border-radius: 24px; transition: .2s;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: .2s;
        }
        input:checked + .slider { background: var(--accent-2); }
        input:checked + .slider:before { transform: translateX(20px); }
        .btn {
            appearance: none; border: 0; border-radius: 8px; padding: 0.55rem 1rem;
            background: var(--accent); color: #041018; font-weight: 600; cursor: pointer;
        }
        .btn.secondary { background: var(--panel-2); color: var(--text); border: 1px solid var(--border); }
        .btn.danger { background: var(--danger); color: #1a0506; }
        .row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
        input[type=text], input[type=email], input[type=password], select, textarea {
            background: var(--panel-2); border: 1px solid var(--border); color: var(--text);
            border-radius: 8px; padding: 0.55rem 0.75rem; width: 100%; max-width: 420px;
        }
        .muted { color: var(--muted); font-size: 0.85rem; }
        .value-input { font-family: ui-monospace, Consolas, monospace; font-size: 0.8rem; max-width: 100%; }
        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            nav { position: relative; height: auto; }
        }
    </style>
</head>
<body>
<div class="shell">
    <nav>
        <div class="brand">OWASP <span>Security</span></div>
        <a href="{{ route('owasp.dashboard') }}" class="{{ request()->routeIs('owasp.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('owasp.settings') }}" class="{{ request()->routeIs('owasp.settings*') ? 'active' : '' }}">Settings</a>
        <a href="{{ route('owasp.settings', ['group' => 'headers']) }}">↳ Headers</a>
        <a href="{{ route('owasp.settings', ['group' => 'authentication']) }}">↳ Authentication</a>
        <a href="{{ route('owasp.settings', ['group' => 'protections']) }}">↳ Protections</a>
        <a href="{{ route('owasp.roles') }}" class="{{ request()->routeIs('owasp.roles*') ? 'active' : '' }}">Roles & Users</a>
        <a href="{{ route('owasp.audit') }}" class="{{ request()->routeIs('owasp.audit') ? 'active' : '' }}">Audit Logs</a>
        <a href="{{ route('owasp.otp.challenge') }}">OTP Challenge</a>
    </nav>
    <main>
        @if(session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="errors">
                <ul style="margin:0;padding-left:1.1rem">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
