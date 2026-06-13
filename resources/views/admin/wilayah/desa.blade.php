@extends('layouts.admin')
@section('title', 'Kelola Desa')
@section('admin_active', 'desa')

@section('admin_content')
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Wilayah</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">KELOLA DESA</h1>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form Tambah --}}
    <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Tambah Desa</p>
        <form method="POST" action="{{ route('admin.desa.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                <select name="kecamatan_id"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Kecamatan —</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ old('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
                @error('kecamatan_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Desa</label>
                <input type="text" name="nama" value="{{ old('nama') }}" placeholder="cth: Desa Cimahi"
                       class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                Tambah →
            </button>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="lg:col-span-2 dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b dark:border-gray-700 border-gray-200">
            <form method="GET" class="flex items-center gap-3">
                <select name="kecamatan_id" onchange="this.form.submit()"
                        class="dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
                <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold uppercase tracking-wider">
                    {{ request('kecamatan_id') ? $desas->count() . ' Desa' : 'Pilih filter' }}
                </span>
            </form>
        </div>
        @if(! request('kecamatan_id'))
        <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Pilih kecamatan untuk menampilkan desa.</div>
        @else
        @forelse($desas as $desa)
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50 transition group">
            <div>
                <p class="text-sm font-medium dark:text-gray-100 text-gray-800">{{ $desa->nama }}</p>
                <p class="text-xs dark:text-gray-500 text-gray-400 mt-0.5">{{ $desa->kecamatan->nama }} · {{ $desa->tps_count }} TPS</p>
            </div>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                <a href="{{ route('admin.desa.view', $desa) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium border border-teal-400 text-teal-400 hover:bg-teal-400 hover:text-white transition">
                    View
                </a>
                <button onclick="openEdit({{ $desa->id }}, '{{ $desa->nama }}', {{ $desa->kecamatan_id }})"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Edit
                </button>
                <form method="POST" action="{{ route('admin.desa.destroy', $desa) }}"
                      onsubmit="return confirm('Hapus desa ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-400 text-red-400 hover:bg-red-500 hover:text-white transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Belum ada desa pada kecamatan ini.</div>
        @endforelse
        @endif
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 p-8 w-full max-w-md shadow-2xl">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Edit Desa</p>
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                <select id="edit-kecamatan" name="kecamatan_id"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Desa</label>
                <input type="text" id="edit-nama" name="nama"
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
function openEdit(id, nama, kecId) {
    document.getElementById('edit-nama').value = nama;
    document.getElementById('edit-kecamatan').value = kecId;
    document.getElementById('edit-form').action = `/admin/desa/${id}`;
    document.getElementById('edit-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeEdit() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.body.style.overflow = '';
}
</script>
@endpush
@endsection
