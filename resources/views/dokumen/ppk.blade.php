@extends('layouts.role-dashboard')
@section('title', 'Dokumen Kecamatan')
@section('role_key', 'ppk')
@section('role_title', 'PPK')
@section('role_subtitle', 'Panitia Pemilihan Kecamatan')
@section('role_active', 'dokumen')

@section('role_content')

@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $aktifDokumenJenis = collect(\App\Models\Dokumen::JENIS)
        ->filter(fn($label, $key) => in_array(strtolower($key), $aktifJenis, true));
    $totalJenisAktif = $aktifDokumenJenis->count();
@endphp

{{-- Banner view mode --}}
@if(false && isset($isAdminView) && $isAdminView)
<div class="dark:bg-orange-950 bg-orange-50 border dark:border-orange-900 border-orange-200 px-5 py-3 mb-6 rounded-lg flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-orange-400 text-xs font-semibold">👁 MODE VIEW</span>
        <span class="dark:text-gray-400 text-gray-500 text-xs">Anda melihat dashboard PPK sebagai admin</span>
    </div>
    <a href="{{ route('admin.kecamatan.index') }}"
       onclick="fetch('/clear-view-session')"
       class="text-xs font-semibold dark:text-gray-400 text-gray-500 hover:text-red-500 transition">
        ← Kembali
    </a>
</div>
@endif

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// PPK — Rekap Dokumen</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DOKUMEN KECAMATAN</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">{{ $kecamatan->nama ?? '' }}</p>
</div>

{{-- Filter Desa --}}
<form method="GET" class="flex gap-3 mb-6">
    <select name="desa_id" onchange="this.form.submit()"
            class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-orange-400 focus:ring-0 focus:outline-none">
        <option value="">Pilih Desa</option>
        @foreach($desas as $desa)
        <option value="{{ $desa->id }}" {{ request('desa_id') == $desa->id ? 'selected' : '' }}>
            {{ $desa->nama }}
        </option>
        @endforeach
    </select>
</form>

@forelse($tpsList as $tps)
@php
    $dokByJenis = $tps->dokumens->keyBy('jenis');
    $totalDokumenAktif = $tps->dokumens->whereIn('jenis', $aktifDokumenJenis->keys())->count();
    $totalTerverifikasiAktif = $tps->dokumens
        ->whereIn('jenis', $aktifDokumenJenis->keys())
        ->where('status', 'terverifikasi')
        ->count();
    $persenDokumenAktif = $totalJenisAktif > 0 ? min(100, ($totalTerverifikasiAktif / $totalJenisAktif) * 100) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 mb-4 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200 cursor-pointer hover:dark:bg-gray-750 hover:bg-gray-50 transition"
         onclick="toggleTps({{ $tps->id }})">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $tps->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                {{ $tps->desa->nama }} · {{ $totalTerverifikasiAktif }}/{{ $totalJenisAktif }} terverifikasi · {{ $totalDokumenAktif }} masuk
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-orange-400"
                     style="width:{{ $persenDokumenAktif }}%"></div>
            </div>
            <span id="arrow-{{ $tps->id }}" class="dark:text-gray-500 text-gray-400 text-xs">▾</span>
        </div>
    </div>

    <div id="tps-{{ $tps->id }}" class="hidden">
    
    @foreach($aktifDokumenJenis as $key => $label)
    @php $dok = $dokByJenis[$key] ?? null; @endphp
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full flex-shrink-0"
                 style="background: {{ $dok ? ($dok->status==='terverifikasi'?'#2EC4B6':'#F4A261') : '#9CA3AF' }}"></div>
            <div>
                <p class="text-sm dark:text-gray-200 text-gray-700">{{ $label }}</p>
                @if($dok)
                <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                    Diupload oleh {{ $dok->uploader->name }}
                    @if($dok->verifier) · ✓ {{ $dok->verifier->name }} @endif
                </p>
                @endif
            </div>
        </div>
        @if($dok)
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold"
                  style="color:{{ App\Models\Dokumen::STATUS_COLORS[$dok->status] }};
                         background:{{ App\Models\Dokumen::STATUS_COLORS[$dok->status] }}20;
                         border:1px solid {{ App\Models\Dokumen::STATUS_COLORS[$dok->status] }}40">
                {{ App\Models\Dokumen::STATUS_LABELS[$dok->status] }}
            </span>
            <button onclick="openPreview('{{ route('dokumen.preview', $dok) }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Preview
            </button>
            <a href="{{ route('dokumen.download', $dok) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Unduh
            </a>
        </div>
        @else
        <span class="text-[11px] dark:text-gray-600 text-gray-400">Belum diupload</span>
        @endif
    </div>
    @endforeach
    </div>
</div>
@empty
<div class="text-center py-20 dark:text-gray-600 text-gray-400 text-sm">
    {{ request('desa_id') ? 'Belum ada TPS.' : 'Pilih desa untuk menampilkan dokumen TPS.' }}
</div>
@endforelse

@push('scripts')
<script>
function toggleTps(id) {
    const el    = document.getElementById('tps-' + id);
    const arrow = document.getElementById('arrow-' + id);
    el.classList.toggle('hidden');
    arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
}
</script>
@endpush
@endsection
