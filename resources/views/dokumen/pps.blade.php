@extends('layouts.role-dashboard')
@section('title', 'Verifikasi Dokumen')
@section('role_key', 'pps')
@section('role_title', 'PPS')
@section('role_subtitle', 'Panitia Pemungutan Suara')
@section('role_active', 'verifikasi')

@section('role_content')

@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $aktifDokumenJenis = collect(\App\Models\Dokumen::JENIS)
        ->filter(fn($label, $key) => in_array(strtolower($key), $aktifJenis, true));
    $totalJenisAktif = $aktifDokumenJenis->count();
@endphp

@if(false && isset($isAdminView) && $isAdminView)
<div class="dark:bg-teal-950 bg-teal-50 border dark:border-teal-900 border-teal-200 px-5 py-3 mb-6 rounded-lg flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-teal-400 text-xs font-semibold">👁 MODE VIEW</span>
        <span class="dark:text-gray-400 text-gray-500 text-xs">
            @if(Auth::user()->role === 'admin') Anda melihat dashboard PPS sebagai admin
            @elseif(Auth::user()->role === 'ppk') Anda melihat dashboard PPS sebagai PPK
            @endif
        </span>
    </div>
    <a href="{{ Auth::user()->role === 'admin' ? route('admin.desa.index') : route('ppk.data-pps') }}"
       onclick="fetch('/clear-view-session')"
       class="text-xs font-semibold dark:text-gray-400 text-gray-500 hover:text-red-500 transition">← Kembali</a>
</div>
@endif

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// PPS — Verifikasi Dokumen</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">DOKUMEN TPS</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">
        {{ isset($desa) ? $desa->nama . ' · ' . $desa->kecamatan->nama : (Auth::user()->desa->nama ?? '') }}
    </p>
</div>

@if(session('success'))
<div class="bg-teal-50 dark:bg-teal-950 border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

<form method="GET" class="flex gap-3 mb-6">
    <select name="tps_id" onchange="this.form.submit()"
            class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-teal-400 focus:ring-0 focus:outline-none">
        <option value="">Pilih TPS</option>
        @foreach($tpsOptions as $tpsOption)
        <option value="{{ $tpsOption->id }}" {{ (int) $selectedTpsId === (int) $tpsOption->id ? 'selected' : '' }}>
            {{ $tpsOption->nama }}
        </option>
        @endforeach
    </select>
</form>

