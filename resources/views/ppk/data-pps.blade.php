@extends('layouts.role-dashboard')
@section('title', 'Data PPS')
@section('role_key', 'ppk')
@section('role_title', 'PPK')
@section('role_subtitle', 'Panitia Pemilihan Kecamatan')
@section('role_active', 'pps')

@section('role_content')

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// PPK — Data PPS</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DATA PPS</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">{{ Auth::user()->kecamatan->nama ?? '' }}</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    @php
        $aktifJenis = \App\Models\PemiluSetting::aktif();
        $aktifDokumenKeys = collect(\App\Models\Dokumen::JENIS)
            ->filter(fn($label, $key) => in_array(strtolower($key), $aktifJenis, true))
            ->keys();
        $totalJenisAktif = $aktifDokumenKeys->count();
        $totalMaxDok = $desas->sum(fn($d) => $d->tps->count()) * $totalJenisAktif;
        $totalTerverifikasi = $desas->sum(fn($d) => $d->tps->sum(
            fn($t) => $t->dokumens->whereIn('jenis', $aktifDokumenKeys)->where('status', 'terverifikasi')->count()
        ));
    @endphp
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total Desa/PPS</p>
        <p class="font-display text-4xl text-orange-400">{{ $desas->count() }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Total TPS</p>
        <p class="font-display text-4xl text-orange-400">{{ $desas->sum(fn($d) => $d->tps->count()) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-6 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">Terverifikasi</p>
        <p class="font-display text-4xl text-orange-400">{{ $totalTerverifikasi }}/{{ $totalMaxDok }}</p>
    </div>
</div>

{{-- Daftar Desa --}}
<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Daftar Desa & PPS
</p>

<div class="space-y-3">
@forelse($desas as $desa)
@php
    $totalTps = $desa->tps->count();
    $totalDok = $desa->tps->sum(fn($t) => $t->dokumens->whereIn('jenis', $aktifDokumenKeys)->count());
    $terverif = $desa->tps->sum(fn($t) => $t->dokumens->whereIn('jenis', $aktifDokumenKeys)->where('status','terverifikasi')->count());
    $ppsUser  = $desa->users->first();
    $targetDok = $totalTps * $totalJenisAktif;
    $persen   = $targetDok > 0 ? round(($terverif / $targetDok) * 100) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-1 h-14 rounded-full flex-shrink-0 bg-orange-400"></div>
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $desa->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                {{ $totalTps }} TPS · PPS: {{ $ppsUser->name ?? 'Belum assign' }}
            </p>
            <div class="flex items-center gap-2 mt-2">
                <div class="w-32 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                    <div class="h-1.5 rounded-full bg-orange-400 transition-all"
                         style="width:{{ $persen }}%"></div>
                </div>
                <span class="text-[11px] dark:text-gray-500 text-gray-400">
                    {{ $terverif }}/{{ $targetDok }} terverifikasi · {{ $totalDok }} masuk
                </span>
            </div>
        </div>
    </div>

    <a href="{{ route('ppk.view-pps', $desa) }}"
       class="px-4 py-2 rounded-lg text-xs font-semibold border border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white transition">
        👁 View PPS
    </a>
</div>
@empty
<div class="dark:bg-gray-800 bg-white rounded-xl px-6 py-16 text-center dark:text-gray-600 text-gray-400 text-sm border dark:border-gray-700 border-gray-200">
    Belum ada desa di kecamatan ini.
</div>
@endforelse
</div>

@endsection
