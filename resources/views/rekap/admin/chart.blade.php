@php
    $party = $party ?? config('party');
    $roleLabel = $party['roles']['admin_partai'] ?? 'Admin Partai';
    $homeRoute = route('dashboard.admin_partai');
    $adminMenus = [
        ['key' => 'dashboard', 'label' => 'Beranda', 'icon' => 'dashboard', 'route' => route('dashboard.admin_partai')],
        ['key' => 'users', 'label' => 'Pengguna', 'icon' => 'group', 'route' => route('admin.users.index')],
        ['key' => 'chart', 'label' => 'Grafik & Statistik', 'icon' => 'bar_chart', 'route' => route('admin.rekap.chart')],
        ['key' => 'kecamatan', 'label' => 'Kelola Kecamatan', 'icon' => 'map', 'route' => route('admin.kecamatan.index')],
        ['key' => 'desa', 'label' => 'Kelola Desa', 'icon' => 'location_city', 'route' => route('admin.desa.index')],
        ['key' => 'tps', 'label' => 'Kelola TPS', 'icon' => 'pin_drop', 'route' => route('admin.tps.index')],
        ['key' => 'rekap', 'label' => 'Rekapitulasi Data', 'icon' => 'analytics', 'route' => route('admin.rekap.index')],
        ['key' => 'setup', 'label' => 'Setup Data ' . $party['short_name'], 'icon' => 'settings', 'route' => route('admin.setup.index')],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $party['app_name'] }} - Grafik & Statistik</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/png" href="{{ asset($party['assets']['logo']) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

    <style>
        :root {
            --surface: #f8f9fa;
            --surface-low: #eef1f4;
            --surface-card: #ffffff;
            --surface-soft: #f3f5f7;
            --ink: #17202a;
            --muted: #657181;
            --line: #d9dee5;
            --primary: #001f45;
            --primary-2: #2d476f;
            --red: #c81924;
            --red-soft: #ffe1df;
            --blue-soft: #dbe8ff;
            --map-dot: rgba(0, 31, 69, 0.08);
        }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            overflow: hidden;
            background: var(--surface);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .font-mono-data {
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .material-symbols-outlined {
            font-variation-settings: "FILL" 0, "wght" 500, "GRAD" 0, "opsz" 24;
            line-height: 1;
        }

        .admin-nav-sidebar {
            background: rgba(255, 255, 255, 0.94);
            border-color: var(--line);
        }

        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 24px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .admin-nav-item:hover {
            background: var(--surface-soft);
            color: var(--red);
        }

        .admin-nav-item.active {
            background: var(--red-soft);
            color: var(--red);
            border-right: 4px solid var(--red);
        }

        .admin-mobile-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            background: rgba(15, 23, 42, 0.46);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .admin-mobile-drawer {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 90;
            width: min(82vw, 20rem);
            transform: translateX(-100%);
            transition: transform 0.25s ease;
        }

        #admin-mobile-menu:checked ~ .admin-mobile-overlay {
            opacity: 1;
            pointer-events: auto;
        }

        #admin-mobile-menu:checked ~ .admin-mobile-drawer {
            transform: translateX(0);
        }

        @media (min-width: 768px) {
            .admin-mobile-overlay,
            .admin-mobile-drawer {
                display: none;
            }
        }

        .map-grid {
            background-color: var(--surface-low);
            background-image: radial-gradient(circle at 2px 2px, var(--map-dot) 1px, transparent 0);
            background-size: 24px 24px;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .leaflet-tooltip-kec {
            background: rgba(0, 31, 69, 0.94);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.24);
            font-size: 12px;
            font-weight: 500;
            padding: 0;
        }

        .map-tooltip {
            min-width: 230px;
            max-width: 300px;
            padding: 10px 12px;
        }

        .map-tooltip-title {
            display: block;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .map-tooltip-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: rgba(255, 255, 255, 0.82);
            font-size: 11px;
            line-height: 1.45;
        }

        .map-tooltip-row span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .map-tooltip-row b {
            color: #ffffff;
            font-weight: 800;
            white-space: nowrap;
            text-align: right;
        }

        .leaflet-container {
            background: transparent !important;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }

        .leaflet-control-zoom {
            border: 1px solid var(--line) !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16) !important;
        }

        .leaflet-control-zoom a {
            color: var(--primary) !important;
        }

        select, input, button {
            font-family: inherit;
        }

        .jenis-btn {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
        }

        .jenis-btn:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            color: var(--primary);
        }

        .jenis-btn.is-active {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 12px 28px rgba(0, 31, 69, 0.18);
        }

        .detail-table-scroll {
            max-height: 286px;
            overflow-y: auto;
        }

        .detail-table-scroll thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .candidate-rank-scroll {
            max-height: 350px;
            overflow-y: auto;
        }

        main.chart-shell {
            display: grid;
            grid-template-columns: 16rem minmax(17rem, 20.625rem) minmax(0, 1fr) minmax(20rem, 25.625rem);
            overflow: hidden;
        }

        .chart-nav,
        .chart-filter,
        .chart-main,
        .chart-summary {
            min-height: 0;
        }

        .chart-filter,
        .chart-summary {
            width: auto !important;
            height: 100%;
            overflow-y: auto;
        }

        .chart-main {
            min-width: 0;
            height: 100%;
            overflow-y: auto;
            padding: 1rem;
        }

        .map-panel {
            height: min(660px, calc(100vh - 9rem));
            min-height: 500px;
        }

        .kpi-card {
            min-width: 0;
            padding: 0.75rem;
        }

        .kpi-grid {
            left: 1rem;
            right: 1rem;
            top: 1rem;
            gap: 0.5rem;
        }

        .kpi-card p:nth-child(2) {
            font-size: 1.125rem;
            line-height: 1.5rem;
            margin-top: 0.25rem;
        }

        .kpi-card p:first-child,
        .map-info p:first-child,
        #map-legend p:first-child {
            font-size: 0.5625rem;
            letter-spacing: 0.12em;
        }

        .kpi-card p:last-child {
            font-size: 0.6875rem;
            line-height: 1rem;
            margin-top: 0.125rem;
        }

        .map-info {
            left: 1rem;
            top: 6.25rem;
            max-width: 17rem;
            padding: 0.625rem 0.75rem;
        }

        .map-info p.text-sm {
            font-size: 0.75rem;
            line-height: 1rem;
        }

        #map-legend {
            left: 1rem;
            bottom: 1rem;
            min-width: 12.5rem;
            padding: 0.75rem;
        }

        #map-legend .space-y-2 {
            display: grid;
            gap: 0.375rem;
        }

        #map-legend .space-y-2 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0;
        }

        #map-legend .flex {
            gap: 0.5rem;
        }

        #map-legend span.w-4 {
            width: 0.75rem;
            height: 0.75rem;
        }

        #map-legend span.text-xs {
            font-size: 0.6875rem;
            line-height: 0.875rem;
        }

        @media (min-width: 1536px) {
            .chart-main {
                padding: 1.25rem;
            }

            .map-panel {
                height: min(700px, calc(100vh - 9.5rem));
                min-height: 540px;
            }
        }

        @media (max-width: 1535px) {
            body {
                overflow: auto;
            }

            main.chart-shell {
                height: auto;
                min-height: 100vh;
                grid-template-columns: 16rem minmax(17rem, 19rem) minmax(0, 1fr);
                overflow: visible;
            }

            .chart-nav,
            .chart-filter {
                height: calc(100vh - 4rem);
                position: sticky;
                top: 4rem;
            }

            .chart-main {
                height: auto;
                overflow: visible;
                padding: 1rem;
            }

            .chart-summary {
                grid-column: 2 / -1;
                height: auto;
                border-left: 0;
                border-top: 1px solid var(--line);
            }

            .map-panel {
                height: 560px;
                min-height: 500px;
            }
        }

        @media (max-width: 1179px) {
            main.chart-shell {
                grid-template-columns: 16rem minmax(0, 1fr);
            }

            .chart-nav {
                grid-row: 1 / span 3;
            }

            .chart-filter,
            .chart-main,
            .chart-summary {
                grid-column: 2;
            }

            .chart-filter {
                height: auto;
                position: static;
                border-right: 0;
                border-bottom: 1px solid var(--line);
                padding: 1rem;
            }

            .chart-summary {
                height: auto;
                padding: 1rem;
            }

            .map-panel {
                height: 540px;
                min-height: 480px;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .map-info {
                top: 9.5rem;
            }
        }

        @media (max-width: 767px) {
            body {
                overflow: auto;
            }

            header {
                height: auto;
                min-height: 4rem;
            }

            header .h-full {
                padding: 0.75rem 1rem;
            }

            main.chart-shell {
                display: block;
                padding-top: 5rem;
            }

            .chart-filter,
            .chart-summary {
                height: auto;
                width: auto !important;
                border-left: 0;
                border-right: 0;
                padding: 1rem;
            }

            .chart-main {
                height: auto;
                overflow: visible;
                padding: 0.75rem;
            }

            .map-panel {
                height: auto;
                min-height: 0;
                overflow: visible;
                padding: 0;
            }

            #map {
                position: relative !important;
                inset: auto !important;
                height: 430px;
                min-height: 360px;
            }

            .kpi-grid,
            .map-info,
            #map-legend {
                position: relative;
                left: auto;
                right: auto;
                top: auto;
                bottom: auto;
                z-index: 1;
                margin: 0.75rem;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .kpi-card {
                padding: 0.625rem 0.75rem;
            }

            .kpi-card p:nth-child(2) {
                font-size: 1rem;
                line-height: 1.25rem;
                margin-top: 0.25rem;
            }

            .map-info {
                max-width: none;
            }

            #map-legend {
                min-width: 0;
                max-height: 190px;
                overflow-y: auto;
            }

            .detail-table-scroll {
                max-height: 310px;
            }
        }

        @media (max-width: 420px) {
            #map {
                height: 380px;
            }
        }
    </style>
