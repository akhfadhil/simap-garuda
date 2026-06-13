@extends('layouts.admin')
@section('title', 'Kelola TPS')
@section('admin_active', 'tps')

@section('admin_content')
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Wilayah</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">KELOLA TPS</h1>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Form Tambah --}}
    <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Tambah TPS</p>
        <form method="POST" action="{{ route('admin.tps.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                <select id="filter-kec" onchange="filterDesa(this.value)"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Kecamatan —</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Desa</label>
                <select name="desa_id" id="desa-select"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Desa —</option>
                </select>
                @error('desa_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama TPS</label>
                <input type="text" name="nama" value="{{ old('nama') }}" placeholder="cth: TPS 001"
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
            <form method="GET" class="flex gap-3 flex-wrap items-center">
                <select name="kecamatan_id" onchange="this.form.submit()"
                        class="dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Semua Kecamatan</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
                @if($desas->count())
                <select name="desa_id" onchange="this.form.submit()"
                        class="dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">Semua Desa</option>
                    @foreach($desas as $d)
                    <option value="{{ $d->id }}" {{ request('desa_id') == $d->id ? 'selected' : '' }}>{{ $d->nama }}</option>
                    @endforeach
                </select>
                @endif
                <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold uppercase tracking-wider">{{ $tps->count() }} TPS</span>
            </form>
        </div>
        @forelse($tps as $t)
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50 transition group">
            <div>
                <p class="text-sm font-medium dark:text-gray-100 text-gray-800">{{ $t->nama }}</p>
                <p class="text-xs dark:text-gray-500 text-gray-400 mt-0.5">{{ $t->desa->nama }} · {{ $t->desa->kecamatan->nama }}</p>
            </div>
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                <a href="{{ route('admin.tps.view', $t) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium border border-sky-400 text-sky-400 hover:bg-sky-400 hover:text-white transition">
                    View
                </a>
                <form method="POST" action="{{ route('admin.tps.destroy', $t) }}"
                      onsubmit="return confirm('Hapus TPS ini?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-400 text-red-400 hover:bg-red-500 hover:text-white transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Belum ada TPS.</div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
const desaData = @json(\App\Models\Desa::all(['id','nama','kecamatan_id']));

function filterDesa(kecId) {
    const sel = document.getElementById('desa-select');
    sel.innerHTML = '<option value="">— Pilih Desa —</option>';
    desaData.filter(d => d.kecamatan_id == kecId).forEach(d => {
        sel.innerHTML += `<option value="${d.id}">${d.nama}</option>`;
    });
}
</script>
@endpush
@endsection
