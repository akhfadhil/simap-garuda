@extends('layouts.admin')
@section('title', 'Rekapitulasi Data')
@section('admin_active', 'rekap')

@section('admin_content')
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Rekapitulasi</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">REKAPITULASI DATA</h1>
</div>

{{-- Filter Kecamatan --}}
<form method="GET" class="flex gap-3 mb-8 items-center">
    <select name="kecamatan_id" onchange="this.form.submit()"
            class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
        <option value="">Semua Kecamatan</option>
        @foreach($kecamatans as $kec)
        <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>
            {{ $kec->nama }}
        </option>
        @endforeach
    </select>
    @if(request('kecamatan_id'))
    <a href="{{ route('admin.rekap.index') }}"
       class="text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition">
        × Reset
    </a>
    @endif
</form>

<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Pilih Jenis Rekap
</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
@php $aktifJenis = \App\Models\PemiluSetting::aktif(); @endphp
@foreach(\App\Models\RekapHeader::JENIS_LABELS as $jenis => $label)
@if(in_array($jenis, $aktifJenis, true))
@php
    $jenisRekaps = $rekaps[$jenis] ?? collect();
    $totalTps    = \App\Models\Tps::when(
        request('kecamatan_id'),
        fn($q) => $q->whereHas('desa', fn($q2) => $q2->where('kecamatan_id', request('kecamatan_id')))
    )->count();
    $sudahFinal  = $jenisRekaps->where('status','final')->count();
    $sudahIsi    = $jenisRekaps->count();
    $persen      = $totalTps > 0 ? round(($sudahFinal / $totalTps) * 100) : 0;
    $hasFlag     = ($flaggedJenis ?? collect())->has($jenis);
@endphp
<a href="{{ route('admin.rekap.show', $jenis) }}{{ request('kecamatan_id') ? '?kecamatan_id='.request('kecamatan_id') : '' }}"
   class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm hover:shadow-md transition overflow-hidden group block">
    <div class="p-5 border-b dark:border-gray-700 border-gray-200">
        <div class="flex items-start justify-between mb-3">
            <p class="text-sm font-semibold dark:text-gray-200 text-gray-700">{{ $label }}</p>
            <span class="text-lg group-hover:translate-x-0.5 transition-transform dark:text-gray-500 text-gray-400">→</span>
        </div>
        <div class="flex items-center gap-2 mb-2">
        <div class="w-full h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
            <div class="h-1.5 rounded-full bg-red-500 transition-all" style="width:{{ $persen }}%"></div>
        </div>
        @if($hasFlag)
            <span title="Ada data yang perlu diperbaiki" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-red-400 bg-red-500 text-[10px] font-bold leading-none text-white shadow-sm">!</span>
        @endif
        </div>
        <p class="text-[11px] dark:text-gray-500 text-gray-400">
            {{ $sudahFinal }}/{{ $totalTps }} TPS difinalisasi
            @if($sudahIsi > $sudahFinal)
                · {{ $sudahIsi - $sudahFinal }} draft
            @endif
        </p>
    </div>
    <div class="px-5 py-3 flex items-center justify-between">
        <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold uppercase tracking-wider">Lihat Rekap</span>
        @if($sudahFinal === $totalTps && $totalTps > 0)
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">Lengkap</span>
        @elseif($sudahIsi > 0)
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-orange-400/20 text-orange-400 border border-orange-400/40">Sebagian</span>
        @else
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-gray-500/20 dark:text-gray-400 text-gray-500 border border-gray-400/30">Kosong</span>
        @endif
    </div>
</a>
@endif
@endforeach
</div>
@endsection
