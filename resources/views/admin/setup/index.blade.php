@extends('layouts.admin')
@section('title', 'Setup Data ' . config('party.short_name'))
@section('admin_active', 'setup')

@section('admin_content')
@php
    $party = $party ?? config('party');
@endphp
<div class="mb-8">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Setup</p>
    <h1 class="font-display text-4xl tracking-[2px] admin-text">SETUP DATA {{ strtoupper($party['short_name']) }}</h1>
    <p class="dark:text-gray-400 text-gray-500 text-sm mt-1">Kelola jenis pemilihan aktif, dapil, caleg, dan referensi rekap {{ $party['name'] }}.</p>
</div>

@if(session('success'))
<div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    ✓ {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 text-xs mb-6 rounded-lg font-medium">
    {{ $errors->first() }}
</div>
@endif

{{-- TAB NAVIGATION --}}
<div class="flex gap-1 mb-6 dark:bg-gray-900 bg-gray-100 p-1 rounded-xl w-fit">
    @foreach(['dpr_ri'=>'DPR RI','dprd_prov'=>'DPRD Prov','dprd_kab'=>'DPRD Kab'] as $tab => $label)
    <button onclick="switchTab('{{ $tab }}')" id="tab-{{ $tab }}"
            class="px-4 py-2 text-xs font-semibold rounded-lg transition tab-btn"
            data-tab="{{ $tab }}">
        {{ $label }}
    </button>
    @endforeach
</div>

{{-- Pengaturan Jenis Pemilu --}}
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b dark:border-gray-700 border-gray-200">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Aktifkan Jenis Pemilu</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">Hanya jenis yang dicentang yang bisa diakses oleh Saksi TPS, Kordes, dan Korcam.</p>
    </div>
    <form method="POST" action="{{ route('admin.setup.pemilu.settings') }}" class="p-6">
        @csrf
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-5">
            @foreach([
                'dpr_ri'    => 'DPR RI',
                'dprd_prov' => 'DPRD Provinsi',
                'dprd_kab'  => 'DPRD Kabupaten',
            ] as $key => $label)
            @php $active = $pemiluSettings[$key]->is_active ?? true; @endphp
            <label class="flex items-center gap-3 dark:bg-gray-700/50 bg-gray-50 border dark:border-gray-700 border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-[var(--admin-primary)] transition {{ $active ? 'border-[var(--admin-primary)]/50' : '' }}">
                <input type="checkbox" name="jenis_{{ $key }}" value="1" {{ $active ? 'checked' : '' }}
                       class="w-4 h-4 accent-[var(--admin-primary)]">
                <span class="text-sm dark:text-gray-300 text-gray-600 font-medium">{{ $label }}</span>
            </label>
            @endforeach
        </div>
        <button class="px-5 py-2.5 bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white text-sm font-semibold rounded-lg transition">
            Simpan Pengaturan
        </button>
    </form>
</div>

{{-- TAB DPR RI & DPRD PROV --}}
@foreach(['dpr_ri'=>['partaiDprRi','DPR RI','bg-orange-500'],'dprd_prov'=>['partaiProv','DPRD Provinsi','bg-sky-500']] as $jenis => [$var, $label, $color])
<div id="panel-{{ $jenis }}" class="tab-panel hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Tambah Caleg {{ $label }}</p>
            <form method="POST" action="{{ route('admin.setup.caleg.configured.store') }}" data-ajax-caleg>
                @csrf
                <input type="hidden" name="jenis" value="{{ $jenis }}">
                <div class="mb-4">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">No. Urut Caleg</label>
                    <input type="number" name="nomor_urut" min="1" placeholder="1"
                           class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Caleg</label>
                    <input type="text" name="nama_caleg" placeholder="Nama caleg {{ $party['short_name'] }}"
                           class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                </div>
                <button class="w-full bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Tambah Caleg
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b dark:border-gray-700 border-gray-200 flex items-center justify-between">
                <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Caleg {{ $party['short_name'] }} {{ $label }}</p>
                @if($$var->isNotEmpty())
                <span class="text-[10px] dark:text-gray-500 text-gray-400 font-semibold" data-caleg-count="{{ $$var->first()->id }}">{{ $$var->first()->calegs->count() }} caleg</span>
                @endif
            </div>
            @forelse($$var as $partai)
            <div class="border-b dark:border-gray-700 border-gray-100 last:border-0">
                <div id="partai-{{ $partai->id }}">
                    @foreach($partai->calegs as $caleg)
                    <div class="flex items-center justify-between px-6 py-3 border-t dark:border-gray-700 border-gray-100 group">
                        <div class="flex items-center gap-3">
                            <span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}</span>
                            <p class="text-sm font-semibold dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.setup.caleg.destroy', $caleg) }}"
                              data-ajax-delete="caleg" class="opacity-0 group-hover:opacity-100 transition">
                            @csrf @method('DELETE')
                            <button class="px-2 py-1 rounded text-xs border border-red-500 text-red-500 hover:bg-red-600 hover:text-white transition">×</button>
                        </form>
                    </div>
                    @endforeach
                    <div class="px-6 py-4 border-t dark:border-gray-700 border-gray-100 dark:bg-gray-900/30 bg-gray-50">
                        <form method="POST" action="{{ route('admin.setup.caleg.store', $partai) }}" class="flex gap-2" data-ajax-caleg data-partai-id="{{ $partai->id }}">
                            @csrf
                            <input type="number" name="nomor_urut" placeholder="No" min="1"
                                   class="w-16 dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                            <input type="text" name="nama_caleg" placeholder="Nama caleg..."
                                   class="flex-1 dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                            <button class="px-4 py-2 bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white text-xs font-semibold rounded-lg transition">+ Caleg</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Belum ada caleg. Tambahkan caleg dari form di samping.</div>
            @endforelse
        </div>
    </div>
