@extends('layouts.app')
@section('title', 'Isi Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')
@php
    $isAdminRekapEdit = Auth::user()->role === 'admin';
    $isCoordinatorRekapEdit = in_array(Auth::user()->role, ['pps', 'ppk'], true);
    $backUrl = $isAdminRekapEdit
        ? (session('admin_rekap_return_url') ?: route('admin.rekap.show', $jenis))
        : route('rekap.index');
    $canEditRekap = in_array(Auth::user()->role, ['kpps', 'pps', 'ppk'], true) || $isAdminRekapEdit;
    $isFinal = $rekap && $rekap->status === 'final';
    $readOnly = !$canEditRekap || ($isFinal && !$isAdminRekapEdit);
    $statusLabels = [
        'draft' => 'Draft',
        'perlu_dicek' => 'Perlu Dicek',
        'final' => 'Final',
    ];
@endphp

<div class="mb-6">
    <a href="{{ $backUrl }}"
       class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
        &larr; Kembali
    </a>
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
        // SAKSI TPS - {{ $tps->nama }} / {{ $tps->desa->nama }}
    </p>

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="font-display text-4xl tracking-[2px] text-sky-300">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
        <div class="flex items-center gap-2">
            @if($rekap)
            @php
                $statusClass = match($rekap->status) {
                    'final' => 'bg-teal-500/20 text-teal-400 border-teal-500/40',
                    'perlu_dicek' => 'bg-red-500/20 text-red-400 border-red-500/40',
                    default => 'bg-orange-400/20 text-orange-400 border-orange-400/40',
                };
            @endphp
            <span class="px-4 py-1.5 rounded-lg text-xs font-semibold border {{ $statusClass }}">
                {{ $statusLabels[$rekap->status] ?? ucfirst($rekap->status) }}
            </span>
            @endif
            @if($rekap && $rekap->status === 'final')
            <span class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">
                Sudah Difinalisasi
            </span>
            @endif
            @if($rekap)
            <a href="{{ route('rekap.export', $jenis) }}"
            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-semibold bg-sky-500 hover:bg-sky-600 text-white transition">
                Export Excel
            </a>
            @endif
        </div>
    </div>
</div>

@if(session('error'))
<div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ session('error') }}
</div>
@endif

@if($isAdminRekapEdit)
<div class="dark:bg-red-950 bg-red-50 border dark:border-red-900 border-red-200 px-5 py-3 mb-6 rounded-lg">
    <p class="text-xs font-semibold text-red-500">Mode koreksi admin sementara. Perubahan langsung mengubah data suara TPS ini.</p>
</div>
@elseif($isCoordinatorRekapEdit)
<div class="dark:bg-sky-950 bg-sky-50 border dark:border-sky-900 border-sky-200 px-5 py-3 mb-6 rounded-lg">
    <p class="text-xs font-semibold text-sky-500">Mode koreksi koordinator. Perubahan hanya berlaku untuk TPS dalam scope wilayah Anda.</p>
</div>
@elseif(!$canEditRekap)
<div class="dark:bg-orange-950 bg-orange-50 border dark:border-orange-900 border-orange-200 px-5 py-3 mb-6 rounded-lg">
    <p class="text-xs font-semibold text-orange-500">Mode lihat saja. Rekapitulasi data hanya bisa diubah oleh Saksi TPS, Kordes, Korcam, atau Admin Partai sesuai scope.</p>
</div>
@endif

<form method="POST" action="{{ route('rekap.store', $jenis) }}" id="rekap-form">
@csrf

