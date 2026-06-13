@php
    $statusCode = $code ?? '500';
    $statusTitle = $title ?? 'Terjadi Kesalahan';
    $statusMessage = $message ?? 'Permintaan tidak dapat diproses saat ini.';
    $tone = $tone ?? 'red';
    $toneColor = [
        'red' => '#c81924',
        'amber' => '#d97706',
        'blue' => '#001f45',
    ][$tone] ?? '#c81924';

    $homeUrl = route('login');
    if (auth()->check()) {
        $dashboardRoute = 'dashboard.' . auth()->user()->role;
        $homeUrl = \Illuminate\Support\Facades\Route::has($dashboardRoute)
            ? route($dashboardRoute)
            : url('/');
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAP - {{ $statusCode }} {{ $statusTitle }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-kpu.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface: #f8f9fa;
            --surface-low: #eef1f4;
            --card: #ffffff;
            --ink: #17202a;
            --muted: #657181;
            --line: #d9dee5;
            --primary: #001f45;
            --accent: {{ $toneColor }};
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 2px 2px, rgba(0, 31, 69, 0.08) 1px, transparent 0),
                var(--surface-low);
            background-size: 24px 24px;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .topbar {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 0 24px;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid var(--line);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .logo {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            display: grid;
            place-items: center;
            overflow: hidden;
            flex: 0 0 auto;
        }

        .logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .eyebrow {
            margin: 0;
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .brand-title {
            margin: 2px 0 0;
            font-size: 16px;
            line-height: 1.15;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-pill {
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, white);
            border: 1px solid color-mix(in srgb, var(--accent) 18%, white);
            border-radius: 999px;
            padding: 8px 12px;
        }

        .content {
            display: grid;
            place-items: center;
            padding: 56px 20px;
        }

        .panel {
            width: min(100%, 760px);
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.16);
            border-radius: 16px;
            overflow: hidden;
        }

        .panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 28px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .code {
            margin: 0;
            color: var(--accent);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: clamp(48px, 10vw, 92px);
            line-height: 0.95;
            font-weight: 700;
            letter-spacing: 0;
        }

        .title {
            margin: 10px 0 0;
            color: var(--primary);
            font-size: clamp(24px, 5vw, 38px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 0;
        }

        .message {
            margin: 14px 0 0;
            max-width: 560px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            font-weight: 500;
        }

        .icon {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, white);
            border: 1px solid color-mix(in srgb, var(--accent) 18%, white);
            flex: 0 0 auto;
        }

        .icon svg {
            width: 30px;
            height: 30px;
        }

        .panel-body {
            padding: 24px 28px 28px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 12px 28px rgba(0, 31, 69, 0.18);
        }

        .btn-danger {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 12px 28px color-mix(in srgb, var(--accent) 24%, transparent);
        }

        .btn-ghost {
            background: #fff;
            color: #475569;
            border-color: var(--line);
        }

        .debug {
            margin-top: 18px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            overflow: hidden;
            background: #fff7f7;
            text-align: left;
        }

        .debug-title {
            margin: 0;
            padding: 10px 12px;
            border-bottom: 1px solid #fecaca;
            color: #991b1b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .debug-body {
            margin: 0;
            padding: 12px;
            color: #7f1d1d;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .foot {
            padding: 16px 24px;
            color: #94a3b8;
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .topbar {
                height: auto;
                padding: 14px 16px;
            }

            .status-pill {
                display: none;
            }

            .panel-head {
                padding: 22px;
            }

            .icon {
                display: none;
            }

            .panel-body {
                padding: 20px 22px 22px;
            }

            .actions {
                display: grid;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div class="brand">
                <div class="logo">
                    <img src="{{ asset('images/logo-kpu.png') }}" alt="SIMAP Logo">
                </div>
                <div>
                    <p class="eyebrow">Sistem Informasi</p>
                    <p class="brand-title">SIMAP Kabupaten Banyuwangi</p>
                </div>
            </div>
            <span class="status-pill">HTTP {{ $statusCode }}</span>
        </header>

        <main class="content">
            <section class="panel" aria-labelledby="error-title">
                <div class="panel-head">
                    <div>
                        <p class="code">{{ $statusCode }}</p>
                        <h1 id="error-title" class="title">{{ $statusTitle }}</h1>
                        <p class="message">{{ $statusMessage }}</p>
                    </div>
                    <div class="icon" aria-hidden="true">
                        @yield('icon')
                    </div>
                </div>

                <div class="panel-body">
                    <div class="actions">
                        <button type="button" class="btn btn-danger" onclick="window.location.reload()">Muat Ulang</button>
                        <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : window.location.assign('{{ $homeUrl }}')">Kembali</button>
                        <a class="btn btn-primary" href="{{ $homeUrl }}">{{ auth()->check() ? 'Ke Dashboard' : 'Login' }}</a>
                    </div>

                    @if(!empty($exception) && config('app.debug') && $exception->getMessage())
                        <div class="debug">
                            <p class="debug-title">Debug Message</p>
                            <pre class="debug-body">{{ $exception->getMessage() }}</pre>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <footer class="foot">
            SIMAP &copy; {{ date('Y') }} - KPU Banyuwangi
        </footer>
    </div>
</body>
</html>
