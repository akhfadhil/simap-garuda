@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
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
    .admin-sidebar { background: var(--admin-sidebar); border-color: var(--admin-border); }
    .admin-topbar { background: var(--admin-topbar); border-color: var(--admin-border); }
    .admin-surface-strong { background: var(--admin-surface-strong); }
    .admin-border { border-color: var(--admin-border); }
    .admin-text { color: var(--admin-text); }
    .admin-muted { color: var(--admin-muted); }
    .admin-muted-soft { color: var(--admin-muted-soft); }
    .admin-primary { color: var(--admin-primary); }
    .admin-primary-bg { background: var(--admin-primary-soft); }
    .admin-icon-button { color: var(--admin-muted); transition: color .2s ease, transform .2s ease; }
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
    #admin-mobile-menu:checked ~ .admin-mobile-drawer { transform: translateX(0); }
    @media (min-width: 768px) {
        .admin-mobile-overlay,
        .admin-mobile-drawer { display: none; }
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .material-symbols-filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
@endpush

@section('content')
@php
    $menus = [
        ['label' => 'Beranda', 'icon' => 'dashboard', 'route' => route('dashboard.admin')],
        ['label' => 'Pengguna', 'icon' => 'group', 'route' => route('admin.users.index'), 'active' => true],
        ['label' => 'Grafik & Statistik', 'icon' => 'bar_chart', 'route' => route('admin.rekap.chart')],
        ['label' => 'Kelola Kecamatan', 'icon' => 'map', 'route' => route('admin.kecamatan.index')],
        ['label' => 'Kelola Desa', 'icon' => 'location_city', 'route' => route('admin.desa.index')],
        ['label' => 'Kelola TPS', 'icon' => 'pin_drop', 'route' => route('admin.tps.index')],
        ['label' => 'Rekap Dokumen', 'icon' => 'folder_open', 'route' => route('dokumen.admin')],
        ['label' => 'Rekapitulasi Data', 'icon' => 'analytics', 'route' => route('admin.rekap.index')],
        ['label' => 'Setup Data Pemilu', 'icon' => 'settings', 'route' => route('admin.setup.index')],
    ];
@endphp

<div class="admin-grid"></div>
<input id="admin-mobile-menu" type="checkbox" class="hidden">
<label for="admin-mobile-menu" class="admin-mobile-overlay"></label>
<aside class="admin-mobile-drawer admin-sidebar flex flex-col border-r backdrop-blur-xl">
    <div class="p-5 flex items-center justify-between border-b admin-border">
        <div class="flex items-center gap-3">
            <div class="admin-primary-bg w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center">
                <img src="{{ asset('images/logo-kpu.png') }}" alt="SIMAP Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="admin-display admin-primary text-2xl leading-none">SIMAP</h1>
                <span class="admin-mono admin-muted-soft text-[10px] uppercase tracking-widest">Administrator</span>
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
                    <img src="{{ asset('images/logo-kpu.png') }}" alt="SIMAP Logo" class="w-full h-full object-contain">
                </div>
                <h1 class="admin-display admin-primary text-2xl leading-none">SIMAP</h1>
            </div>
            <div class="admin-primary-bg px-2 py-1 w-max rounded-sm">
                <span class="admin-display admin-primary uppercase text-[10px] tracking-[.2em]">Administrator</span>
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
                    <img src="{{ asset('images/logo-kpu.png') }}" alt="SIMAP" class="w-full h-full object-contain">
                </div>
                <div class="hidden lg:block">
                    <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.24em]">Sistem Informasi</p>
                    <p class="admin-text text-sm font-semibold leading-tight">Sistem Informasi Manajemen Arsip Pemilu</p>
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
                        <p class="admin-muted text-[10px] leading-none mt-1">Admin Utama</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-[#e63946] flex items-center justify-center text-white font-bold text-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8 overflow-y-auto">

<div class="mb-8">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Pengguna</p>
            <h1 class="font-display text-4xl tracking-[2px] admin-text">MANAJEMEN PENGGUNA</h1>
            <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">Kelola akun Admin/Operator, Komisioner, Partai, PPK, PPS, dan KPPS.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            <a href="{{ route('admin.users.bulk') }}"
               class="flex items-center gap-2 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 text-xs font-semibold px-5 py-2.5 rounded-lg transition mt-1">
                Bulk Input
            </a>
            <button onclick="openModal('tambah')"
                    class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-5 py-2.5 rounded-lg transition mt-1">
                + Tambah User
            </button>
        </div>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ⚠ {{ session('error') }}
</div>
@endif

{{-- Filter --}}
<div class="flex gap-3 mb-6 flex-wrap">
    <form method="GET" id="filter-form" class="flex gap-3 flex-wrap items-center">
        {{-- Role --}}
        <select name="role" onchange="this.form.submit()"
                class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            <option value="">Semua Role</option>
            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin/Operator</option>
            <option value="komisioner" {{ request('role') == 'komisioner' ? 'selected' : '' }}>Komisioner</option>
            <option value="partai" {{ request('role') == 'partai' ? 'selected' : '' }}>Partai</option>
            <option value="ppk"  {{ request('role') == 'ppk'  ? 'selected' : '' }}>PPK</option>
            <option value="pps"  {{ request('role') == 'pps'  ? 'selected' : '' }}>PPS</option>
            <option value="kpps" {{ request('role') == 'kpps' ? 'selected' : '' }}>KPPS</option>
        </select>

        {{-- Kecamatan --}}
        <select name="kecamatan_id" onchange="filterKecChange(this.value)"
                class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            <option value="">Semua Kecamatan</option>
            @foreach($kecamatans as $kec)
            <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>
                {{ $kec->nama }}
            </option>
            @endforeach
        </select>

        {{-- Desa (muncul jika kecamatan dipilih) --}}
        <select name="desa_id" id="filter-desa" onchange="this.form.submit()"
                class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none {{ !request('kecamatan_id') ? 'hidden' : '' }}">
            <option value="">Semua Desa</option>
            @foreach($desas->where('kecamatan_id', request('kecamatan_id')) as $desa)
            <option value="{{ $desa->id }}" {{ request('desa_id') == $desa->id ? 'selected' : '' }}>
                {{ $desa->nama }}
            </option>
            @endforeach
        </select>

        {{-- Reset --}}
        @if(request('role') || request('kecamatan_id') || request('desa_id'))
        <a href="{{ route('admin.users.index') }}"
           class="text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition">× Reset</a>
        @endif

        @if($usersLoaded)
        <a href="{{ route('admin.users.export', request()->only('role', 'kecamatan_id', 'desa_id')) }}"
           class="px-4 py-2.5 rounded-lg text-xs font-semibold border border-green-500 text-green-600 dark:text-green-400 hover:bg-green-500 hover:text-white transition">
            Export CSV
        </a>
        @endif

        <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold uppercase tracking-wider">
            {{ $usersLoaded ? $users->total() . ' User' : 'Pilih filter untuk memuat user' }}
        </span>
    </form>
</div>

{{-- Tabel --}}
@if(!$usersLoaded)
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-8 text-center mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Daftar user tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
        Pilih role, kecamatan, atau desa untuk menampilkan user. Ini menjaga halaman tetap ringan saat jumlah user besar.
    </p>
</div>
@else
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-x-auto">
    <div class="grid grid-cols-12 min-w-[860px] px-6 py-3 border-b dark:border-gray-700 border-gray-200">
        <div class="col-span-3 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Nama</div>
        <div class="col-span-2 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Username</div>
        <div class="col-span-1 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Role</div>
        <div class="col-span-4 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Wilayah</div>
        <div class="col-span-2 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold text-right">Aksi</div>
    </div>

    @forelse($users as $user)
    @php
        $roleColor = match($user->role) {
            'admin' => '#DC2626',
            'komisioner' => '#2563EB',
            'partai' => '#7C3AED',
            'ppk'  => '#F4A261',
            'pps'  => '#2EC4B6',
            'kpps' => '#A8DADC',
            default => '#666'
        };
        $wilayah = match($user->role) {
            'ppk'  => $user->kecamatan->nama ?? '-',
            'pps'  => ($user->desa->nama ?? '-') . ' / ' . ($user->desa->kecamatan->nama ?? '-'),
            'kpps' => ($user->tps->nama ?? '-') . ' / ' . ($user->tps->desa->nama ?? '-'),
            'partai' => ($user->partai?->nomor_urut ? $user->partai->nomor_urut . '. ' : '') . ($user->partai?->nama_partai ?? '-'),
            default => '-'
        };
    @endphp
    <div class="grid grid-cols-12 min-w-[860px] px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50 transition group items-center">
        <div class="col-span-3">
            <p class="text-sm font-medium dark:text-gray-100 text-gray-800">{{ $user->name }}</p>
        </div>
        <div class="col-span-2">
            <p class="text-xs dark:text-gray-400 text-gray-500">{{ $user->username }}</p>
        </div>
        <div class="col-span-1">
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold"
                  style="color:{{ $roleColor }};background:{{ $roleColor }}20;border:1px solid {{ $roleColor }}40">
                {{ strtoupper($user->role) }}
            </span>
        </div>
        <div class="col-span-4">
            <p class="text-xs dark:text-gray-500 text-gray-400">{{ $wilayah }}</p>
        </div>
        <div class="col-span-2 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
            <button onclick="openEdit({{ json_encode($user) }})"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Edit
            </button>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  onsubmit="return confirm('Hapus user {{ $user->username }}?')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-400 text-red-400 hover:bg-red-500 hover:text-white transition">
                    Hapus
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="px-6 py-16 text-center dark:text-gray-600 text-gray-400 text-sm">
        Belum ada user.
    </div>
    @endforelse
</div>
@endif

{{-- Pagination --}}
@if($usersLoaded && $users->hasPages())
<div class="flex items-center justify-between mt-4 flex-wrap gap-3">
    <p class="text-xs dark:text-gray-500 text-gray-400">
        Menampilkan <span class="font-semibold dark:text-gray-300 text-gray-600">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span>
        dari <span class="font-semibold dark:text-gray-300 text-gray-600">{{ $users->total() }}</span> user
    </p>
    <div class="flex items-center gap-1">
        {{-- Prev --}}
        @if($users->onFirstPage())
            <span class="px-3 py-1.5 text-xs rounded-lg dark:text-gray-600 text-gray-300 cursor-not-allowed">← Prev</span>
        @else
            <a href="{{ $users->previousPageUrl() }}"
               class="px-3 py-1.5 text-xs rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                ← Prev
            </a>
        @endif

        {{-- Page numbers --}}
        @php
            $current  = $users->currentPage();
            $last     = $users->lastPage();
            $start    = max(1, $current - 2);
            $end      = min($last, $current + 2);
        @endphp

        @if($start > 1)
            <a href="{{ $users->url(1) }}"
               class="px-3 py-1.5 text-xs rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">1</a>
            @if($start > 2)
                <span class="px-2 text-xs dark:text-gray-600 text-gray-400">…</span>
            @endif
        @endif

        @for($page = $start; $page <= $end; $page++)
            @if($page == $current)
                <span class="px-3 py-1.5 text-xs rounded-lg bg-red-600 text-white font-semibold">{{ $page }}</span>
            @else
                <a href="{{ $users->url($page) }}"
                   class="px-3 py-1.5 text-xs rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    {{ $page }}
                </a>
            @endif
        @endfor

        @if($end < $last)
            @if($end < $last - 1)
                <span class="px-2 text-xs dark:text-gray-600 text-gray-400">…</span>
            @endif
            <a href="{{ $users->url($last) }}"
               class="px-3 py-1.5 text-xs rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">{{ $last }}</a>
        @endif

        {{-- Next --}}
        @if($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}"
               class="px-3 py-1.5 text-xs rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Next →
            </a>
        @else
            <span class="px-3 py-1.5 text-xs rounded-lg dark:text-gray-600 text-gray-300 cursor-not-allowed">Next →</span>
        @endif
    </div>
</div>
@endif

        </div>
    </main>
</div>

@php
$inputClass = "w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none";
$labelClass = "block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2";
@endphp

{{-- ══════════════ MODAL TAMBAH ══════════════ --}}
<div id="modal-tambah" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between px-8 py-5 border-b dark:border-gray-700 border-gray-200">
            <div>
                <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Admin</p>
                <h2 class="font-display text-2xl tracking-wide text-red-600 mt-0.5">TAMBAH USER</h2>
            </div>
            <button onclick="closeModal('tambah')" class="dark:text-gray-500 text-gray-400 hover:text-red-500 transition text-xl">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="px-8 py-6 space-y-5">
            @csrf
            <div>
                <label class="{{ $labelClass }}">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="cth: Operator PPK Andir" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="cth: ppk_andir" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Password <span class="dark:text-gray-600 text-gray-400 normal-case tracking-normal font-normal">(kosong = username)</span></label>
                <input type="password" name="password" placeholder="Opsional, min. 6 karakter" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Role</label>
                <select name="role" id="tambah-role" onchange="updateWilayahField('tambah')" class="{{ $inputClass }}">
                    <option value="">- Pilih Role -</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin/Operator - Akses penuh sistem</option>
                    <option value="komisioner" {{ old('role') == 'komisioner' ? 'selected' : '' }}>Komisioner - Akses baca data kabupaten</option>
                    <option value="partai" {{ old('role') == 'partai' ? 'selected' : '' }}>Partai - Grafik dan rekap partai</option>
                    <option value="ppk"  {{ old('role') == 'ppk'  ? 'selected' : '' }}>PPK — Panitia Pemilihan Kecamatan</option>
                    <option value="pps"  {{ old('role') == 'pps'  ? 'selected' : '' }}>PPS — Panitia Pemungutan Suara</option>
                    <option value="kpps" {{ old('role') == 'kpps' ? 'selected' : '' }}>KPPS — Kelompok Penyelenggara</option>
                </select>
            </div>

            <div id="tambah-wilayah" class="space-y-4 hidden">
                <div id="tambah-field-partai" class="hidden">
                    <label class="{{ $labelClass }}">Partai</label>
                    <select name="partai_id" class="{{ $inputClass }}">
                        <option value="">- Pilih Partai -</option>
                        @foreach($partais as $partai)
                        <option value="{{ $partai->id }}">{{ $partai->nomor_urut }}. {{ $partai->nama_partai }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="tambah-field-kecamatan" class="hidden">
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <select name="kecamatan_id" class="{{ $inputClass }}">
                        <option value="">— Pilih Kecamatan —</option>
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="tambah-field-kecamatan-pps" class="hidden">
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <select id="tambah-kec-pps" onchange="loadDesa('tambah', this.value)" class="{{ $inputClass }}">
                        <option value="">— Pilih Kecamatan —</option>
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="tambah-field-desa" class="hidden">
                    <label class="{{ $labelClass }}">Desa</label>
                    <select name="desa_id" id="tambah-desa-select" class="{{ $inputClass }}">
                        <option value="">— Pilih Desa —</option>
                    </select>
                </div>
                <div id="tambah-field-kecamatan-kpps" class="hidden">
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <select id="tambah-kec-kpps" onchange="loadDesa('tambah-kpps', this.value)" class="{{ $inputClass }}">
                        <option value="">— Pilih Kecamatan —</option>
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="tambah-field-desa-kpps" class="hidden">
                    <label class="{{ $labelClass }}">Desa</label>
                    <select id="tambah-desa-kpps" onchange="loadTps('tambah', this.value)" class="{{ $inputClass }}">
                        <option value="">— Pilih Desa —</option>
                    </select>
                </div>
                <div id="tambah-field-tps" class="hidden">
                    <label class="{{ $labelClass }}">TPS</label>
                    <select name="tps_id" id="tambah-tps-select" class="{{ $inputClass }}">
                        <option value="">— Pilih TPS —</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('tambah')"
                        class="flex-1 border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 py-2.5 rounded-lg text-sm font-medium dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold transition">
                    Simpan →
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════ MODAL EDIT ══════════════ --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between px-8 py-5 border-b dark:border-gray-700 border-gray-200">
            <div>
                <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Admin</p>
                <h2 class="font-display text-2xl tracking-wide text-red-600 mt-0.5">EDIT USER</h2>
            </div>
            <button onclick="closeModal('edit')" class="dark:text-gray-500 text-gray-400 hover:text-red-500 transition text-xl">✕</button>
        </div>

        <form method="POST" id="edit-form" class="px-8 py-6 space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="{{ $labelClass }}">Nama Lengkap</label>
                <input type="text" name="name" id="edit-name" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Username</label>
                <input type="text" name="username" id="edit-username" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Password <span class="dark:text-gray-600 text-gray-400 normal-case tracking-normal font-normal">(kosongkan jika tidak diganti)</span></label>
                <input type="password" name="password" placeholder="••••••••" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Role</label>
                <input type="text" id="edit-role-display"
                       class="w-full dark:bg-gray-900 bg-gray-100 border dark:border-gray-700 border-gray-200 dark:text-gray-500 text-gray-400 px-4 py-2.5 text-sm rounded-lg cursor-not-allowed" readonly>
            </div>

            <div id="edit-wilayah-partai" class="hidden">
                <label class="{{ $labelClass }}">Partai</label>
                <select name="partai_id" id="edit-partai" class="{{ $inputClass }}">
                    @foreach($partais as $partai)
                    <option value="{{ $partai->id }}">{{ $partai->nomor_urut }}. {{ $partai->nama_partai }}</option>
                    @endforeach
                </select>
            </div>

            <div id="edit-wilayah-ppk" class="hidden">
                <label class="{{ $labelClass }}">Kecamatan</label>
                <select name="kecamatan_id" id="edit-kecamatan" class="{{ $inputClass }}">
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div id="edit-wilayah-pps" class="hidden space-y-4">
                <div>
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <select id="edit-kec-pps" onchange="loadDesaEdit(this.value)" class="{{ $inputClass }}">
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Desa</label>
                    <select name="desa_id" id="edit-desa-select" class="{{ $inputClass }}"></select>
                </div>
            </div>

            <div id="edit-wilayah-kpps" class="hidden space-y-4">
                <div>
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <select id="edit-kec-kpps" onchange="loadDesaEditKpps(this.value)" class="{{ $inputClass }}">
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Desa</label>
                    <select id="edit-desa-kpps" onchange="loadTpsEdit(this.value)" class="{{ $inputClass }}"></select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">TPS</label>
                    <select name="tps_id" id="edit-tps-select" class="{{ $inputClass }}"></select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('edit')"
                        class="flex-1 border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 py-2.5 rounded-lg text-sm font-medium dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold transition">
                    Simpan →
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const allDesas = @json($desas->map(fn($d) => ['id'=>$d->id,'nama'=>$d->nama,'kecamatan_id'=>$d->kecamatan_id]));
    const allTps   = @json($tpsList->map(fn($t) => ['id'=>$t->id,'nama'=>$t->nama,'desa_id'=>$t->desa_id]));

    function openModal(type) {
        document.getElementById('modal-' + type).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(type) {
        document.getElementById('modal-' + type).classList.add('hidden');
        document.body.style.overflow = '';
    }
    ['tambah','edit'].forEach(type => {
        document.getElementById('modal-' + type).addEventListener('click', function(e) {
            if (e.target === this) closeModal(type);
        });
    });

    function updateWilayahField(prefix) {
        const role = document.getElementById(prefix + '-role').value;
        const wrap = document.getElementById(prefix + '-wilayah');
        ['partai','kecamatan','kecamatan-pps','field-desa','kecamatan-kpps','field-desa-kpps','field-tps'].forEach(f => {
            const el = document.getElementById(prefix + '-field-' + f);
            if (el) el.classList.add('hidden');
        });
        if (!role || role === 'admin' || role === 'komisioner') { wrap.classList.add('hidden'); return; }
        wrap.classList.remove('hidden');
        if (role === 'partai')    document.getElementById(prefix + '-field-partai').classList.remove('hidden');
        else if (role === 'ppk')  document.getElementById(prefix + '-field-kecamatan').classList.remove('hidden');
        else if (role === 'pps')  document.getElementById(prefix + '-field-kecamatan-pps').classList.remove('hidden');
        else if (role === 'kpps') document.getElementById(prefix + '-field-kecamatan-kpps').classList.remove('hidden');
    }

    function loadDesa(prefix, kecId) {
        const desas = allDesas.filter(d => d.kecamatan_id == kecId);
        let selId, fieldId;
        if (prefix === 'tambah') {
            selId   = 'tambah-desa-select';
            fieldId = 'tambah-field-desa';
        } else if (prefix === 'tambah-kpps') {
            selId   = 'tambah-desa-kpps';
            fieldId = 'tambah-field-desa-kpps';
        }
        const sel = document.getElementById(selId);
        sel.innerHTML = '<option value="">— Pilih Desa —</option>';
        desas.forEach(d => sel.innerHTML += `<option value="${d.id}">${d.nama}</option>`);
        document.getElementById(fieldId).classList.toggle('hidden', desas.length === 0);
    }

    function loadTps(prefix, desaId) {
        const list = allTps.filter(t => t.desa_id == desaId);
        const sel  = document.getElementById(prefix + '-tps-select');
        sel.innerHTML = '<option value="">— Pilih TPS —</option>';
        list.forEach(t => sel.innerHTML += `<option value="${t.id}">${t.nama}</option>`);
        document.getElementById(prefix + '-field-tps').classList.toggle('hidden', list.length === 0);
    }

    function openEdit(user) {
        document.getElementById('edit-name').value         = user.name;
        document.getElementById('edit-username').value     = user.username;
        document.getElementById('edit-role-display').value = user.role.toUpperCase();
        document.getElementById('edit-form').action        = `/admin/users/${user.id}`;
        ['partai','ppk','pps','kpps'].forEach(r => document.getElementById('edit-wilayah-' + r).classList.add('hidden'));
        if (user.role === 'partai') {
            document.getElementById('edit-wilayah-partai').classList.remove('hidden');
            if (user.partai_id) document.getElementById('edit-partai').value = user.partai_id;
        } else if (user.role === 'ppk') {
            document.getElementById('edit-wilayah-ppk').classList.remove('hidden');
            if (user.kecamatan_id) document.getElementById('edit-kecamatan').value = user.kecamatan_id;
        } else if (user.role === 'pps') {
            document.getElementById('edit-wilayah-pps').classList.remove('hidden');
            if (user.desa_id) {
                const desa = allDesas.find(d => d.id == user.desa_id);
                if (desa) { document.getElementById('edit-kec-pps').value = desa.kecamatan_id; loadDesaEdit(desa.kecamatan_id, user.desa_id); }
            }
        } else if (user.role === 'kpps') {
            document.getElementById('edit-wilayah-kpps').classList.remove('hidden');
            if (user.tps_id) {
                const tps  = allTps.find(t => t.id == user.tps_id);
                const desa = tps ? allDesas.find(d => d.id == tps.desa_id) : null;
                if (desa) { document.getElementById('edit-kec-kpps').value = desa.kecamatan_id; loadDesaEditKpps(desa.kecamatan_id, tps.desa_id, user.tps_id); }
            }
        }
        openModal('edit');
    }

    function loadDesaEdit(kecId, selectedDesaId = null) {
        const desas = allDesas.filter(d => d.kecamatan_id == kecId);
        const sel   = document.getElementById('edit-desa-select');
        sel.innerHTML = '<option value="">— Pilih Desa —</option>';
        desas.forEach(d => sel.innerHTML += `<option value="${d.id}" ${d.id == selectedDesaId ? 'selected' : ''}>${d.nama}</option>`);
    }

    function loadDesaEditKpps(kecId, selectedDesaId = null, selectedTpsId = null) {
        const desas = allDesas.filter(d => d.kecamatan_id == kecId);
        const sel   = document.getElementById('edit-desa-kpps');
        sel.innerHTML = '<option value="">— Pilih Desa —</option>';
        desas.forEach(d => sel.innerHTML += `<option value="${d.id}" ${d.id == selectedDesaId ? 'selected' : ''}>${d.nama}</option>`);
        if (selectedDesaId) loadTpsEdit(selectedDesaId, selectedTpsId);
    }

    function loadTpsEdit(desaId, selectedTpsId = null) {
        const list = allTps.filter(t => t.desa_id == desaId);
        const sel  = document.getElementById('edit-tps-select');
        sel.innerHTML = '<option value="">— Pilih TPS —</option>';
        list.forEach(t => sel.innerHTML += `<option value="${t.id}" ${t.id == selectedTpsId ? 'selected' : ''}>${t.nama}</option>`);
    }
</script>
@endpush

@endsection