@if($isAdminRekapEdit)
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-200 dark:bg-gray-700/50 bg-gray-50">
        <p class="text-xs font-bold dark:text-gray-300 text-gray-700 uppercase tracking-wider">Status Internal Partai</p>
    </div>
    <div class="p-6 grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-4">
        <div>
            <label for="status_internal" class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Status</label>
            <select id="status_internal" name="status_internal"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-3 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                @foreach($statusLabels as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" {{ old('status_internal', $rekap->status ?? 'draft') === $statusValue ? 'selected' : '' }}>
                        {{ $statusLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="catatan_internal" class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Catatan Internal</label>
            <textarea id="catatan_internal" name="catatan_internal" rows="3"
                      placeholder="Contoh: angka caleg nomor 2 perlu dikonfirmasi ulang ke saksi."
                      class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-3 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">{{ old('catatan_internal', $rekap->catatan_internal ?? '') }}</textarea>
        </div>
    </div>
</div>
@elseif($rekap?->catatan_internal)
<div class="dark:bg-red-950 bg-red-50 border dark:border-red-900 border-red-200 px-5 py-3 mb-6 rounded-lg">
    <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Catatan Internal</p>
    <p class="text-sm dark:text-red-100 text-red-700">{{ $rekap->catatan_internal }}</p>
</div>
@endif

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-200 dark:bg-gray-700/50 bg-gray-50">
        <p class="text-xs font-bold dark:text-gray-300 text-gray-700 uppercase tracking-wider">Perolehan Suara Partai Garuda</p>
    </div>
    <div class="p-6">
        @if($data['partais']->isEmpty())
        <div class="text-center py-8 dark:text-gray-500 text-gray-400 text-sm">
            Belum ada data Partai Garuda. Minta admin untuk menginput terlebih dahulu.
        </div>
        @else
        <div class="space-y-4">
        @foreach($data['partais'] as $partai)
        <div class="border dark:border-gray-700 border-gray-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 dark:bg-gray-700/50 bg-gray-50 border-b dark:border-gray-700 border-gray-200 gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-orange-400 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                        {{ $partai->nomor_urut }}
                    </span>
                    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800 truncate">{{ $partai->nama_partai }}</p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <label class="text-xs dark:text-gray-400 text-gray-500 font-medium">Suara Partai</label>
                    <input type="number" name="suara_partai[{{ $partai->id }}]" min="0"
                           value="{{ old('suara_partai.'.$partai->id, $data['suara_partai'][$partai->id] ?? 0) }}"
                           {{ $readOnly ? 'disabled' : '' }}
                           class="w-28 dark:bg-gray-900 bg-white border dark:border-gray-600 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-1.5 text-sm rounded-lg focus:border-sky-400 focus:ring-0 focus:outline-none text-right {{ $readOnly ? 'opacity-60 cursor-not-allowed' : '' }}">
                </div>
            </div>

            @foreach($partai->calegs as $caleg)
            <div class="flex items-center justify-between px-5 py-3 border-b dark:border-gray-700 border-gray-100 last:border-0 gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-xs dark:text-gray-500 text-gray-400 w-5 text-center flex-shrink-0">{{ $caleg->nomor_urut }}</span>
                    <p class="text-sm dark:text-gray-300 text-gray-600 truncate">{{ $caleg->nama_caleg }}</p>
                </div>
                <input type="number" name="suara_caleg[{{ $caleg->id }}]" min="0"
                       value="{{ old('suara_caleg.'.$caleg->id, $data['suara_caleg'][$caleg->id] ?? 0) }}"
                       {{ $readOnly ? 'disabled' : '' }}
                       class="w-28 flex-shrink-0 dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-1.5 text-sm rounded-lg focus:border-sky-400 focus:ring-0 focus:outline-none text-right {{ $readOnly ? 'opacity-60 cursor-not-allowed' : '' }}">
            </div>
            @endforeach

            <div class="flex items-center justify-between px-5 py-3 border-t dark:border-gray-600 border-gray-300 dark:bg-gray-700/50 bg-gray-50">
                <p class="text-xs font-bold dark:text-gray-300 text-gray-700 uppercase tracking-wider">
                    Total suara {{ $partai->nama_partai }}
                    <span class="text-[10px] font-normal dark:text-gray-500 text-gray-400 normal-case tracking-normal">(partai + seluruh caleg)</span>
                </p>
                <div class="w-28 dark:bg-gray-900 bg-white border dark:border-gray-600 border-gray-300 px-3 py-1.5 text-sm font-bold text-orange-400 rounded-lg text-right partai-subtotal"
                     data-partai-id="{{ $partai->id }}">
                    0
                </div>
            </div>
        </div>
        @endforeach
        </div>
        @endif
    </div>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-6 overflow-hidden">
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-200 dark:bg-gray-700/50 bg-gray-50">
        <p class="text-xs font-bold dark:text-gray-300 text-gray-700 uppercase tracking-wider">Total Suara Garuda</p>
    </div>
    <div class="p-6">
        <div class="flex items-center gap-4">
            <p class="flex-1 text-sm dark:text-gray-300 text-gray-700">
                Total suara Partai Garuda dan seluruh caleg Garuda
                <span class="text-[10px] dark:text-gray-600 text-gray-400 font-normal">(otomatis dari input di atas)</span>
            </p>
            <div id="display-suara-garuda"
                 class="w-36 dark:bg-gray-900 bg-gray-100 border dark:border-gray-700 border-gray-300 px-3 py-2 text-sm font-bold dark:text-sky-400 text-sky-600 rounded-lg text-right">
                0
            </div>
        </div>
    </div>
</div>

@if($isAdminRekapEdit)
<div class="flex gap-3">
    <button type="submit"
            class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-xl text-sm transition">
        Simpan Perubahan Admin
    </button>
</div>
@elseif($canEditRekap && !$isFinal)
<div class="flex gap-3">
    <button type="submit"
            class="flex-1 bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 rounded-xl text-sm transition">
        Simpan Draft
    </button>
    <button type="button" onclick="confirmFinalisasi()"
            class="flex-1 bg-teal-500 hover:bg-teal-600 text-white font-semibold py-3 rounded-xl text-sm transition">
        Simpan & Finalisasi
    </button>
</div>
@else
<div class="dark:bg-gray-800 bg-gray-50 rounded-xl border dark:border-gray-700 border-gray-200 p-4 text-center">
    <p class="text-sm dark:text-gray-400 text-gray-500">
        {{ $isFinal ? 'Data sudah difinalisasi dan tidak bisa diubah.' : 'Mode lihat saja. Data hanya bisa diubah oleh Saksi TPS, Kordes, Korcam, atau Admin Partai sesuai scope.' }}
    </p>
</div>
@endif

</form>

@push('scripts')
<script>
function updateSuaraGaruda() {
    let totalGaruda = 0;

    document.querySelectorAll('.partai-subtotal').forEach(elSubtotal => {
        const partaiId = elSubtotal.dataset.partaiId;
        let subtotal = 0;

        const inpPartai = document.querySelector(`[name="suara_partai[${partaiId}]"]`);
        subtotal += parseInt(inpPartai?.value) || 0;

        const containerPartai = elSubtotal.closest('.border.dark\\:border-gray-700');
        if (containerPartai) {
            containerPartai.querySelectorAll('[name^="suara_caleg["]').forEach(inp => {
                subtotal += parseInt(inp.value) || 0;
            });
        }

        elSubtotal.textContent = subtotal.toLocaleString('id-ID');
        totalGaruda += subtotal;
    });

    const elTotal = document.getElementById('display-suara-garuda');
    if (elTotal) elTotal.textContent = totalGaruda.toLocaleString('id-ID');
}

document.querySelectorAll('input[type="number"]').forEach(inp => {
    inp.addEventListener('input', updateSuaraGaruda);
});

updateSuaraGaruda();

async function confirmFinalisasi() {
    const ok = await confirmFinal();
    if (ok) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'finalisasi';
        input.value = '1';
        document.getElementById('rekap-form').appendChild(input);
        document.getElementById('rekap-form').submit();
    }
}

function confirmFinal() {
    return new Promise((resolve) => {
        const modal = document.getElementById('toast-final');
        const btnOk = document.getElementById('toast-final-ok');
        const btnCancel = document.getElementById('toast-final-cancel');
        const backdrop = document.getElementById('toast-final-backdrop');

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        function close(result) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            btnOk.removeEventListener('click', onOk);
            btnCancel.removeEventListener('click', onCancel);
            backdrop.removeEventListener('click', onCancel);
            resolve(result);
        }

        const onOk = () => close(true);
        const onCancel = () => close(false);

        btnOk.addEventListener('click', onOk);
        btnCancel.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);
    });
}
</script>

<div id="toast-final" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" id="toast-final-backdrop"></div>
    <div class="relative dark:bg-gray-800 bg-white rounded-2xl shadow-2xl border dark:border-gray-700 border-gray-200 w-full max-w-sm p-6">
        <div class="flex items-start gap-4 mb-5">
            <div class="w-10 h-10 rounded-full bg-teal-500/15 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold dark:text-gray-100 text-gray-800 text-sm mb-1">Konfirmasi Finalisasi</p>
                <p class="text-xs dark:text-gray-400 text-gray-500 leading-relaxed">
                    Data rekap akan difinalisasi dan <span class="font-semibold text-teal-500">tidak bisa diubah</span> setelah ini.
                    Pastikan semua data sudah benar sebelum melanjutkan.
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="toast-final-cancel"
                    class="flex-1 px-4 py-2.5 rounded-xl text-xs font-semibold border dark:border-gray-600 border-gray-300
                           dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Batal
            </button>
            <button id="toast-final-ok"
                    class="flex-1 px-4 py-2.5 rounded-xl text-xs font-semibold bg-teal-500 hover:bg-teal-600 text-white transition">
                Ya, Finalisasi
            </button>
        </div>
    </div>
</div>
@endpush
@endsection
