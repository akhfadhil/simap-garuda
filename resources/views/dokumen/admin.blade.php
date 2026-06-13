@extends('layouts.admin')
@section('title', 'Semua Dokumen')
@section('admin_active', 'dokumen')

@section('admin_content')

@php
    $aktifJenis = \App\Models\PemiluSetting::aktif();
    $aktifDokumenJenis = collect(\App\Models\Dokumen::JENIS)
        ->filter(fn($label, $key) => in_array(strtolower($key), $aktifJenis, true));
    $totalJenisAktif = $aktifDokumenJenis->count();
    $canManageDocuments = Auth::user()->role === 'admin';
@endphp

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Semua Dokumen</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">REKAP DOKUMEN</h1>
</div>

@if(session('backup_result'))
<div class="bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ session('backup_result') }}
</div>
@endif

@if($canManageDocuments)
{{-- Tools Dokumen --}}
<div class="dark:bg-gray-800 bg-white rounded-xl p-6 border-l-4 border border-l-amber-500 dark:border-gray-700 border-gray-200 shadow-sm mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-3 font-semibold">// Tools Dokumen</p>
    <p class="font-semibold text-sm mb-1 dark:text-gray-100 text-gray-800">Backup Dokumen</p>
    <p class="text-xs dark:text-gray-500 text-gray-500 leading-relaxed mb-4">Pindahkan file PDF dokumen yang sudah terverifikasi ke folder backup eksternal.</p>
    <form method="POST" action="{{ route('admin.tools.backup') }}">
        @csrf
        <button class="px-4 py-2 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition">
            Jalankan Backup
        </button>
    </form>
</div>
@endif

{{-- Filter --}}
<form method="GET" class="flex gap-3 mb-8 flex-wrap">
    <select name="kecamatan_id" onchange="this.form.submit()"
            class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
        <option value="">Pilih Kecamatan</option>
        @foreach($kecamatans as $kec)
        <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
        @endforeach
    </select>
    @if($desas->count())
    <select name="desa_id" onchange="this.form.submit()"
            class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
        <option value="">Pilih Desa</option>
        @foreach($desas as $d)
        <option value="{{ $d->id }}" {{ request('desa_id') == $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
        @endforeach
    </select>
    @endif
</form>

@if(!$selectedKecamatanId)
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-6 mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Dokumen tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
        Pilih kecamatan untuk melihat dokumen PPK, lalu pilih desa untuk melihat dokumen TPS.
    </p>
</div>
@endif

{{-- ── Dokumen Kecamatan (PPK) ── --}}
@if($dokumenKecamatan->count())
<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Dokumen Kecamatan (PPK)
</p>

@foreach($dokumenKecamatan as $kecId => $dokList)
@php
    $kecNama = $dokList->first()->kecamatan->nama;
    $totalDokumenAktif = $dokList->whereIn('jenis', $aktifDokumenJenis->keys())->count();
    $totalTerverifikasiAktif = $dokList
        ->whereIn('jenis', $aktifDokumenJenis->keys())
        ->where('status', 'terverifikasi')
        ->count();
    $persenDokumenAktif = $totalJenisAktif > 0 ? min(100, ($totalTerverifikasiAktif / $totalJenisAktif) * 100) : 0;
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 mb-4 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200 cursor-pointer hover:dark:bg-gray-750 hover:bg-gray-50 transition"
         onclick="toggleTps('kec-{{ $kecId }}')">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $kecNama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $totalTerverifikasiAktif }}/{{ $totalJenisAktif }} terverifikasi · {{ $totalDokumenAktif }} masuk · PPK</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-red-500" style="width:{{ $persenDokumenAktif }}%"></div>
            </div>
            <span id="arrow-kec-{{ $kecId }}" class="dark:text-gray-500 text-gray-400 text-xs">▾</span>
        </div>
    </div>

    <div id="tps-kec-{{ $kecId }}">
    @foreach($aktifDokumenJenis as $key => $label)
    @php $dok = $dokList->firstWhere('jenis', $key); @endphp
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full flex-shrink-0"
                     style="background: {{ $dok ? App\Models\Dokumen::STATUS_COLORS[$dok->status] : '#9CA3AF' }}"></div>
                <div>
                    <p class="text-sm dark:text-gray-200 text-gray-700">{{ $label }}</p>
                    @if($dok)
                    <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                        Diupload oleh {{ $dok->uploader->name }}
                        @if($dok->verifier) · ✓ {{ $dok->verifier->name }} @endif
                    </p>
                    @else
                    <p class="text-[11px] dark:text-gray-600 text-gray-400 mt-0.5">Belum diupload</p>
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
                @if($canManageDocuments && $dok->status === 'menunggu_verifikasi')
                <button onclick="openTolakModal('{{ route('dokumen.verifikasi.admin', $dok) }}', '{{ $label }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-400/40 text-red-400 hover:bg-red-500/10 transition">
                    ✗ Tolak
                </button>
                <form method="POST" action="{{ route('dokumen.verifikasi.admin', $dok) }}">
                    @csrf
                    <input type="hidden" name="aksi" value="terverifikasi">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition">
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
@endforeach
@endif