</div>
@endforeach

{{-- ══ TAB DPRD KAB ══ --}}
<div id="panel-dprd_kab" class="tab-panel hidden">

    {{-- Row 1: Setup Dapil + Assign Kecamatan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Form tambah dapil --}}
        <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Daftar Dapil</p>
            <form method="POST" action="{{ route('admin.setup.dapil.store') }}" class="flex gap-2 mb-4">
                @csrf
                <input type="text" name="nama" placeholder="cth: Dapil 1"
                       class="flex-1 dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                <button class="px-4 py-2.5 bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white text-xs font-semibold rounded-lg transition">+ Tambah</button>
            </form>
            @forelse($dapils as $dapil)
            <div class="flex items-center justify-between py-2.5 border-b dark:border-gray-700 border-gray-100 last:border-0 group">
                <span class="text-sm dark:text-gray-200 text-gray-700 font-medium">{{ $dapil->nama }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-xs dark:text-gray-500 text-gray-400">{{ $dapil->kecamatans->count() }} kecamatan</span>
                    <form method="POST" action="{{ route('admin.setup.dapil.destroy', $dapil) }}"
                          onsubmit="return confirm('Hapus dapil ini?')" class="opacity-0 group-hover:opacity-100 transition">
                        @csrf @method('DELETE')
                        <button class="px-2 py-1 rounded text-xs border border-red-500 text-red-500 hover:bg-red-600 hover:text-white transition">×</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-xs dark:text-gray-600 text-gray-400 text-center py-4">Belum ada dapil.</p>
            @endforelse
        </div>

        {{-- Assign kecamatan ke dapil --}}
        <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Assign Kecamatan ke Dapil</p>
            @if($kecamatans->isEmpty())
            <p class="text-xs dark:text-gray-600 text-gray-400 text-center py-4">Belum ada kecamatan.</p>
            @else
            <form method="POST" action="{{ route('admin.setup.kecamatan.dapil') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-96 overflow-y-auto pr-1 mb-5">
            @foreach($kecamatans as $kec)
                    <label class="dark:bg-gray-900/60 bg-gray-50 border dark:border-gray-700 border-gray-200 rounded-lg px-3 py-3">
                <span class="block text-xs font-semibold dark:text-gray-300 text-gray-700 mb-2 truncate">{{ $kec->nama }}</span>
                <select name="kecamatan_dapil[{{ $kec->id }}]"
                        class="w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Dapil —</option>
                    @foreach($dapils as $dapil)
                    <option value="{{ $dapil->id }}" {{ $kec->dapil_id == $dapil->id ? 'selected' : '' }}>
                        {{ $dapil->nama }}
                    </option>
                    @endforeach
                </select>
                    </label>
            @endforeach
                </div>
                <button class="w-full bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Simpan Assign Dapil
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Row 2: Tambah Caleg per Dapil --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 p-6 shadow-sm">
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-5 font-semibold">// Tambah Caleg DPRD Kab</p>
            <form method="POST" action="{{ route('admin.setup.caleg.configured.store') }}" data-ajax-caleg>
                @csrf
                <input type="hidden" name="jenis" value="dprd_kab">
                <div class="mb-4">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Dapil</label>
                    <select name="dapil_id"
                            class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                        <option value="">— Pilih Dapil —</option>
                        @foreach($dapils as $dapil)
                        <option value="{{ $dapil->id }}">{{ $dapil->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">No. Urut Caleg</label>
                    <input type="number" name="nomor_urut" min="1" placeholder="1"
                           class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Caleg</label>
                    <input type="text" name="nama_caleg" placeholder="Nama caleg {{ $party['short_name'] }}"
                           class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                </div>
                <button class="w-full bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Tambah Caleg
                </button>
            </form>
        </div>

        {{-- Daftar partai per dapil --}}
        <div class="lg:col-span-2 dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b dark:border-gray-700 border-gray-200">
                <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Caleg {{ $party['short_name'] }} DPRD Kab per Dapil</p>
            </div>

            @if($dapils->isEmpty())
            <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">Belum ada dapil. Tambah dapil terlebih dahulu.</div>
            @else

            {{-- Tab dapil --}}
            <div class="flex gap-1 p-3 border-b dark:border-gray-700 border-gray-200 dark:bg-gray-900/30 bg-gray-50 flex-wrap">
                @foreach($dapils as $i => $dapil)
                @php 
                    $dapilPartais = $partaiKab[(string)$dapil->id] ?? collect(); 
                    $firstPartai = $dapilPartais->first();
                    $calegCount = $firstPartai ? $firstPartai->calegs->count() : 0;
                @endphp
                <button onclick="switchDapilTab({{ $dapil->id }})" id="dapil-tab-{{ $dapil->id }}"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition dapil-tab-btn">
                    {{ $dapil->nama }}
                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px]
                                dark:bg-gray-700 bg-gray-200 dark:text-gray-400 text-gray-500"
                          @if($firstPartai) data-caleg-count="{{ $firstPartai->id }}" @endif>
                        {{ $calegCount }} caleg
                    </span>
                </button>
                @endforeach
            </div>

            {{-- Panel per dapil --}}
            @foreach($dapils as $dapil)
            @php $dapilPartais = $partaiKab[(string)$dapil->id] ?? collect(); @endphp
            <div id="dapil-panel-{{ $dapil->id }}" class="dapil-panel hidden">
                @forelse($dapilPartais as $partai)
                <div class="border-b dark:border-gray-700 border-gray-100 last:border-0">
                    <div id="partai-{{ $partai->id }}">
                        @foreach($partai->calegs as $caleg)
                        <div class="flex items-center justify-between px-6 py-3 border-t dark:border-gray-700 border-gray-100 group">
                            <div class="flex items-center gap-3">
                                <span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}</span>
                                <p class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.setup.caleg.destroy', $caleg) }}"
                                data-ajax-delete="caleg" class="opacity-0 group-hover:opacity-100 transition">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 rounded text-xs border border-red-500 text-red-500 hover:bg-red-600 hover:text-white transition">×</button>
                            </form>
                        </div>
                        @endforeach
                        {{-- Form tambah caleg --}}
                        <div class="px-6 py-4 border-t dark:border-gray-700 border-gray-100 dark:bg-gray-900/30 bg-gray-50">
                            <form method="POST" action="{{ route('admin.setup.caleg.store', $partai) }}" class="flex gap-2" data-ajax-caleg data-partai-id="{{ $partai->id }}">
                                @csrf
                                <input type="number" name="nomor_urut" placeholder="No" min="1"
                                    class="w-16 dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                                <input type="text" name="nama_caleg" placeholder="Nama caleg..."
                                    class="flex-1 dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
                                <button class="px-4 py-2 bg-[var(--admin-primary)] hover:bg-[var(--admin-primary)]/90 text-white text-xs font-semibold rounded-lg transition">+ Caleg</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center dark:text-gray-600 text-gray-400 text-sm">
                    Belum ada partai untuk {{ $dapil->nama }}.
                </div>
                @endforelse
            </div>
            @endforeach

            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
const tabs = ['dpr_ri','dprd_prov','dprd_kab'];

function addPartaiFields(button) {
    const form = button.closest('form');
    const container = form.querySelector('.partai-extra-rows');
    const indexes = Array.from(form.querySelectorAll('input[name*="[nomor_urut]"]'))
        .map((input) => input.name.match(/partais\[(\d+)\]/))
        .filter(Boolean)
        .map((match) => Number(match[1]));
    const index = indexes.length ? Math.max(...indexes) + 1 : 0;
    const row = document.createElement('div');
    row.className = 'grid grid-cols-1 md:grid-cols-[96px_1fr_auto] gap-2 mb-4 items-end';
    row.innerHTML = `
        <div>
            <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">No. Urut</label>
            <input type="number" name="partais[${index}][nomor_urut]" min="1" placeholder="${index + 1}"
                   class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Nama Partai</label>
            <input type="text" name="partais[${index}][nama_partai]" placeholder="cth: Partai Kebangkitan Bangsa"
                   class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-0 focus:outline-none">
        </div>
        <button type="button" onclick="this.closest('div').remove()"
                class="px-3 py-2.5 rounded-lg text-xs font-semibold border border-red-500/40 text-red-500 hover:bg-red-500/10 transition">
            Hapus
        </button>
    `;
    container.appendChild(row);
}

function switchTab(active) {
    tabs.forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const btn   = document.getElementById('tab-' + t);
        if (t === active) {
            panel.classList.remove('hidden');
            btn.classList.add('dark:bg-gray-700','bg-white','shadow','dark:text-white','text-gray-800');
            btn.classList.remove('dark:text-gray-500','text-gray-400');
        } else {
            panel.classList.add('hidden');
            btn.classList.remove('dark:bg-gray-700','bg-white','shadow','dark:text-white','text-gray-800');
            btn.classList.add('dark:text-gray-500','text-gray-400');
        }
    });
    localStorage.setItem('setup_tab', active);
}


