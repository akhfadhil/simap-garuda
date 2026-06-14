@extends('layouts.role-dashboard')
@section('title', 'Dashboard Kordes')
@section('role_key', 'pps')
@section('role_title', 'Kordes')
@section('role_subtitle', 'Koordinator Desa')
@section('role_active', 'dashboard')

@section('role_content')
@php
    $desa        = $viewDesa ?? Auth::user()->desa;
    $tpsList     = $desa ? $desa->tps : collect();
    $totalTps    = $tpsList->count();
    $tpsIds      = $tpsList->pluck('id');
    $aktifJenis  = \App\Models\PemiluSetting::aktif();
    $totalPemiluAktif = count($aktifJenis);
    $targetPemiluTps  = $totalTps * $totalPemiluAktif;

    $totalRekapFinal = \App\Models\RekapHeader::select('tps_id')
                        ->where('status', 'final')
                        ->whereIn('tps_id', $tpsIds)
                        ->whereIn('jenis', $aktifJenis)
                        ->groupBy('tps_id')
                        ->havingRaw('COUNT(DISTINCT jenis) = ?', [$totalPemiluAktif])
                        ->count();
    $persenRekap = $totalTps > 0 ? min(100, round(($totalRekapFinal / $totalTps) * 100)) : 0;
@endphp

<div class="mb-10">
    <p class="admin-mono admin-muted-soft tracking-[.3em] text-xs mb-2">// KOORDINATOR DESA</p>
    <h1 class="admin-display text-5xl lg:text-6xl admin-text leading-tight">DASHBOARD KORDES</h1>
    <p class="admin-muted text-lg max-w-2xl mt-2">Pantau progress input dan finalisasi rekap saksi di wilayah desa.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">DESA</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $desa->nama ?? '-' }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">{{ $desa->kecamatan->nama ?? '-' }}</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">JUMLAH TPS</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ $totalTps }}</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase mt-2">di desa ini</p>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <div class="flex justify-between items-start mb-4">
            <span class="admin-display admin-muted tracking-widest text-[10px]">TARGET REKAP</span>
            <span class="admin-muted text-[10px] admin-mono">TPS x {{ $totalPemiluAktif }} AKTIF</span>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ number_format($targetPemiluTps) }}</span>
            <span class="admin-mono admin-muted-soft text-[11px] uppercase">baris rekap</span>
        </div>
    </div>

    <div class="admin-glass p-5 rounded-lg">
        <span class="admin-display admin-muted tracking-widest text-[10px]">REKAP FINALISASI</span>
        <div class="mt-4 flex items-baseline gap-2">
            <span class="admin-display text-4xl role-accent">{{ number_format($totalRekapFinal) }}/{{ number_format($totalTps) }}</span>
            <span class="admin-mono admin-muted-soft text-[11px] uppercase">{{ $persenRekap }}%</span>
        </div>
        <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">TPS final semua pemilu aktif</p>
        <div class="admin-surface-strong mt-4 h-1 w-full overflow-hidden rounded-full">
            <div class="h-full" style="width:{{ $persenRekap }}%; background: var(--role-accent)"></div>
        </div>
    </div>
</div>

@include('dashboard.partials.election-summary', ['electionSummary' => $electionSummary])
@endsection
