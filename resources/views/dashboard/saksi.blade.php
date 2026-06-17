@extends('layouts.role-dashboard')
@section('title', 'Dashboard Saksi TPS')
@section('role_key', 'saksi_tps')
@section('role_title', 'Saksi TPS')
@section('role_subtitle', 'Saksi Tempat Pemungutan Suara')
@section('role_active', 'dashboard')

@section('role_content')
@php
    $tps      = $viewTps ?? Auth::user()->tps;
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $totalPemiluAktif = count($aktifJenis);
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
    <p class="admin-mono admin-muted-soft tracking-[.3em] text-xs mb-2">// SAKSI TPS</p>
    <h1 class="admin-display text-5xl lg:text-6xl admin-text leading-tight">DASHBOARD SAKSI TPS</h1>
    <p class="admin-muted text-lg max-w-2xl mt-2">Input dan pantau rekap suara di TPS yang ditugaskan.</p>
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
        <span class="admin-display admin-muted tracking-widest text-[10px]">JENIS PEMILIHAN AKTIF</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $totalPemiluAktif }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">target input rekap</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">REKAP DIINPUT</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $totalRekap }}/{{ $totalPemiluAktif }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">sudah tersimpan</p>
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
