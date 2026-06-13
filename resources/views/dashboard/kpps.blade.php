@extends('layouts.role-dashboard')
@section('title', 'Dashboard KPPS')
@section('role_key', 'kpps')
@section('role_title', 'KPPS')
@section('role_subtitle', 'Kelompok Penyelenggara Pemungutan Suara')
@section('role_active', 'dashboard')

@section('role_content')
@php
    $tps      = $viewTps ?? Auth::user()->tps;
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $totalPemiluAktif = count($aktifJenis);
    $dokumenJenisAktif = array_map('strtoupper', $aktifJenis);
    $uploaded = $tps
        ? \App\Models\Dokumen::where('tps_id', $tps->id)->whereIn('jenis', $dokumenJenisAktif)->count()
        : 0;
    $terverif = $tps
        ? \App\Models\Dokumen::where('tps_id', $tps->id)->whereIn('jenis', $dokumenJenisAktif)->where('status','terverifikasi')->count()
        : 0;
    $totalRekap = $tps
        ? \App\Models\RekapHeader::where('tps_id', $tps->id)->whereIn('jenis', $aktifJenis)->count()
        : 0;
    $finalRekap = $tps
        ? \App\Models\RekapHeader::where('tps_id', $tps->id)->whereIn('jenis', $aktifJenis)->where('status','final')->count()
        : 0;
    $draftRekap = max(0, $totalRekap - $finalRekap);
    $belumRekap = max(0, $totalPemiluAktif - $totalRekap);
@endphp

<div class="mb-10">
    <p class="admin-mono admin-muted-soft tracking-[.3em] text-xs mb-2">// KELOMPOK PENYELENGGARA PEMUNGUTAN SUARA</p>
    <h1 class="admin-display text-5xl lg:text-6xl admin-text leading-tight">DASHBOARD KPPS</h1>
    <p class="admin-muted text-lg max-w-2xl mt-2">Input dan laporan data pemungutan suara di TPS.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">TPS</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $tps->nama ?? '-' }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">{{ $tps->desa->nama ?? '-' }}</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">DOKUMEN TERVERIFIKASI</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $terverif }}/{{ $totalPemiluAktif }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">sudah terverifikasi</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">DOKUMEN MASUK</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $uploaded }}/{{ $totalPemiluAktif }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">sudah diupload</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">REKAP DATA</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $finalRekap }}/{{ $totalPemiluAktif }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-2">
            @if($totalPemiluAktif > 0 && $finalRekap === $totalPemiluAktif)
                semua difinalisasi
            @elseif($totalRekap > 0)
                {{ $draftRekap }} draft | {{ $belumRekap }} belum diisi
            @else
                belum ada rekap
            @endif
        </p>
    </div>
</div>

@include('dashboard.partials.election-summary', ['electionSummary' => $electionSummary])
@endsection
