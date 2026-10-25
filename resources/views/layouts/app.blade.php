<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pulso')</title>
    @stack('head')
    <style>
        :root {
            --bg: #f5f6f8; --card: #ffffff; --text: #1c2230; --muted: #6a7488;
            --line: #e2e6ed; --up: #1a9d57; --down: #d83a3a; --unknown: #8892a6;
            --paused: #c98a10; --accent: #3355ff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #11141b; --card: #1a1f29; --text: #e6e9f0; --muted: #96a0b4;
                --line: #2a3140; --accent: #6b83ff;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text);
               font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .topbar { background: var(--card); border-bottom: 1px solid var(--line); }
        .topbar .inner, main { max-width: 1000px; margin: 0 auto; padding: 14px 20px; }
        .brand { font-weight: 700; font-size: 18px; color: var(--text); }
        main { padding-top: 24px; padding-bottom: 60px; }
        .head { display: flex; justify-content: space-between; align-items: center;
                gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
        h1 { font-size: 22px; margin: 0; }
        h2 { font-size: 16px; margin: 28px 0 10px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
        .summary, .stats { display: grid; gap: 12px; margin-bottom: 20px;
                           grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
        .summary .card, .stats .card { padding: 14px 16px; }
        .summary .num, .stats .num { font-size: 26px; font-weight: 700; }
        .muted { color: var(--muted); font-size: 13px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--line);
                 vertical-align: middle; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px;
                 font-size: 12px; font-weight: 600; color: #fff; }
        .badge.up { background: var(--up); } .badge.down { background: var(--down); }
        .badge.unknown { background: var(--unknown); } .badge.paused { background: var(--paused); }
        .btn { display: inline-block; padding: 7px 14px; border-radius: 8px; border: 1px solid var(--line);
               background: var(--card); color: var(--text); font: inherit; cursor: pointer; }
        .btn:hover { text-decoration: none; border-color: var(--accent); }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn.danger { color: var(--down); }
        .btn.small { padding: 3px 10px; font-size: 13px; }
        .actions form { display: inline; }
        .flash { background: #e6f6ee; border: 1px solid #b6e2c9; color: #12663a;
                 padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .alert-down { background: #fdecec; border: 1px solid #f3bcbc; color: #8e2020;
                      padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .empty { padding: 40px; text-align: center; color: var(--muted); }
        form.monitor-form { padding: 20px; max-width: 640px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .field input, .field select { width: 100%; padding: 8px 10px; border-radius: 8px;
                border: 1px solid var(--line); background: var(--bg); color: var(--text); font: inherit; }
        .field .hint { color: var(--muted); font-size: 12px; margin-top: 3px; }
        .field .error { color: var(--down); font-size: 13px; display: block; margin-top: 3px; }
        .row { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
        .spark { width: 100%; height: 80px; color: var(--accent); display: block; }
        .chart { padding: 14px 16px; }
        .ok { color: var(--up); } .fail { color: var(--down); }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="inner"><a class="brand" href="{{ route('monitors.index') }}">◉ Pulso</a></div>
    </div>
    <main>
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>