@forelse($tpsList as $tps)
@php
    $dokByJenis = $tps->dokumens->keyBy('jenis');
    $totalDok   = $tps->dokumens->whereIn('jenis', $aktifDokumenJenis->keys())->count();
    $verified   = $tps->dokumens
                    ->whereIn('jenis', $aktifDokumenJenis->keys())
                    ->where('status', 'terverifikasi')
                    ->count();
    $persenDokumen = $totalJenisAktif > 0 ? min(100, ($verified / $totalJenisAktif) * 100) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 mb-4 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200 cursor-pointer dark:hover:bg-gray-750 hover:bg-gray-50 transition"
         onclick="toggleTps({{ $tps->id }})">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $tps->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $verified }}/{{ $totalJenisAktif }} dokumen terverifikasi</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-32 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full transition-all bg-teal-400" style="width:{{ $persenDokumen }}%"></div>
            </div>
            <span class="text-[11px] dark:text-gray-500 text-gray-400">{{ $verified }}/{{ $totalJenisAktif }}</span>
            <span class="dark:text-gray-500 text-gray-400 text-xs" id="arrow-{{ $tps->id }}">▸</span>
        </div>
    </div>

    <div id="tps-{{ $tps->id }}" class="hidden">
    
    @foreach($aktifDokumenJenis as $key => $label)
    @php $dok = $dokByJenis[$key] ?? null; @endphp
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0">
        <div class="flex items-center justify-between flex-wrap gap-3">
            {{-- Info dokumen --}}
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full flex-shrink-0"
                     style="background: {{ $dok ? App\Models\Dokumen::STATUS_COLORS[$dok->status] : '#9CA3AF' }}"></div>
                <div>
                    <p class="text-sm font-medium dark:text-gray-200 text-gray-700">{{ $label }}</p>
                    @if($dok)
                    <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                        {{ $dok->file_name }} · {{ $dok->updated_at->diffForHumans() }}
                        @if($dok->verifier) · ✓ {{ $dok->verifier->name }} @endif
                    </p>
                    @else
                    <p class="text-[11px] dark:text-gray-600 text-gray-400 mt-0.5">Belum diupload</p>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
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
                @if($dok->status === 'menunggu_verifikasi' && (!isset($isAdminView) || !$isAdminView))
                <button onclick="openTolakModal('{{ route('dokumen.verifikasi', $dok) }}', '{{ $label }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-400/40 text-red-400 hover:bg-red-500/10 transition">
                    ✗ Tolak
                </button>
                <form method="POST" action="{{ route('dokumen.verifikasi', $dok) }}">
                    @csrf
                    <input type="hidden" name="aksi" value="terverifikasi">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-500 hover:bg-teal-600 text-white transition">
                        ✓ Verifikasi
                    </button>
                </form>
                @endif
            </div>
            @endif
        </div>

        {{-- Komentar penolakan --}}
        @if($dok && $dok->status === 'ditolak' && $dok->komentar)
        <div class="mt-3 flex items-start gap-2 bg-red-500/10 border border-red-400/30 rounded-lg px-4 py-2.5">
            <span class="text-red-400 text-xs mt-0.5">✗</span>
            <div>
                <p class="text-[10px] text-red-400 font-semibold uppercase tracking-wider mb-0.5">Alasan Penolakan</p>
                <p class="text-xs dark:text-gray-300 text-gray-600">{{ $dok->komentar }}</p>
            </div>
        </div>
        @endif
    </div>
    @endforeach
    </div>
</div>
@empty
<div class="text-center py-20 dark:text-gray-600 text-gray-400 text-sm">
    {{ $selectedTpsId ? 'Belum ada TPS di desa ini.' : 'Pilih TPS untuk menampilkan dokumen.' }}
</div>
@endforelse

{{-- Modal Tolak --}}
<div id="modal-tolak" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeTolakModal()"></div>
    <div class="relative dark:bg-gray-800 bg-white rounded-xl shadow-xl w-full max-w-md p-6 border dark:border-gray-700 border-gray-200">
        <h3 class="font-semibold dark:text-gray-100 text-gray-800 mb-1">Tolak Dokumen</h3>
        <p id="modal-tolak-label" class="text-xs dark:text-gray-500 text-gray-400 mb-4"></p>
        <form id="modal-tolak-form" method="POST">
            @csrf
            <input type="hidden" name="aksi" value="ditolak">
            <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 mb-1.5 uppercase tracking-wider">
                Alasan Penolakan <span class="text-red-400">*</span>
            </label>
            <textarea name="komentar" rows="3" required
                      placeholder="Contoh: Dokumen terlalu blur, tidak dapat dibaca..."
                      class="w-full dark:bg-gray-700 bg-gray-50 border dark:border-gray-600 border-gray-300 rounded-lg px-3 py-2.5 text-sm dark:text-gray-200 text-gray-700 focus:border-red-400 focus:ring-0 focus:outline-none resize-none"></textarea>
            <div class="flex gap-2 mt-4 justify-end">
                <button type="button" onclick="closeTolakModal()"
                        class="px-4 py-2 text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 text-xs font-semibold bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                    ✗ Tolak Dokumen
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleTps(id) {
    const el    = document.getElementById('tps-' + id);
    const arrow = document.getElementById('arrow-' + id);
    el.classList.toggle('hidden');
    arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
}
function openTolakModal(action, label) {
    document.getElementById('modal-tolak-form').action = action;
    document.getElementById('modal-tolak-label').textContent = label;
    document.getElementById('modal-tolak').classList.remove('hidden');
}
function closeTolakModal() {
    document.getElementById('modal-tolak').classList.add('hidden');
    document.getElementById('modal-tolak-form').querySelector('textarea').value = '';
}
</script>
@endpush
@endsection