</head>
<body>
@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $defaultJenis = collect(\App\Models\RekapHeader::JENIS_LABELS)
        ->keys()
        ->first(fn ($key) => in_array($key, $aktifJenis));
@endphp

<input id="admin-mobile-menu" type="checkbox" class="hidden">
<label for="admin-mobile-menu" class="admin-mobile-overlay"></label>
<aside class="admin-mobile-drawer admin-nav-sidebar flex flex-col border-r">
    <div class="p-5 flex items-center justify-between border-b border-slate-200">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center overflow-hidden">
                <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }} Logo" class="w-8 h-8 object-contain">
            </div>
            <div>
                <p class="text-lg font-extrabold text-[var(--primary)] leading-none">{{ $party['app_name'] }}</p>
                <p class="font-mono-data text-[10px] uppercase tracking-widest text-slate-500 mt-1">{{ $roleLabel }}</p>
            </div>
        </div>
        <label for="admin-mobile-menu" class="cursor-pointer p-2 text-slate-500 hover:text-red-600">
            <span class="material-symbols-outlined">close</span>
        </label>
    </div>
    <nav class="flex-1 py-4 overflow-y-auto">
        @foreach($adminMenus as $menu)
            <a class="admin-nav-item {{ $menu['key'] === 'chart' ? 'active' : '' }}" href="{{ $menu['route'] }}">
                <span class="material-symbols-outlined">{{ $menu['icon'] }}</span>
                <span>{{ $menu['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>

<header class="fixed top-0 left-0 right-0 z-50 h-16 bg-white border-b border-slate-200 shadow-sm">
    <div class="h-full px-6 flex items-center justify-between gap-6">
        <div class="flex items-center gap-4 min-w-0">
            <label for="admin-mobile-menu" class="md:hidden cursor-pointer -ml-2 p-2 text-slate-500 hover:text-red-600">
                <span class="material-symbols-outlined text-3xl">menu</span>
            </label>
            <a href="{{ $homeRoute }}" class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }}" class="w-8 h-8 object-contain">
            </a>
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500 font-semibold">Sistem Informasi</p>
                <h1 class="text-lg font-extrabold text-[var(--primary)] truncate">{{ $party['app_name'] }}</h1>
            </div>
        </div>

        <div class="hidden lg:flex items-center gap-6">
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-semibold">Wilayah</p>
                <p class="text-sm font-bold text-slate-800">Kabupaten Banyuwangi</p>
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-semibold">Operator</p>
                <p class="text-sm font-bold text-slate-800">{{ Auth::user()->name }}</p>
            </div>
        </div>
    </div>
</header>

<main class="chart-shell pt-16 h-screen flex">
    <aside class="chart-nav admin-nav-sidebar hidden md:flex flex-col w-64 border-r h-full overflow-y-auto shrink-0 z-40">
        <div class="p-6 border-b border-slate-200">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center overflow-hidden">
                    <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }} Logo" class="w-8 h-8 object-contain">
                </div>
                <p class="text-xl font-extrabold text-[var(--primary)] leading-none">{{ $party['app_name'] }}</p>
            </div>
            <span class="font-mono-data text-[10px] uppercase tracking-widest text-red-600 bg-red-50 px-2 py-1 rounded">{{ $roleLabel }}</span>
        </div>
        <nav class="flex-1 py-4">
            @foreach($adminMenus as $menu)
                <a class="admin-nav-item {{ $menu['key'] === 'chart' ? 'active' : '' }}" href="{{ $menu['route'] }}">
                    <span class="material-symbols-outlined">{{ $menu['icon'] }}</span>
                    <span>{{ $menu['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <aside class="chart-filter w-[330px] bg-white text-slate-800 border-r border-slate-200 h-full overflow-y-auto flex flex-col p-6 shadow-xl z-40">
        <div class="mb-7">
            <p class="text-[10px] uppercase tracking-[0.24em] text-slate-500 font-bold mb-2">Filter Utama</p>
            <label class="block text-xs text-slate-600 mb-2 font-semibold">Jenis Pemilihan</label>
            <input type="hidden" id="f-jenis" value="{{ $defaultJenis }}">
            <div id="jenis-buttons" class="grid grid-cols-2 gap-2">
                @foreach(\App\Models\RekapHeader::JENIS_LABELS as $key => $label)
                    @if(in_array($key, $aktifJenis))
                        <button type="button"
                                data-jenis="{{ $key }}"
                                onclick="selectJenis('{{ $key }}')"
                                class="jenis-btn min-h-11 rounded-lg border px-3 py-2 text-left text-xs font-bold leading-tight transition">
                            {{ $label }}
                        </button>
                    @endif
                @endforeach
            </div>

            <div class="mt-5">
                <label class="block text-xs text-slate-600 mb-2 font-semibold">Cari Partai / Caleg</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-3 text-slate-400 text-lg">search</span>
                    <input id="f-search"
                           type="search"
                           oninput="applyChartSearch()"
                           onfocus="renderSearchSuggestions()"
                           placeholder="Ketik nama partai atau caleg"
                           autocomplete="off"
                           class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm font-semibold text-slate-800 placeholder:text-slate-400 outline-none focus:border-red-300 focus:ring-2 focus:ring-red-300/30">
                    <button type="button" onclick="clearChartSearch()" class="absolute right-2 top-2 hidden h-7 w-7 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" id="clear-search">
                        <span class="material-symbols-outlined text-base">close</span>
                    </button>
                    <div id="search-suggestions" class="hidden absolute left-0 right-0 top-full z-[1200] mt-2 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs text-slate-600 mb-2 font-semibold">Level Tampilan</label>
                <div class="relative">
                    <select id="f-level" onchange="onLevelChange()" class="w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 pr-10 text-sm font-semibold text-slate-800 outline-none focus:border-red-300 focus:ring-2 focus:ring-red-300/30">
                        <option value="kabupaten">Kabupaten</option>
                        <option value="dapil" class="hidden">Dapil</option>
                        <option value="kecamatan">Kecamatan</option>
                        <option value="desa">Desa</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-slate-500">expand_more</span>
                </div>
            </div>

            <div id="wrap-dapil" class="hidden">
                <label class="block text-xs text-slate-600 mb-2 font-semibold">Dapil</label>
                <select id="f-dapil" onchange="onDapilChange()" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-red-300">
                    <option value="">Pilih Dapil</option>
                    @foreach($dapils as $dapil)
                        <option value="{{ $dapil->id }}">{{ $dapil->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wrap-kec" class="hidden">
                <label class="block text-xs text-slate-600 mb-2 font-semibold">Kecamatan</label>
                <select id="f-kec" onchange="onKecChange()" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-red-300">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wrap-desa" class="hidden">
                <label class="block text-xs text-slate-600 mb-2 font-semibold">Desa</label>
                <select id="f-desa" onchange="onDesaChange()" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-red-300">
                    <option value="">Pilih Desa</option>
                </select>
            </div>

            <button id="wrap-reset-kec" onclick="resetKecFilter()" class="hidden w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100">
                Lihat seluruh kabupaten
            </button>
        </div>

        <div class="my-7 h-px bg-slate-200"></div>

        <div class="mt-auto pt-6">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold mb-2">Status Peta</p>
                <p id="map-selected-label" class="text-sm font-semibold text-slate-800">Klik kecamatan untuk filter</p>
            </div>
        </div>
    </aside>

    <section class="chart-main flex-1 h-full overflow-y-auto bg-[var(--surface-low)] p-5">
        <div class="map-panel relative h-[640px] min-h-[520px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm map-grid">
            <div id="map" class="absolute inset-0"></div>

            <section class="kpi-grid absolute left-6 right-6 top-6 z-[1000] grid grid-cols-4 gap-3">
                <div class="kpi-card glass-panel rounded-xl border border-slate-200 shadow-lg p-4">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-500 font-bold">Total Suara</p>
                    <p id="stat-total-suara" class="font-mono-data text-2xl font-extrabold text-[var(--primary)] mt-2">0</p>
                    <p class="text-xs text-slate-500 mt-1">suara sah</p>
                </div>
                <div class="kpi-card glass-panel rounded-xl border border-slate-200 shadow-lg p-4">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-500 font-bold">TPS Masuk</p>
                    <p id="stat-tps-masuk" class="font-mono-data text-2xl font-extrabold text-[var(--primary)] mt-2">0%</p>
                    <p id="stat-tps-detail" class="text-xs text-slate-500 mt-1">0 / 0 TPS</p>
                </div>
                <div class="kpi-card glass-panel rounded-xl border border-slate-200 shadow-lg p-4">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-500 font-bold">Partisipasi</p>
                    <p id="stat-partisipasi" class="font-mono-data text-2xl font-extrabold text-[var(--red)] mt-2">0%</p>
                    <p id="stat-partisipasi-detail" class="text-xs text-slate-500 mt-1">0 hadir / 0 DPT</p>
                </div>
                <div class="kpi-card glass-panel rounded-xl border border-slate-200 shadow-lg p-4">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-slate-500 font-bold">Selisih Teratas</p>
                    <p id="stat-selisih-teratas" class="font-mono-data text-2xl font-extrabold text-[var(--primary)] mt-2">0%</p>
                    <p id="stat-selisih-detail" class="text-xs text-slate-500 mt-1">Top 1 vs Top 2</p>
                </div>
            </section>

            <div class="map-info absolute left-6 top-36 z-[1000] glass-panel rounded-xl border border-slate-200 shadow-lg px-4 py-3 max-w-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Peta Sebaran</p>
                        <p class="text-sm text-slate-700 mt-1">Warna wilayah mengikuti data pada filter aktif.</p>
                    </div>
                    <button id="map-reset-btn" type="button" onclick="resetKecFilter()" class="hidden shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                        Reset
                    </button>
                </div>
            </div>

            <div id="map-legend" class="hidden absolute left-6 bottom-6 z-[1000] glass-panel rounded-xl border border-slate-200 shadow-lg p-4 min-w-56">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold mb-3">Legenda Suara</p>
                <div class="space-y-2">
                    <div class="flex items-center gap-3"><span class="w-4 h-4 rounded" style="background:#fee2e2"></span><span class="text-xs text-slate-600">Rendah</span></div>
                    <div class="flex items-center gap-3"><span class="w-4 h-4 rounded" style="background:#fca5a5"></span><span class="text-xs text-slate-600">Menengah rendah</span></div>
                    <div class="flex items-center gap-3"><span class="w-4 h-4 rounded" style="background:#f87171"></span><span class="text-xs text-slate-600">Menengah</span></div>
                    <div class="flex items-center gap-3"><span class="w-4 h-4 rounded" style="background:#ef4444"></span><span class="text-xs text-slate-600">Tinggi</span></div>
                    <div class="flex items-center gap-3"><span class="w-4 h-4 rounded" style="background:#b91c1c"></span><span class="text-xs text-slate-600">Sangat tinggi</span></div>
                </div>
            </div>
        </div>

        <section class="mt-5 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4">
                <p id="detail-table-title" class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Tabel Detail Wilayah</p>
                <p id="detail-table-subtitle" class="text-sm text-slate-600 mt-1">Data mengikuti filter aktif.</p>
            </div>
            <div class="detail-table-scroll overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-bold">Wilayah</th>
                            <th id="detail-subject-header" class="px-5 py-3 font-bold">Pemenang</th>
                            <th class="px-5 py-3 font-bold text-right">Total Suara</th>
                            <th class="px-5 py-3 font-bold text-right">Partisipasi</th>
                            <th class="px-5 py-3 font-bold text-right">TPS Masuk</th>
                        </tr>
                    </thead>
                    <tbody id="detail-table-body" class="divide-y divide-slate-200">
                        <tr>
                            <td colspan="5" class="px-5 py-5 text-center text-sm text-slate-500">Belum ada data ditampilkan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <aside class="chart-summary w-[410px] bg-white border-l border-slate-200 h-full overflow-y-auto z-40 p-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <p class="text-[10px] uppercase tracking-[0.22em] text-slate-500 font-bold">Visualisasi</p>
                <h2 class="text-2xl font-extrabold text-[var(--primary)] mt-1">Ringkasan</h2>
            </div>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--red-soft)] text-[var(--red)]">
                <span class="material-symbols-outlined">bar_chart</span>
            </span>
        </div>

        <div id="chart-placeholder" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <span class="material-symbols-outlined text-4xl text-slate-400">query_stats</span>
            <p class="mt-3 text-sm font-semibold text-slate-600">Pilih jenis pemilihan untuk menampilkan grafik.</p>
        </div>

        <div id="chart-loading" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-8 text-center">
            <div class="mx-auto mb-3 h-8 w-8 rounded-full border-2 border-red-600 border-t-transparent animate-spin"></div>
            <p class="text-sm font-semibold text-slate-600">Memuat data grafik...</p>
        </div>

        <div id="chart-error" class="hidden rounded-xl border border-red-200 bg-red-50 p-5 text-sm font-semibold text-red-700"></div>

        <section id="card-kandidat" class="hidden rounded-xl border border-slate-200 bg-slate-50 overflow-hidden mb-5">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Ranking Kandidat</p>
                <p class="text-sm text-slate-600 mt-1">Perolehan suara utama.</p>
            </div>
            <div id="candidate-rank-list" class="candidate-rank-scroll divide-y divide-slate-200">
                <div class="px-5 py-4 text-sm text-slate-500">Belum ada data ditampilkan.</div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Wilayah Teratas</p>
                <!-- <span class="material-symbols-outlined text-sm text-[var(--red)]">bolt</span> -->
            </div>
            <div id="rank-list" class="divide-y divide-slate-200">
                <div class="px-5 py-4 text-sm text-slate-500">Belum ada data ditampilkan.</div>
            </div>
        </section>

        <section id="card-quick-stats" class="hidden rounded-xl border border-slate-200 bg-slate-50 overflow-hidden mt-5">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Quick Stats</p>
            </div>
            <div class="grid grid-cols-2 gap-3 p-5">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[10px] uppercase tracking-[0.12em] text-slate-500 font-bold">Partisipasi tertinggi</p>
                    <p id="quick-partisipasi" class="mt-2 text-sm font-extrabold text-slate-800 truncate">-</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[10px] uppercase tracking-[0.12em] text-slate-500 font-bold">DPT terbesar</p>
                    <p id="quick-dpt" class="mt-2 text-sm font-extrabold text-slate-800 truncate">-</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[10px] uppercase tracking-[0.12em] text-slate-500 font-bold">Suara sah tertinggi</p>
                    <p id="quick-suara" class="mt-2 text-sm font-extrabold text-slate-800 truncate">-</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-[10px] uppercase tracking-[0.12em] text-slate-500 font-bold">Selisih tertipis</p>
                    <p id="quick-selisih" class="mt-2 text-sm font-extrabold text-slate-800 truncate">-</p>
                </div>
            </div>
        </section>

        <section id="card-demografi" class="hidden rounded-xl border border-slate-200 bg-slate-50 overflow-hidden mt-5">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500 font-bold">Demografi Pemilih</p>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-600">Laki-laki</span>
                        <b id="demo-lk-label" class="font-mono-data text-slate-800">0%</b>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                        <div id="demo-lk-bar" class="h-full rounded-full bg-[var(--primary)]" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-600">Perempuan</span>
                        <b id="demo-pr-label" class="font-mono-data text-slate-800">0%</b>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                        <div id="demo-pr-bar" class="h-full rounded-full bg-[var(--red)]" style="width:0%"></div>
                    </div>
                </div>
            </div>
        </section>
    </aside>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    // Data awal dari Laravel untuk filter wilayah dan endpoint grafik.
    window.SIMAP_CHART_CONFIG = {
        dataUrl: @json(route('admin.rekap.chart.data')),
        geojsonVersion: @json(max(
            filemtime(public_path('geojson/banyuwangi_kecamatan.geojson')),
            filemtime(public_path('geojson/banyuwangi_desa_full.geojson'))
        )),
        kecamatans: @json($kecamatans->map(fn($k) => ['id' => $k->id, 'nama' => $k->nama])->values()),
        desas: @json($kecamatans->flatMap(fn($k) => $k->desas->map(fn($d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan_id' => $k->id]))->values()),
    };
</script>
<script src="{{ asset('js/rekap-admin-chart.js') }}?v={{ filemtime(public_path('js/rekap-admin-chart.js')) }}"></script>
</body>
</html>