function toggleDapil(id) {
    const el    = document.getElementById('dapil-' + id);
    const arrow = document.getElementById('arrow-dapil-' + id);
    el.classList.toggle('hidden');
    arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
}

function switchDapilTab(activeId) {
    // panel
    document.querySelectorAll('.dapil-panel').forEach(el => el.classList.add('hidden'));
    document.getElementById('dapil-panel-' + activeId).classList.remove('hidden');

    // tab button style
    document.querySelectorAll('.dapil-tab-btn').forEach(btn => {
        btn.classList.remove('dark:bg-gray-700','bg-white','shadow','dark:text-white','text-gray-800');
        btn.classList.add('dark:text-gray-500','text-gray-400');
    });
    const activeBtn = document.getElementById('dapil-tab-' + activeId);
    activeBtn.classList.add('dark:bg-gray-700','bg-white','shadow','dark:text-white','text-gray-800');
    activeBtn.classList.remove('dark:text-gray-500','text-gray-400');

    localStorage.setItem('dapil_tab', activeId);
}

// auto-aktifkan tab pertama atau yang tersimpan
const savedDapilTab = localStorage.getItem('dapil_tab');
const firstDapilBtn = document.querySelector('.dapil-tab-btn');
if (firstDapilBtn) {
    const firstId = firstDapilBtn.id.replace('dapil-tab-','');
    switchDapilTab(savedDapilTab || firstId);
}

