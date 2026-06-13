@extends('layouts.admin')
@section('title', 'Kelola Kecamatan')
@section('admin_active', 'kecamatan')

@section('admin_content')
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Wilayah</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">KELOLA KECAMATAN</h1>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form Tambah --}}
    <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Tambah Kecamatan</p>
        <form method="POST" action="{{ route('admin.kecamatan.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Kecamatan</label>
                <input type="text" name="nama" value="{{ old('nama') }}" placeholder="cth: Kecamatan Andir"
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
        <div class="p-5 border-b dark:border-gray-700 border-gray-200">
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Daftar Kecamatan ({{ $kecamatans->count() }})</p>
        </div>
        @forelse($kecamatans as $kec)
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50 transition group">
            <div>
                <p class="text-sm font-medium dark:text-gray-100 text-gray-800">{{ $kec->nama }}</p>
                <p class="text-xs dark:text-gray-500 text-gray-400 mt-0.5">{{ $kec->desas_count }} desa</p>
            </div>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                <a href="{{ route('admin.kecamatan.view', $kec) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium border border-orange-400 text-orange-400 hover:bg-orange-400 hover:text-white transition">
                    View
                </a>
                <button onclick="openEdit({{ $kec->id }}, '{{ $kec->nama }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Edit
                </button>
                <form method="POST" action="{{ route('admin.kecamatan.destroy', $kec) }}"
                      onsubmit="return confirm('Hapus kecamatan ini? Semua desa & TPS di dalamnya akan terhapus.')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-400 text-red-400 hover:bg-red-500 hover:text-white transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Belum ada kecamatan.</div>
        @endforelse
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 p-8 w-full max-w-md shadow-2xl">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Edit Kecamatan</p>
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Kecamatan</label>
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
function openEdit(id, nama) {
    document.getElementById('edit-nama').value = nama;
    document.getElementById('edit-form').action = `/admin/kecamatan/${id}`;
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
