@extends('layouts.role-dashboard')
@section('title', 'Data TPS')
@section('role_key', 'kordes')
@section('role_title', 'Kordes')
@section('role_subtitle', 'Koordinator Desa')
@section('role_active', 'tps')

@section('role_content')
@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $totalJenisAktif = count($aktifJenis);
    $totalTps = $tpsList->count();
    $targetRekap = $totalTps * $totalJenisAktif;
    $totalRekap = $tpsList->sum(fn($tps) => $tps->rekapHeaders->whereIn('jenis', $aktifJenis)->count());
    $totalFinal = $tpsList->sum(fn($tps) => $tps->rekapHeaders->whereIn('jenis', $aktifJenis)->where('status', 'final')->count());
    $persenFinal = $targetRekap > 0 ? min(100, round(($totalFinal / $targetRekap) * 100)) : 0;
@endphp

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Kordes - Data TPS</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DATA TPS</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">
        {{ Auth::user()->desa->nama ?? '' }} - {{ Auth::user()->desa->kecamatan->nama ?? '' }}
    </p>
</div>

<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total TPS</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalTps }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">{{ $targetRekap }} target rekap</p>
    </div>

    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Rekap Diinput</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalRekap }}/{{ $targetRekap }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">draft dan final</p>
    </div>

    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Rekap Final</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalFinal }}/{{ $targetRekap }}</p>
        <div class="mt-2 flex items-center gap-2">
            <div class="flex-1 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-teal-400 transition-all" style="width:{{ $persenFinal }}%"></div>
            </div>
            <span class="text-xs dark:text-gray-500 text-gray-400">{{ $persenFinal }}%</span>
        </div>
    </div>
</div>

<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Daftar TPS dan Saksi
</p>

<div class="space-y-3">
@forelse($tpsList as $tps)
@php
    $rekapCount = $tps->rekapHeaders->whereIn('jenis', $aktifJenis)->count();
    $finalCount = $tps->rekapHeaders->whereIn('jenis', $aktifJenis)->where('status', 'final')->count();
    $saksiUser = $tps->users->first();
    $persen = $totalJenisAktif > 0 ? min(100, round(($finalCount / $totalJenisAktif) * 100)) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-1 h-14 rounded-full flex-shrink-0 bg-teal-400"></div>
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $tps->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                Saksi TPS: {{ $saksiUser->name ?? 'Belum assign' }}
            </p>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-32 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                    <div class="h-1.5 rounded-full bg-teal-400 transition-all" style="width:{{ $persen }}%"></div>
                </div>
                <span class="text-[11px] dark:text-gray-500 text-gray-400">
                    {{ $finalCount }}/{{ $totalJenisAktif }} final - {{ $rekapCount }} diinput
                </span>
            </div>
        </div>
    </div>

    <a href="{{ route('kordes.view-tps', $tps) }}"
       class="px-4 py-2 rounded-lg text-xs font-semibold border border-teal-400 text-teal-400 hover:bg-teal-400 hover:text-white transition">
        Kelola Rekap TPS
    </a>
</div>
@empty
<div class="dark:bg-gray-800 bg-white rounded-xl px-6 py-16 text-center dark:text-gray-600 text-gray-400 text-sm border dark:border-gray-700 border-gray-200">
    Belum ada TPS di desa ini.
</div>
@endforelse
</div>

@endsection
