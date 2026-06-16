@extends('layouts.role-dashboard')
@section('title', 'Rekapitulasi Data')
@section('role_key', 'kpps')
@section('role_title', 'Saksi TPS')
@section('role_subtitle', 'Saksi Tempat Pemungutan Suara')
@section('role_active', 'rekap')

@section('role_content')
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Saksi TPS - Rekapitulasi</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">ISI DATA REKAPITULASI</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">{{ $tps->nama }} - {{ $tps->desa->nama }}</p>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ session('success') }}
</div>
@endif

<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Pilih Jenis Rekap
</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $canEditRekap = Auth::user()->role === 'kpps';
@endphp

@foreach(\App\Models\RekapHeader::JENIS_LABELS as $jenis => $label)
@if(in_array($jenis, $aktifJenis))
@php
    $rekap = $rekaps[$jenis] ?? null;
    $isDraft = $rekap && $rekap->status === 'draft';
    $isReview = $rekap && $rekap->status === 'perlu_dicek';
    $isFinal = $rekap && $rekap->status === 'final';
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b dark:border-gray-700 border-gray-200 flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold dark:text-gray-300 text-gray-700 uppercase tracking-wider">{{ $label }}</p>
            <p class="text-[10px] dark:text-gray-500 text-gray-400 mt-1">
                @if($isFinal)
                    Difinalisasi {{ $rekap->difinalisasi_at->diffForHumans() }}
                @elseif($isDraft)
                    Tersimpan - belum difinalisasi
                @elseif($isReview)
                    Perlu dicek internal
                @else
                    Belum diisi
                @endif
            </p>
        </div>
        @if($isFinal)
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">Final</span>
        @elseif($isDraft)
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-orange-400/20 text-orange-400 border border-orange-400/40">Draft</span>
        @elseif($isReview)
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-red-500/20 text-red-400 border border-red-500/40">Perlu Dicek</span>
        @else
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold bg-gray-500/20 dark:text-gray-400 text-gray-500 border border-gray-400/30">Kosong</span>
        @endif
    </div>

    @if($rekap)
    <div class="px-5 py-3 border-b dark:border-gray-700 border-gray-100">
        <div class="flex justify-between text-[11px] dark:text-gray-500 text-gray-400 mb-1">
            <span>Total suara Garuda</span>
            <span class="font-semibold dark:text-gray-300 text-gray-600">{{ number_format($rekap->suara_sah) }}</span>
        </div>
        <div class="flex justify-between text-[11px] dark:text-gray-500 text-gray-400">
            <span>Status data</span>
            <span class="font-semibold dark:text-gray-300 text-gray-600">
                {{ $isFinal ? 'Final' : ($isReview ? 'Perlu Dicek' : 'Draft') }}
            </span>
        </div>
    </div>
    @endif

    <div class="p-4 flex gap-2">
        @if(!$canEditRekap || $isFinal)
            <a href="{{ route('rekap.form', $jenis) }}"
               class="flex-1 text-center px-3 py-2 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Lihat
            </a>
        @else
            <a href="{{ route('rekap.form', $jenis) }}"
               class="flex-1 text-center px-3 py-2 rounded-lg text-xs font-semibold bg-sky-500 hover:bg-sky-600 text-white transition">
                {{ $isDraft ? 'Lanjut Isi' : 'Mulai Isi' }}
            </a>
        @endif
    </div>
</div>
@endif
@endforeach
</div>
@endsection