{{-- ── Dokumen TPS (KPPS) ── --}}
<p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-4 pb-3 border-b dark:border-gray-800 border-gray-200 font-semibold">
    // Dokumen TPS (KPPS)
</p>

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
                {{ $tps->desa->nama }} · {{ $tps->desa->kecamatan->nama }} · {{ $totalTerverifikasiAktif }}/{{ $totalJenisAktif }} terverifikasi · {{ $totalDokumenAktif }} masuk
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-red-500" style="width:{{ $persenDokumenAktif }}%"></div>
            </div>
            <span id="arrow-{{ $tps->id }}" class="dark:text-gray-500 text-gray-400 text-xs">▾</span>
        </div>
    </div>

    <div id="tps-{{ $tps->id }}" class="hidden">
    @foreach($aktifDokumenJenis as $key => $label)
    @php $dok = $dokByJenis[$key] ?? null; @endphp
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full flex-shrink-0"
                     style="background: {{ $dok ? App\Models\Dokumen::STATUS_COLORS[$dok->status] : '#9CA3AF' }}"></div>
                <div>
                    <p class="text-sm dark:text-gray-200 text-gray-700">{{ $label }}</p>
                    @if($dok)
                    <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">
                        Diupload oleh {{ $dok->uploader->name }}
                        @if($dok->verifier) · ✓ {{ $dok->verifier->name }} @endif
                    </p>
                    @else
                    <p class="text-[11px] dark:text-gray-600 text-gray-400 mt-0.5">Belum diupload</p>
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
                @if($canManageDocuments && $dok->status === 'menunggu_verifikasi')
                <button onclick="openTolakModal('{{ route('dokumen.verifikasi.admin', $dok) }}', '{{ $label }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-400/40 text-red-400 hover:bg-red-500/10 transition">
                    ✗ Tolak
                </button>
                <form method="POST" action="{{ route('dokumen.verifikasi.admin', $dok) }}">
                    @csrf
                    <input type="hidden" name="aksi" value="terverifikasi">
                    <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition">
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
    @if(!$selectedKecamatanId)
        Pilih kecamatan untuk mulai menampilkan dokumen.
    @elseif(!$selectedDesaId)
        Pilih desa untuk menampilkan dokumen TPS.
    @else
        Belum ada data.
    @endif
</div>
@endforelse

@if($canManageDocuments)
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
@endif

@push('scripts')
<script>
function toggleTps(id) {
    const el    = document.getElementById('tps-' + id);
    const arrow = document.getElementById('arrow-' + id);
    if (!el) return;
    el.classList.toggle('hidden');
    arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
}
function openTolakModal(action, label) {
    if (!document.getElementById('modal-tolak-form')) return;
    document.getElementById('modal-tolak-form').action = action;
    document.getElementById('modal-tolak-label').textContent = label;
    document.getElementById('modal-tolak').classList.remove('hidden');
}
function closeTolakModal() {
    if (!document.getElementById('modal-tolak')) return;
    document.getElementById('modal-tolak').classList.add('hidden');
    document.getElementById('modal-tolak-form').querySelector('textarea').value = '';
}
</script>
@endpush

@endsection
