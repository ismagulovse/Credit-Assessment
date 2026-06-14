<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Журнал') · Журнал</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Public+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Палитра по референсу 3: нейтральный серый фон, белые панели,
               чёрный как основной «акцент действия», синий — вторичный. */
            --bg: #ececec;
            --panel: #ffffff;
            --ink: #1a1a1a;
            --muted: #71717a;
            --muted-light: #a1a1aa;
            --line: #e7e7e9;
            --line-soft: #f1f1f2;
            --sidebar: #fafafa;
            --hover: #f4f4f5;
            --accent: #2563eb;        /* синий — вторичный акцент */
            --accent-soft: #eef2ff;
            --gold: #d97706;
            --green: #16a34a;
            --red: #dc2626;
            --black: #18181b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Public Sans', system-ui, sans-serif;
            color: var(--ink); background: var(--bg);
            display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        h1, h2, h3, .display { font-family: 'Bricolage Grotesque', sans-serif; letter-spacing: -.02em; }

        /* ===== Sidebar (белый, реф 3) ===== */
        .sidebar {
            width: 258px; flex-shrink: 0; background: var(--sidebar);
            border-right: 1px solid var(--line); display: flex; flex-direction: column;
            padding: 16px 14px; position: sticky; top: 0; height: 100vh;
        }
        .sidebar .logo {
            display: flex; align-items: center; gap: 11px; padding: 8px 10px 14px; margin-bottom: 6px;
        }
        .sidebar .logo .mark {
            width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center;
            font-size: 1.15rem; background: var(--black); color: #fff;
        }
        .sidebar .logo .name { font-family: 'Bricolage Grotesque'; font-weight: 800; font-size: 1.1rem; }
        .sidebar .logo .name small { display:block; font-family:'Public Sans'; font-weight:500; font-size:.64rem; letter-spacing:.14em; text-transform:uppercase; color:var(--muted-light); }

        .nav-group { margin-top: 16px; }
        .nav-group .title { font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; color: var(--muted-light); padding: 6px 12px; font-weight: 700; }
        .nav-link {
            display: flex; align-items: center; gap: 12px; padding: 9px 12px; border-radius: 10px;
            margin: 2px 0; color: var(--muted); font-weight: 500; font-size: .92rem; transition: .14s;
        }
        .nav-link .ico { width: 20px; text-align: center; font-size: 1.05rem; }
        .nav-link:hover { background: var(--hover); color: var(--ink); }
        .nav-link.active { background: var(--panel); color: var(--ink); font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 0 0 1px var(--line); }
        .sidebar .spacer { flex: 1; }
        .sidebar .foot { font-size: .72rem; color: var(--muted-light); padding: 12px; border-top: 1px solid var(--line); letter-spacing: .03em; }

        /* ===== Main ===== */
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            height: 64px; background: rgba(255,255,255,.8); backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 14px;
            padding: 0 30px; position: sticky; top: 0; z-index: 5;
        }
        .crumbs { display: flex; align-items: center; gap: 9px; color: var(--muted); font-size: .9rem; }
        .crumbs a:hover { color: var(--ink); }
        .crumbs .sep { color: var(--muted-light); }
        .crumbs .current { color: var(--ink); font-weight: 600; }
        .topbar .right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
        .avatar { width: 34px; height: 34px; border-radius: 10px; color: #fff; font-weight: 700; font-size: .85rem; display: grid; place-items: center; background: var(--black); }

        .content { padding: 32px 30px; max-width: 1180px; width: 100%; }
        h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; }
        .sub { color: var(--muted); margin-bottom: 26px; font-size: 1rem; max-width: 62ch; }

        /* ===== Components ===== */
        .btn {
            cursor: pointer; padding: 10px 18px; border: none; border-radius: 10px; font-weight: 600;
            font-size: .89rem; font-family: inherit; transition: transform .12s, box-shadow .15s, filter .15s, background .15s;
            display: inline-flex; align-items: center; gap: 8px; color: #fff; background: var(--black);
        }
        .btn:hover { box-shadow: 0 6px 16px rgba(0,0,0,.16); transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-blue { background: var(--accent); }
        .btn-green { background: var(--green); }
        .btn-gold { background: var(--gold); }
        .btn-ghost { background: var(--panel); border: 1px solid var(--line); color: var(--ink); }
        .btn-ghost:hover { border-color: var(--ink); box-shadow: none; }
        .btn-danger { background: var(--panel); border: 1px solid #f1c9c9; color: var(--red); padding: 7px 12px; font-size: .82rem; }
        .btn-danger:hover { background: #fdeded; box-shadow: none; transform: none; }
        .btn-sm { padding: 8px 14px; font-size: .83rem; }

        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 22px; }

        .grid { display: grid; gap: 16px; }
        .grid-tiles { grid-template-columns: repeat(auto-fill, minmax(264px, 1fr)); }

        input, select, textarea {
            width: 100%; padding: 11px 14px; border-radius: 10px; border: 1px solid var(--line);
            background: #fff; color: var(--ink); font-size: .92rem; font-family: inherit;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        label { display: block; color: var(--muted); font-size: .85rem; margin-bottom: 6px; margin-top: 13px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 13px 15px; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 700; font-size: .76rem; text-transform: uppercase; letter-spacing: .05em; background: var(--line-soft); }
        tbody tr { transition: background .12s; }
        tbody tr:hover { background: var(--line-soft); }

        .err { color: var(--red); font-size: .85rem; margin-top: 6px; }
        .flash {
            background: #effdf4; color: var(--green); padding: 13px 16px; border-radius: 12px;
            margin-bottom: 22px; border: 1px solid #c7eed5; font-weight: 600;
            display: flex; align-items: center; gap: 9px; animation: pop .4s ease;
        }
        .muted { color: var(--muted); }

        /* ===== Motion ===== */
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes pop  { from { opacity: 0; transform: scale(.97); } to { opacity: 1; transform: none; } }
        .reveal > * { opacity: 0; animation: rise .5s cubic-bezier(.2,.7,.2,1) forwards; }
        .reveal > *:nth-child(1){animation-delay:.04s} .reveal > *:nth-child(2){animation-delay:.1s}
        .reveal > *:nth-child(3){animation-delay:.16s} .reveal > *:nth-child(4){animation-delay:.22s}
        .reveal > *:nth-child(5){animation-delay:.28s} .reveal > *:nth-child(6){animation-delay:.34s}
        .reveal > *:nth-child(7){animation-delay:.4s}  .reveal > *:nth-child(8){animation-delay:.46s}
        @media (prefers-reduced-motion: reduce) { .reveal > * { animation: none; opacity: 1; } }
    </style>
    @stack('head')
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <span class="mark">🎓</span>
            <span class="name">Журнал<small>Credit Assessment</small></span>
        </div>

        <div class="nav-group">
            <div class="title">Главное</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="ico">🏠</span> Дашборд
            </a>
            <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                <span class="ico">🏆</span> Кто на автомат
            </a>
        </div>

        <div class="nav-group">
            <div class="title">Управление</div>
            <a class="nav-link {{ request()->routeIs('groups.*') ? 'active' : '' }}" href="{{ route('groups.index') }}">
                <span class="ico">👥</span> Группы
            </a>
        </div>

        <div class="spacer"></div>
        <div class="foot">© {{ date('Y') }} · v0.2</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <nav class="crumbs">
                <a href="{{ route('dashboard') }}">🏠</a>
                @hasSection('crumbs')
                    <span class="sep">›</span>
                    @yield('crumbs')
                @endif
            </nav>
            <div class="right">
                <span class="avatar">П</span>
            </div>
        </header>

        <div class="content">
            @if(session('status'))
                <div class="flash">✓ {{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
