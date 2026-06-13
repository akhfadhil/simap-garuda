@extends('layouts.role-dashboard')
@section('title', 'Data TPS')
@section('role_key', 'pps')
@section('role_title', 'PPS')
@section('role_subtitle', 'Panitia Pemungutan Suara')
@section('role_active', 'tps')

@section('role_content')

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// PPS — Data TPS</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DATA TPS</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">
        {{ Auth::user()->desa->nama ?? '' }} · {{ Auth::user()->desa->kecamatan->nama ?? '' }}
    </p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    @php
        $aktifJenis = \App\Models\PemiluSetting::aktif();
        $aktifDokumenKeys = collect(\App\Models\Dokumen::JENIS)
            ->filter(fn($label, $key) => in_array(strtolower($key), $aktifJenis, true))
            ->keys();
        $totalJenisAktif = $aktifDokumenKeys->count();
        $totalTps      = $tpsList->count();
        $totalMaxDok   = $totalTps * $totalJenisAktif;
        $totalUploaded = $tpsList->sum(fn($t) => $t->dokumens->whereIn('jenis', $aktifDokumenKeys)->count());
        $totalVerif    = $tpsList->sum(fn($t) => $t->dokumens->whereIn('jenis', $aktifDokumenKeys)->where('status','terverifikasi')->count());
        $persenVerif   = $totalMaxDok > 0 ? round(($totalVerif / $totalMaxDok) * 100) : 0;
    @endphp

    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total TPS</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalTps }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">{{ $totalMaxDok }} dokumen maksimal</p>
    </div>

    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Dokumen Masuk</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalUploaded }}/{{ $totalMaxDok }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">menunggu proses verifikasi</p>
    </div>

    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Terverifikasi</p>
        <p class="font-display text-4xl text-teal-400">{{ $totalVerif }}/{{ $totalMaxDok }}</p>
        <div class="mt-2 flex items-center gap-2">
            <div class="flex-1 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-teal-400 transition-all" style="width:{{ $persenVerif }}%"></div>
            </div>
            <span class="text-xs dark:text-gray-500 text-gray-400">{{ $persenVerif }}%</span>
        </div>
    </div>
</div>

{{-- Daftar TPS --}}
<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Daftar TPS
</p>

<div class="space-y-3">
@forelse($tpsList as $tps)
@php
    $totalDok = $tps->dokumens->whereIn('jenis', $aktifDokumenKeys)->count();
    $terverif = $tps->dokumens->whereIn('jenis', $aktifDokumenKeys)->where('status','terverifikasi')->count();
    $kppsUser = $tps->users->first();
    $persen   = $totalJenisAktif > 0 ? round(($terverif / $totalJenisAktif) * 100) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-1 h-14 rounded-full flex-shrink-0 bg-teal-400"></div>
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $tps->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                KPPS: {{ $kppsUser->name ?? 'Belum assign' }}
            </p>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-32 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                    <div class="h-1.5 rounded-full bg-teal-400 transition-all"
                         style="width:{{ $persen }}%"></div>
                </div>
                <span class="text-[11px] dark:text-gray-500 text-gray-400">
                    {{ $terverif }}/{{ $totalJenisAktif }} terverifikasi · {{ $totalDok }} masuk
                </span>
            </div>
        </div>
    </div>

    <a href="{{ route('pps.view-tps', $tps) }}"
       class="px-4 py-2 rounded-lg text-xs font-semibold border border-teal-400 text-teal-400 hover:bg-teal-400 hover:text-white transition">
        👁 View KPPS
    </a>
</div>
@empty
<div class="dark:bg-gray-800 bg-white rounded-xl px-6 py-16 text-center dark:text-gray-600 text-gray-400 text-sm border dark:border-gray-700 border-gray-200">
    Belum ada TPS di desa ini.
</div>
@endforelse
</div>

@endsection
