@extends('layouts.role-dashboard')
@section('title', 'Data Kordes')
@section('role_key', 'ppk')
@section('role_title', 'Korcam')
@section('role_subtitle', 'Koordinator Kecamatan')
@section('role_active', 'pps')

@section('role_content')
@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $totalJenisAktif = count($aktifJenis);
    $totalTpsAll = $desas->sum(fn($desa) => $desa->tps->count());
    $targetRekapAll = $totalTpsAll * $totalJenisAktif;
    $finalRekapAll = $desas->sum(fn($desa) => $desa->tps->sum(
        fn($tps) => $tps->rekapHeaders
            ->whereIn('jenis', $aktifJenis)
            ->where('status', 'final')
            ->count()
    ));
@endphp

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Korcam - Data Kordes</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DATA KORDES</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">{{ Auth::user()->kecamatan->nama ?? '' }}</p>
</div>

<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total Desa</p>
        <p class="font-display text-4xl text-orange-400">{{ $desas->count() }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total TPS</p>
        <p class="font-display text-4xl text-orange-400">{{ $totalTpsAll }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Rekap Final</p>
        <p class="font-display text-4xl text-orange-400">{{ $finalRekapAll }}/{{ $targetRekapAll }}</p>
    </div>
</div>

<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Daftar Desa dan Kordes
</p>

<div class="space-y-3">
@forelse($desas as $desa)
@php
    $totalTps = $desa->tps->count();
    $kordesUser = $desa->users->first();
    $targetRekap = $totalTps * $totalJenisAktif;
    $finalRekap = $desa->tps->sum(fn($tps) => $tps->rekapHeaders
        ->whereIn('jenis', $aktifJenis)
        ->where('status', 'final')
        ->count());
    $persen = $targetRekap > 0 ? min(100, round(($finalRekap / $targetRekap) * 100)) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-1 h-14 rounded-full flex-shrink-0 bg-orange-400"></div>
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $desa->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                {{ $totalTps }} TPS - Kordes: {{ $kordesUser->name ?? 'Belum assign' }}
            </p>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-32 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                    <div class="h-1.5 rounded-full bg-orange-400 transition-all" style="width:{{ $persen }}%"></div>
                </div>
                <span class="text-[11px] dark:text-gray-500 text-gray-400">
                    {{ $finalRekap }}/{{ $targetRekap }} rekap final
                </span>
            </div>
        </div>
    </div>

    <a href="{{ route('ppk.view-pps', $desa) }}"
       class="px-4 py-2 rounded-lg text-xs font-semibold border border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white transition">
        Lihat Kordes
    </a>
</div>
@empty
<div class="dark:bg-gray-800 bg-white rounded-xl px-6 py-16 text-center dark:text-gray-600 text-gray-400 text-sm border dark:border-gray-700 border-gray-200">
    Belum ada desa di kecamatan ini.
</div>
@endforelse
</div>

@endsection
