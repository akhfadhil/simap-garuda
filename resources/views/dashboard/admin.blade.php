@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('fullscreen', true)
@section('body_class', 'admin-dashboard min-h-screen overflow-x-hidden selection:bg-[#ff535b] selection:text-[#5b000e]')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Geist:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
    :root {
        --admin-bg: #f4f6fb;
        --admin-grid-dot: rgba(15, 23, 42, .08);
        --admin-sidebar: rgba(255, 255, 255, .92);
        --admin-topbar: rgba(255, 255, 255, .9);
        --admin-surface: rgba(255, 255, 255, .82);
        --admin-surface-strong: #eef2f7;
        --admin-border: rgba(15, 23, 42, .1);
        --admin-text: #1f2937;
        --admin-muted: rgba(71, 85, 105, .78);
        --admin-muted-soft: rgba(71, 85, 105, .55);
        --admin-primary: #bb152c;
        --admin-primary-soft: rgba(187, 21, 44, .1);
        --admin-danger: #e63946;
    }
    html.dark {
        --admin-bg: #0a0d14;
        --admin-grid-dot: rgba(255, 255, 255, .05);
        --admin-sidebar: rgba(11, 14, 21, .9);
        --admin-topbar: rgba(16, 19, 26, .9);
        --admin-surface: rgba(17, 24, 39, .7);
        --admin-surface-strong: #272a32;
        --admin-border: rgba(255, 255, 255, .06);
        --admin-text: #e1e2ec;
        --admin-muted: rgba(228, 190, 188, .82);
        --admin-muted-soft: rgba(228, 190, 188, .55);
        --admin-primary: #ffb3b1;
        --admin-primary-soft: rgba(255, 179, 177, .1);
        --admin-danger: #e63946;
    }
    .admin-dashboard {
        background: var(--admin-bg);
        color: var(--admin-text);
        font-family: 'Geist', sans-serif;
        transition: background-color .2s ease, color .2s ease;
    }
    .admin-display { font-family: 'Barlow Condensed', sans-serif; letter-spacing: .02em; }
    .admin-mono { font-family: 'JetBrains Mono', monospace; }
    .admin-grid {
        position: fixed;
        inset: 0;
        z-index: -1;
        background-image: radial-gradient(var(--admin-grid-dot) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }
    .admin-sidebar {
        background: var(--admin-sidebar);
        border-color: var(--admin-border);
    }
    .admin-topbar {
        background: var(--admin-topbar);
        border-color: var(--admin-border);
    }
    .admin-glass {
        background: var(--admin-surface);
        backdrop-filter: blur(12px);
        border: 1px solid var(--admin-border);
    }
    .admin-surface-strong { background: var(--admin-surface-strong); }
    .admin-border { border-color: var(--admin-border); }
    .admin-text { color: var(--admin-text); }
    .admin-muted { color: var(--admin-muted); }
    .admin-muted-soft { color: var(--admin-muted-soft); }
    .admin-primary { color: var(--admin-primary); }
    .admin-primary-bg { background: var(--admin-primary-soft); }
    .role-accent { color: var(--role-accent, #e63946); }
    .role-accent-bg { background: color-mix(in srgb, var(--role-accent, #e63946) 14%, transparent); }
    .admin-icon-button {
        color: var(--admin-muted);
        transition: color .2s ease, transform .2s ease;
    }
    .admin-icon-button:hover { color: var(--admin-primary); }
    .admin-top-link { color: var(--admin-muted); }
    .admin-top-link:hover { color: var(--admin-primary); }
    .admin-nav-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .75rem 1.5rem;
        color: var(--admin-muted);
        transition: all .2s ease;
    }
    .admin-nav-item:hover { background: var(--admin-surface-strong); color: var(--admin-primary); }
    .admin-nav-item.active {
        background: var(--admin-primary-soft);
        color: var(--admin-primary);
        border-right: 4px solid var(--admin-primary);
    }
    .admin-mobile-overlay {
        position: fixed;
        inset: 0;
        z-index: 80;
        background: rgba(0, 0, 0, .45);
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s ease;
    }
    .admin-mobile-drawer {
        position: fixed;
        inset: 0 auto 0 0;
        z-index: 90;
        width: min(82vw, 20rem);
        transform: translateX(-100%);
        transition: transform .25s ease;
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
    .admin-stat {
        animation: adminIn .8s cubic-bezier(.16, 1, .3, 1) both;
    }
    @keyframes adminIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .material-symbols-filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
@endpush

@section('content')
@php
    $party = $party ?? config('party');
    $roleLabel = $party['roles']['admin'];
    $userRoleLabel = $party['roles']['admin'] . ' ' . $party['short_name'];
    $totalPengguna     = \App\Models\User::count();
    $totalTps          = \App\Models\Tps::count();
    $aktifJenis        = \App\Models\PemiluSetting::aktif();
    $totalPemiluAktif  = count($aktifJenis);
    $targetPemiluTps   = $totalTps * $totalPemiluAktif;

    $totalRekapFinal   = \App\Models\RekapHeader::select('tps_id')
                        ->where('status', 'final')
                        ->whereIn('jenis', $aktifJenis)
                        ->groupBy('tps_id')
                        ->havingRaw('COUNT(DISTINCT jenis) = ?', [$totalPemiluAktif])
                        ->count();
    $persenRekap       = $totalTps > 0 ? min(100, round(($totalRekapFinal / $totalTps) * 100)) : 0;

    $menus = [
        ['label' => 'Beranda', 'icon' => 'dashboard', 'route' => route('dashboard.admin'), 'active' => true],
        ['label' => 'Pengguna', 'icon' => 'group', 'route' => route('admin.users.index')],
        ['label' => 'Grafik & Statistik', 'icon' => 'bar_chart', 'route' => route('admin.rekap.chart')],
        ['label' => 'Kelola Kecamatan', 'icon' => 'map', 'route' => route('admin.kecamatan.index')],
        ['label' => 'Kelola Desa', 'icon' => 'location_city', 'route' => route('admin.desa.index')],
        ['label' => 'Kelola TPS', 'icon' => 'pin_drop', 'route' => route('admin.tps.index')],
        ['label' => 'Rekapitulasi Data', 'icon' => 'analytics', 'route' => route('admin.rekap.index')],
        ['label' => 'Setup Data ' . $party['short_name'], 'icon' => 'settings', 'route' => route('admin.setup.index')],
    ];
@endphp

<div class="admin-grid"></div>
<input id="admin-mobile-menu" type="checkbox" class="hidden">
<label for="admin-mobile-menu" class="admin-mobile-overlay"></label>
<aside class="admin-mobile-drawer admin-sidebar flex flex-col border-r backdrop-blur-xl">
    <div class="p-5 flex items-center justify-between border-b admin-border">
        <div class="flex items-center gap-3">
            <div class="admin-primary-bg w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center">
                <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }} Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="admin-display admin-primary text-2xl leading-none">{{ $party['app_name'] }}</h1>
                <span class="admin-mono admin-muted-soft text-[10px] uppercase tracking-widest">{{ $roleLabel }}</span>
            </div>
        </div>
        <label for="admin-mobile-menu" class="admin-icon-button cursor-pointer p-2">
            <span class="material-symbols-outlined">close</span>
        </label>
    </div>

    <nav class="flex-1 py-4 space-y-1 overflow-y-auto">
        @foreach($menus as $menu)
            <a class="admin-nav-item {{ ! empty($menu['active']) ? 'active' : '' }}" href="{{ $menu['route'] }}">
                <span class="material-symbols-outlined {{ ! empty($menu['active']) ? 'material-symbols-filled' : '' }}">{{ $menu['icon'] }}</span>
                <span>{{ $menu['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="p-4 mt-auto border-t admin-border">
        <a href="{{ route('password.edit') }}" class="w-full flex items-center gap-4 admin-muted px-6 py-3 hover:text-[#e63946] transition">
            <span class="material-symbols-outlined">lock_reset</span>
            <span>Ubah Password</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-4 text-[#ff6f6f] px-6 py-3 hover:text-[#e63946] transition">
                <span class="material-symbols-outlined">logout</span>
                <span>Log Keluar</span>
            </button>
        </form>
    </div>
</aside>

<div class="flex min-h-screen">
    <aside class="admin-sidebar hidden md:flex flex-col h-screen sticky top-0 w-64 border-r backdrop-blur-xl z-[60]">
        <div class="p-6 flex flex-col gap-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="admin-primary-bg w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center">
                    <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }} Logo" class="w-full h-full object-contain">
                </div>
                <h1 class="admin-display admin-primary text-2xl leading-none">{{ $party['app_name'] }}</h1>
            </div>
            <div class="admin-primary-bg px-2 py-1 w-max rounded-sm">
                <span class="admin-display admin-primary uppercase text-[10px] tracking-[.2em]">{{ $roleLabel }}</span>
            </div>
        </div>

        <nav class="flex-1 mt-4 space-y-1 overflow-y-auto">
            @foreach($menus as $menu)
                <a class="admin-nav-item {{ ! empty($menu['active']) ? 'active' : '' }}" href="{{ $menu['route'] }}">
                    <span class="material-symbols-outlined {{ ! empty($menu['active']) ? 'material-symbols-filled' : '' }}">{{ $menu['icon'] }}</span>
                    <span>{{ $menu['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="p-4 mt-auto border-t admin-border">
            <a href="{{ route('password.edit') }}" class="w-full flex items-center gap-4 admin-muted px-6 py-3 hover:text-[#e63946] transition">
                <span class="material-symbols-outlined">lock_reset</span>
                <span>Ubah Password</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-4 text-[#ff6f6f] px-6 py-3 hover:text-[#e63946] transition">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Log Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 bg-transparent">
        <header class="admin-topbar sticky top-0 z-50 min-h-16 w-full flex flex-wrap gap-3 justify-between items-center px-4 lg:px-8 py-3 backdrop-blur-md border-b shadow-sm">
            <div class="flex items-center gap-4">
                <label for="admin-mobile-menu" class="admin-icon-button md:hidden cursor-pointer p-2 -ml-2" title="Buka menu">
                    <span class="material-symbols-outlined text-3xl">menu</span>
                </label>
                <div class="admin-primary-bg md:hidden w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center">
                    <img src="{{ asset($party['assets']['logo']) }}" alt="{{ $party['app_name'] }}" class="w-full h-full object-contain">
                </div>
                <div class="hidden lg:block">
                    <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.24em]">Sistem Informasi</p>
                    <p class="admin-text text-sm font-semibold leading-tight">{{ $party['tagline'] }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="toggleTheme()" class="admin-icon-button p-2 active:scale-95" title="Ubah tema">
                    <svg id="icon-sun" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg id="icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                <div class="h-8 w-px admin-surface-strong mx-1"></div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="admin-text text-xs font-bold leading-none">{{ strtoupper(Auth::user()->name) }}</p>
                        <p class="admin-muted text-[10px] leading-none mt-1">{{ $userRoleLabel }}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-[#e63946] flex items-center justify-center text-white font-bold text-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8 overflow-y-auto">
            <div class="mb-10 admin-stat" style="animation-delay: .1s">
                <p class="admin-mono admin-muted-soft tracking-[.3em] text-xs mb-2">// {{ strtoupper($roleLabel) }}</p>
                <h2 class="admin-display text-5xl lg:text-6xl admin-text leading-tight">DASHBOARD</h2>
                <p class="admin-muted text-lg max-w-2xl mt-2">
                    Kelola pengguna, wilayah saksi, rekapitulasi suara, grafik, dan laporan internal {{ $party['name'] }}.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
                <div class="admin-glass p-5 rounded-lg admin-stat" style="animation-delay: .2s" title="Jumlah total akun yang terdaftar dalam sistem">
                    <div class="flex justify-between items-start mb-4">
                        <span class="admin-display admin-muted tracking-widest text-[10px]">TOTAL PENGGUNA</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="admin-display text-4xl text-[#e63946]">{{ number_format($totalPengguna) }}</span>
                        <span class="admin-mono admin-muted-soft text-[11px] uppercase">terdaftar</span>
                    </div>
                </div>

                <div class="admin-glass p-5 rounded-lg admin-stat" style="animation-delay: .3s" title="Total cakupan titik pemungutan suara di seluruh wilayah">
                    <div class="flex justify-between items-start mb-4">
                        <span class="admin-display admin-muted tracking-widest text-[10px]">WILAYAH TPS</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="admin-display text-4xl text-[#e63946]">{{ number_format($totalTps) }}</span>
                        <span class="admin-mono admin-muted-soft text-[11px] uppercase">titik</span>
                    </div>
                </div>

                <div class="admin-glass p-5 rounded-lg admin-stat" style="animation-delay: .4s" title="Jumlah jenis pemilihan yang aktif untuk input dan rekap">
                    <div class="flex justify-between items-start mb-4">
                        <span class="admin-display admin-muted tracking-widest text-[10px]">JENIS PEMILIHAN AKTIF</span>
                        <span class="admin-muted text-[10px] admin-mono">{{ number_format($targetPemiluTps) }} target rekap</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="admin-display text-4xl text-[#e63946]">{{ number_format($totalPemiluAktif) }}</span>
                        <span class="admin-mono admin-muted-soft text-[11px] uppercase">aktif</span>
                    </div>
                </div>

                <div class="admin-glass p-5 rounded-lg admin-stat" style="animation-delay: .5s" title="Status finalisasi data dari seluruh TPS yang ada">
                    <div class="flex justify-between items-start mb-4">
                        <span class="admin-display admin-muted tracking-widest text-[10px]">REKAP FINALISASI</span>
                        <div class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#e63946]"></span>
                            <span class="text-[#e63946] text-[10px] admin-mono">COMPLETED</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="admin-display text-4xl text-[#e63946]">{{ number_format($totalRekapFinal) }}/{{ number_format($totalTps) }}</span>
                    </div>
                    <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">TPS final semua pemilu aktif</p>
                    <div class="admin-surface-strong mt-4 h-1 w-full overflow-hidden rounded-full">
                        <div class="h-full bg-[#e63946]" style="width:{{ $persenRekap }}%"></div>
                    </div>
                </div>
            </div>

            @include('dashboard.partials.election-summary', ['electionSummary' => $electionSummary])

            <div class="mt-12 py-8 border-t admin-border opacity-60">
            </div>
        </div>
    </main>
</div>

<a href="{{ route('admin.users.index') }}" class="fixed bottom-8 right-8 w-14 h-14 bg-[#e63946] rounded-full flex items-center justify-center text-white shadow-[0_0_20px_rgba(230,57,70,.4)] hover:scale-110 active:scale-95 transition z-[100]" title="Tambah pengguna">
    <span class="material-symbols-outlined text-3xl">add</span>
</a>
@endsection