// Restore tab dari localStorage
const savedTab = localStorage.getItem('setup_tab');
switchTab(tabs.includes(savedTab) ? savedTab : 'dpr_ri');


document.querySelectorAll('.partai-extra-rows').forEach((container) => {
    const addButton = container.nextElementSibling;
    if (addButton) {
        addPartaiFields(addButton);
        addPartaiFields(addButton);
    }
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

function calegRowHtml(caleg) {
    return `
        <div class="flex items-center justify-between px-6 py-3 border-t dark:border-gray-700 border-gray-100 group">
            <div class="flex items-center gap-3">
                <span class="text-xs dark:text-gray-500 text-gray-400 w-4">${escapeHtml(caleg.nomor_urut)}</span>
                <p class="text-sm dark:text-gray-200 text-gray-700">${escapeHtml(caleg.nama_caleg)}</p>
            </div>
            <form method="POST" action="${escapeHtml(caleg.destroy_url)}" data-ajax-delete="caleg" class="opacity-0 group-hover:opacity-100 transition">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                <input type="hidden" name="_method" value="DELETE">
                <button class="px-2 py-1 rounded text-xs border border-red-500 text-red-500 hover:bg-red-600 hover:text-white transition">×</button>
            </form>
        </div>
    `;
}

function appendCalegRow(partaiId, caleg) {
    const panel = document.getElementById('partai-' + partaiId);
    const formWrapper = panel?.querySelector('form[data-ajax-caleg]')?.closest('div');
    let appended = false;
    if (formWrapper) {
        formWrapper.insertAdjacentHTML('beforebegin', calegRowHtml(caleg));
        appended = true;
    }
    updateCalegCount(partaiId, 1);
    return appended;
}

function updateCalegCount(partaiId, delta) {
    const counter = document.querySelector(`[data-caleg-count="${partaiId}"]`);
    if (counter) {
        const current = parseInt(counter.textContent, 10) || 0;
        counter.textContent = `${Math.max(0, current + delta)} caleg`;
    }
}

function confirmDeleteCaleg() {
    let dialog = document.querySelector('[data-delete-caleg-dialog]');
    if (!dialog) {
        dialog = document.createElement('div');
        dialog.dataset.deleteCalegDialog = '1';
        dialog.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4';
        dialog.innerHTML = `
            <div class="w-full max-w-sm rounded-xl border dark:border-gray-700 border-gray-200 dark:bg-gray-900 bg-white p-5 shadow-xl">
                <p class="text-sm font-semibold dark:text-gray-100 text-gray-800 mb-2">Hapus caleg ini?</p>
                <p class="text-xs dark:text-gray-400 text-gray-500 mb-5">Data caleg akan dihapus dari daftar setup.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" data-delete-cancel class="px-4 py-2 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Batal</button>
                    <button type="button" data-delete-confirm class="px-4 py-2 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition">Hapus</button>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);
    }

    return new Promise((resolve) => {
        const close = (value) => {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            dialog.querySelector('[data-delete-cancel]').onclick = null;
            dialog.querySelector('[data-delete-confirm]').onclick = null;
            resolve(value);
        };

        dialog.querySelector('[data-delete-cancel]').onclick = () => close(false);
        dialog.querySelector('[data-delete-confirm]').onclick = () => close(true);
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        dialog.querySelector('[data-delete-cancel]').focus();
    });
}

document.querySelectorAll('form[data-ajax-caleg]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"], button:not([type])');
        button?.setAttribute('disabled', 'disabled');
        button?.classList.add('opacity-60');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            if (!response.ok) throw new Error('Request failed');

            const payload = await response.json();
            const partaiId = payload.partai_id || form.dataset.partaiId;
            const appended = partaiId && payload.caleg ? appendCalegRow(partaiId, payload.caleg) : false;
            form.reset();
            if (!appended) {
                let status = form.querySelector('[data-ajax-caleg-status]');
                if (!status) {
                    status = document.createElement('p');
                    status.dataset.ajaxCalegStatus = '1';
                    status.className = 'mt-3 text-xs font-semibold text-green-500';
                    form.appendChild(status);
                }
                status.textContent = payload.message || 'Caleg berhasil ditambahkan.';
            }
        } catch (error) {
            form.submit();
        } finally {
            button?.removeAttribute('disabled');
            button?.classList.remove('opacity-60');
        }
    });
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-ajax-delete="caleg"]');
    if (!form) return;

    event.preventDefault();
    event.stopPropagation();

    const confirmed = await confirmDeleteCaleg();
    if (!confirmed) return;

    const button = form.querySelector('button[type="submit"], button:not([type])');
    button?.setAttribute('disabled', 'disabled');
    button?.classList.add('opacity-60');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: new FormData(form),
        });

        if (!response.ok) throw new Error('Request failed');

        const panel = form.closest('[id^="partai-"]');
        const partaiId = panel?.id.replace('partai-', '');
        form.closest('.group')?.remove();
        if (partaiId) updateCalegCount(partaiId, -1);
    } catch (error) {
        form.submit();
    } finally {
        button?.removeAttribute('disabled');
        button?.classList.remove('opacity-60');
    }
});
</script>
@endpush
@endsection
