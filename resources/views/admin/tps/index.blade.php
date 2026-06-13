@extends('layouts.admin')
@section('title', 'Kelola TPS')
@section('admin_active', 'tps')

@section('admin_content')
@php
    $hasFilter = $selectedKecamatanId || $selectedDesaId;
@endphp

<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin - Wilayah</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">KELOLA TPS</h1>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form Bulk Add --}}
    <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Bulk Add TPS</p>
        <form method="POST" action="{{ route('admin.tps.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                <select id="bulk-kecamatan" name="kecamatan_id_filter" onchange="loadBulkDesa(this.value, true)"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ old('kecamatan_id_filter') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider">Jumlah TPS Per Desa</label>
                    <span class="text-[10px] dark:text-gray-600 text-gray-400 uppercase tracking-wider whitespace-nowrap">Kosongkan jika 0</span>
                </div>
                @error('jumlah_tps') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div id="bulk-desa-empty" class="rounded-lg border border-dashed dark:border-gray-700 border-gray-300 px-4 py-8 text-center text-xs dark:text-gray-500 text-gray-400">
                    Pilih kecamatan untuk menampilkan desa.
                </div>

                <div id="bulk-desa-list" class="hidden max-h-[360px] overflow-y-auto rounded-lg border dark:border-gray-700 border-gray-200 divide-y dark:divide-gray-700 divide-gray-100">
                    @foreach($kecamatans as $kec)
                        @foreach($kec->desas as $desa)
                        <div class="bulk-desa-row hidden items-center gap-3 px-3 py-2.5 dark:bg-gray-900 bg-gray-50" data-kec="{{ $kec->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium dark:text-gray-100 text-gray-800 truncate">{{ $desa->nama }}</p>
                                <p class="text-[10px] dark:text-gray-600 text-gray-400 uppercase tracking-wider">{{ $kec->nama }}</p>
                            </div>
                            <input type="number" name="jumlah_tps[{{ $desa->id }}]" min="0" max="999"
                                   value="{{ old('jumlah_tps.' . $desa->id) }}"
                                   placeholder="0"
                                   class="w-24 dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                        </div>
                        @endforeach
                    @endforeach
                </div>
                @error('jumlah_tps.*') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                <p class="text-[11px] dark:text-gray-600 text-gray-400 mt-2">TPS yang sudah ada dilewati otomatis.</p>
            </div>

            <button class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                Buat TPS
            </button>
        </form>
    </div>

    {{-- Daftar TPS --}}
    <div class="lg:col-span-2 dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden">

        {{-- Filter --}}
        <div class="p-4 border-b dark:border-gray-700 border-gray-200">
            <form method="GET" id="filter-form" class="flex items-center gap-3 flex-wrap">
                <select name="kecamatan_id" onchange="updateDesaFilter(this.value)"
                        class="dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ $selectedKecamatanId == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
                <select name="desa_id" id="filter-desa" onchange="this.form.submit()"
                        class="dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Semua Desa</option>
                    @foreach($kecamatans as $kec)
                        @foreach($kec->desas as $desa)
                        <option value="{{ $desa->id }}" data-kec="{{ $kec->id }}"
                                class="filter-desa-option"
                                {{ $selectedDesaId == $desa->id ? 'selected' : '' }}>
                            {{ $desa->nama }}
                        </option>
                        @endforeach
                    @endforeach
                </select>
                <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold uppercase tracking-wider">
                    {{ $filteredTps->count() }} TPS
                </span>
            </form>
        </div>

        {{-- List --}}
        @if(! $hasFilter)
        <div class="px-6 py-14 text-center dark:text-gray-600 text-gray-400 text-sm">
            Pilih kecamatan atau desa untuk menampilkan TPS.
        </div>
        @elseif($filteredTps->isEmpty())
        <div class="px-6 py-14 text-center dark:text-gray-600 text-gray-400 text-sm">
            Belum ada TPS pada filter ini.
        </div>
        @else
            @foreach($filteredTps as $tps)
            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50 transition group">
                <div class="min-w-0">
                    <p class="text-sm font-medium dark:text-gray-100 text-gray-800">{{ $tps->nama }}</p>
                    <p class="text-xs dark:text-gray-500 text-gray-400 mt-0.5 truncate">
                        {{ $tps->desa->nama }} - {{ $tps->desa->kecamatan->nama }}
                    </p>
                </div>
                <div class="flex gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition">
                    <a href="{{ route('admin.tps.view', $tps) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-medium border border-teal-400 text-teal-400 hover:bg-teal-400 hover:text-white transition">
                        View
                    </a>
                    <button onclick="openEdit({{ $tps->id }}, '{{ addslashes($tps->nama) }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('admin.tps.destroy', $tps) }}"
                          onsubmit="return confirm('Hapus {{ addslashes($tps->nama) }}?')">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-400 text-red-400 hover:bg-red-500 hover:text-white transition">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Modal Edit --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 p-8 w-full max-w-md shadow-2xl">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Edit Nama TPS</p>
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            <div class="mb-5">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama TPS</label>
                <input type="text" id="edit-nama" name="nama" required maxlength="100"
                       class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeEdit()"
                        class="flex-1 border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 py-2.5 rounded-lg text-sm font-medium dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function loadBulkDesa(kecId, clearHidden = false) {
    const list = document.getElementById('bulk-desa-list');
    const empty = document.getElementById('bulk-desa-empty');
    let shown = 0;

    document.querySelectorAll('.bulk-desa-row').forEach(row => {
        const isVisible = kecId && row.dataset.kec === kecId;
        row.classList.toggle('hidden', !isVisible);
        row.classList.toggle('flex', isVisible);

        if (!isVisible && clearHidden) {
            const input = row.querySelector('input');
            if (input) input.value = '';
        }

        if (isVisible) shown++;
    });

    list.classList.toggle('hidden', shown === 0);
    empty.classList.toggle('hidden', shown > 0);
}

function filterDesaOptions(kecId) {
    document.querySelectorAll('.filter-desa-option').forEach(opt => {
        opt.style.display = (!kecId || opt.dataset.kec === kecId) ? '' : 'none';
    });
}

function updateDesaFilter(kecId) {
    const sel = document.getElementById('filter-desa');
    sel.value = '';
    filterDesaOptions(kecId);
    document.getElementById('filter-form').submit();
}

function openEdit(id, nama) {
    document.getElementById('edit-nama').value = nama;
    document.getElementById('edit-form').action = `/admin/tps/${id}`;
    document.getElementById('edit-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('edit-nama').select(), 50);
}

function closeEdit() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {
    loadBulkDesa('{{ old('kecamatan_id_filter') }}');
    filterDesaOptions('{{ $selectedKecamatanId }}');
});
</script>
@endpush
@endsection
