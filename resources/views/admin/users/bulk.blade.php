@extends('layouts.admin')
@section('title', 'Bulk Input User')
@section('admin_active', 'users')

@section('admin_content')
@php
    $inputClass = "w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none";
@endphp

<div class="mb-8">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin - Pengguna</p>
            <h1 class="font-display text-4xl tracking-[2px] admin-text">BULK INPUT USER</h1>
            <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">Isi banyak akun sekaligus dari tabel wilayah. Password kosong untuk user baru akan memakai username.</p>
        </div>
        <a href="{{ route('admin.users.index') }}"
           class="px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-800 hover:bg-gray-100 transition">
            Kembali
        </a>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ $errors->first() }}
</div>
@endif

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('admin.users.bulk') }}" class="flex items-end gap-3 flex-wrap">
        <div>
            <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Role</label>
            <select name="role" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="kpps" {{ $role === 'kpps' ? 'selected' : '' }}>Saksi TPS per TPS</option>
                <option value="pps" {{ $role === 'pps' ? 'selected' : '' }}>Kordes per Desa</option>
                <option value="ppk" {{ $role === 'ppk' ? 'selected' : '' }}>Korcam per Kecamatan</option>
            </select>
        </div>

        @if($role !== 'ppk')
        <div>
            <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Kecamatan</label>
            <select name="kecamatan_id" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Pilih Kecamatan</option>
                @foreach($kecamatans as $kecamatan)
                <option value="{{ $kecamatan->id }}" {{ (int) $selectedKecamatanId === (int) $kecamatan->id ? 'selected' : '' }}>
                    {{ $kecamatan->nama }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        @if($role === 'kpps' && $selectedKecamatanId)
        <div>
            <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Desa</label>
            <select name="desa_id" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Pilih Desa</option>
                @foreach($desas as $desa)
                <option value="{{ $desa->id }}" {{ (int) $selectedDesaId === (int) $desa->id ? 'selected' : '' }}>
                    {{ $desa->nama }}
                </option>
                @endforeach
            </select>
        </div>
        @endif
    </form>
</div>

@if($rows->isEmpty())
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-10 text-center">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Belum ada data untuk ditampilkan</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
        @if($role === 'kpps')
            Pilih kecamatan dan desa untuk menampilkan semua TPS.
        @elseif($role === 'pps')
            Pilih kecamatan untuk menampilkan semua desa.
        @else
            Data kecamatan belum tersedia.
        @endif
    </p>
</div>
@else
<form method="POST" action="{{ route('admin.users.bulk.store') }}">
    @csrf
    <input type="hidden" name="role" value="{{ $role }}">
    <input type="hidden" name="kecamatan_id" value="{{ $selectedKecamatanId }}">
    <input type="hidden" name="desa_id" value="{{ $selectedDesaId }}">

    <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-x-auto">
        <div class="grid grid-cols-12 min-w-[980px] px-5 py-3 border-b dark:border-gray-700 border-gray-200">
            <div class="col-span-1">
                <input type="checkbox" id="check-all" class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
            </div>
            <div class="col-span-3 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Wilayah</div>
            <div class="col-span-3 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Nama User</div>
            <div class="col-span-2 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Username</div>
            <div class="col-span-2 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Password</div>
            <div class="col-span-1 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Status</div>
        </div>

        @foreach($rows as $row)
        @php
            $oldBase = "rows.{$row['id']}";
            $isExisting = (bool) $row['user'];
            $checked = old("rows.{$row['id']}.enabled") || (!$isExisting && !old('rows'));
        @endphp
        <div class="grid grid-cols-12 min-w-[980px] px-5 py-4 border-b dark:border-gray-700 border-gray-100 last:border-0 items-center gap-3">
            <div class="col-span-1">
                <input type="checkbox" name="rows[{{ $row['id'] }}][enabled]" value="1"
                       class="bulk-check rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500"
                       {{ $checked ? 'checked' : '' }}>
            </div>
            <div class="col-span-3">
                <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">{{ $row['label'] }}</p>
                <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $row['scope'] }}</p>
            </div>
            <div class="col-span-3">
                <input type="text" name="rows[{{ $row['id'] }}][name]"
                       value="{{ old($oldBase . '.name', $row['name']) }}"
                       class="{{ $inputClass }}">
            </div>
            <div class="col-span-2">
                <input type="text" name="rows[{{ $row['id'] }}][username]"
                       value="{{ old($oldBase . '.username', $row['username']) }}"
                       class="{{ $inputClass }}">
            </div>
            <div class="col-span-2">
                <input type="text" name="rows[{{ $row['id'] }}][password]"
                       value="{{ old($oldBase . '.password') }}"
                       placeholder="{{ $isExisting ? 'Kosong = tetap' : 'Kosong = username' }}"
                       class="{{ $inputClass }}">
            </div>
            <div class="col-span-1">
                @if($isExisting)
                <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold text-teal-500 bg-teal-500/10 border border-teal-500/30">Ada</span>
                @else
                <span class="text-[9px] tracking-widest uppercase px-2 py-1 rounded font-semibold text-gray-500 bg-gray-500/10 border border-gray-500/30">Baru</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-5 flex items-center justify-between gap-3 flex-wrap">
        <p class="text-xs dark:text-gray-500 text-gray-400">
            {{ $rows->count() }} baris ditampilkan. Password kosong untuk user baru memakai username, existing tetap tidak berubah.
        </p>
        <button class="px-5 py-2.5 rounded-lg text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition"
                onclick="return confirm('Simpan user yang dicentang?')">
            Simpan User Terpilih
        </button>
    </div>
</form>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkAll = document.getElementById('check-all');
    if (!checkAll) return;

    checkAll.addEventListener('change', () => {
        document.querySelectorAll('.bulk-check').forEach((checkbox) => {
            checkbox.checked = checkAll.checked;
        });
    });
});
</script>
@endpush
@endsection
