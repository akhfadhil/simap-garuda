<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b dark:border-gray-700 border-gray-200 pb-5">
        <div>
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Modul Pemetaan</p>
            <h2 class="font-display text-3xl tracking-wide dark:text-white text-gray-900 mt-1">DATA PENDUKUNG</h2>
        </div>
        <div class="flex flex-wrap gap-2.5">
            @if(Auth::user()->role !== 'kordes')
                <a href="{{ route('pemetaan-dukungan.statistik') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-800 hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-sm">bar_chart</span>
                    Statistik
                </a>
                <button onclick="triggerExport()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-800 hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Ekspor Excel
                </button>
            @endif
            <a href="{{ route('pemetaan-dukungan.create') }}" 
               class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-5 py-2.5 rounded-lg transition">
                <span class="material-symbols-outlined text-sm">add</span>
                Tambah Pendukung
            </a>
        </div>
    </div>

    {{-- Alert Success --}}
    @if(session('success'))
    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border dark:border-green-800 border-green-200 flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">check_circle</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Filter & Search Form --}}
    <form method="GET" action="{{ route('pemetaan-dukungan.index') }}" id="filter-form" class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search Input --}}
            <div>
                <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NIK, No HP..." 
                       class="w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-1 focus:ring-[var(--admin-primary)] focus:outline-none">
            </div>

            {{-- Kecamatan Filter --}}
            @if(Auth::user()->role === 'admin_partai')
            <div>
                <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Kecamatan</label>
                <select name="kecamatan_id" id="filter-kecamatan" onchange="loadDesaFilter(this.value)"
                        class="w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-1 focus:ring-[var(--admin-primary)] focus:outline-none">
                    <option value="">— Semua Kecamatan —</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}" {{ request('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Desa Filter --}}
            @if(in_array(Auth::user()->role, ['admin_partai', 'korcam'], true))
            <div>
                <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">Desa</label>
                <select name="desa_id" id="filter-desa" onchange="loadTpsFilter(this.value)"
                        class="w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-1 focus:ring-[var(--admin-primary)] focus:outline-none">
                    <option value="">— Semua Desa —</option>
                    @foreach($desas as $ds)
                    <option value="{{ $ds->id }}" {{ request('desa_id') == $ds->id ? 'selected' : '' }}>{{ $ds->nama }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- TPS Filter --}}
            <div>
                <label class="block text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider mb-1.5">TPS</label>
                <select name="tps_id" id="filter-tps"
                        class="w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-3 py-2 text-xs rounded-lg focus:border-[var(--admin-primary)] focus:ring-1 focus:ring-[var(--admin-primary)] focus:outline-none">
                    <option value="">— Semua TPS —</option>
                    @foreach($tpsList as $tp)
                    <option value="{{ $tp->id }}" {{ request('tps_id') == $tp->id ? 'selected' : '' }}>{{ $tp->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t dark:border-gray-700 border-gray-200">
            <a href="{{ route('pemetaan-dukungan.index') }}" 
               class="px-4 py-2 text-xs font-semibold rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-600 dark:hover:bg-gray-750 hover:bg-gray-50 transition">
                Reset
            </a>
            <button type="submit" 
                    class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-850 hover:bg-gray-750 dark:bg-gray-100 dark:hover:bg-gray-200 dark:text-gray-900 text-white transition">
                Cari & Filter
            </button>
        </div>
    </form>

    {{-- Supporters Table --}}
    <div class="mt-6 dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl overflow-hidden shadow-sm" style="margin-top: 1.5rem;">
        <div class="overflow-x-auto pt-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b dark:border-gray-700 border-gray-200 text-[10px] font-semibold dark:text-gray-400 text-gray-500 uppercase tracking-wider bg-transparent">
                        <th class="px-6 py-4 w-12 text-center">No</th>
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">NIK</th>
                        <th class="px-6 py-4">No. HP / WA</th>
                        <th class="px-6 py-4">Alamat</th>
                        <th class="px-6 py-4">Wilayah</th>
                        <th class="px-6 py-4 w-32 text-center">KTP</th>
                        <th class="px-6 py-4 w-40 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700 divide-gray-200 text-xs dark:text-gray-300 text-gray-700">
                    @forelse($pendukungs as $index => $pendukung)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750/30 transition-colors">
                        <td class="px-6 py-4 text-center dark:text-gray-500 text-gray-400 font-medium">
                            {{ $pendukungs->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 font-semibold dark:text-white text-gray-900">
                            {{ $pendukung->nama }}
                        </td>
                        <td class="px-6 py-4 font-mono">
                            {{ $pendukung->nik }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $pendukung->no_hp }}
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate">
                            {{ $pendukung->alamat }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="block font-semibold dark:text-gray-200 text-gray-800">Kec. {{ $pendukung->kecamatan?->nama ?? '-' }}</span>
                            <span class="block text-[10px] dark:text-gray-500 text-gray-400">Desa {{ $pendukung->desa?->nama ?? '-' }} @if($pendukung->tps) | {{ $pendukung->tps?->nama }} @endif</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($pendukung->ktp_path)
                                @php
                                    $isPdf = str_ends_with(strtolower($pendukung->ktp_path), '.pdf');
                                @endphp
                                @if($isPdf)
                                    <button onclick="openPreview('{{ route('pemetaan-dukungan.ktp', $pendukung) }}')"
                                             class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-semibold dark:bg-gray-800 bg-gray-100 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 hover:bg-[var(--admin-primary)] dark:hover:bg-[var(--admin-primary)] hover:text-white hover:border-transparent transition-all">
                                        <span class="material-symbols-outlined text-[12px]">visibility</span>
                                        Preview PDF
                                    </button>
                                @else
                                    <a href="{{ route('pemetaan-dukungan.ktp', $pendukung) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-semibold dark:bg-gray-800 bg-gray-100 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 hover:bg-[var(--admin-primary)] dark:hover:bg-[var(--admin-primary)] hover:text-white hover:border-transparent transition-all">
                                        <span class="material-symbols-outlined text-[12px]">download</span>
                                        Unduh Gambar
                                    </a>
                                @endif
                            @else
                                <span class="text-gray-400 dark:text-gray-600 italic">Tidak ada</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('pemetaan-dukungan.edit', $pendukung) }}"
                                   class="px-3 py-1.5 text-[10px] font-semibold rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                                    Edit
                                </a>
                                @if(Auth::user()->role === 'admin_partai')
                                <form method="POST" action="{{ route('pemetaan-dukungan.destroy', $pendukung) }}"
                                      onsubmit="return confirm('Hapus data pendukung {{ $pendukung->nama }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 text-[10px] font-semibold rounded-lg border border-[var(--admin-primary)]/30 text-[var(--admin-primary)] hover:bg-[var(--admin-primary)] hover:text-white hover:border-transparent transition">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>       </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center dark:text-gray-500 text-gray-400">
                            Tidak ditemukan data pendukung.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pendukungs->hasPages())
        <div class="px-6 py-4 border-t dark:border-gray-700 border-gray-200">
            {{ $pendukungs->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const allDesas = @json($desas->map(fn($d) => ['id'=>$d->id,'nama'=>$d->nama,'kecamatan_id'=>$d->kecamatan_id]));
    const allTps   = @json($tpsList->map(fn($t) => ['id'=>$t->id,'nama'=>$t->nama,'desa_id'=>$t->desa_id]));

    function loadDesaFilter(kecId) {
        const desas = allDesas.filter(d => !kecId || d.kecamatan_id == kecId);
        const sel = document.getElementById('filter-desa');
        if (sel) {
            sel.innerHTML = '<option value="">— Semua Desa —</option>';
            desas.forEach(d => sel.innerHTML += `<option value="${d.id}">${d.nama}</option>`);
            loadTpsFilter('');
        }
    }

    // Dynamic dropdown loading logic
    function loadTpsFilter(desaId) {
        let tps = [];
        if (desaId) {
            tps = allTps.filter(t => t.desa_id == desaId);
        } else {
            const currentKec = document.getElementById('filter-kecamatan') ? document.getElementById('filter-kecamatan').value : null;
            if (currentKec) {
                const decIds = allDesas.filter(d => d.kecamatan_id == currentKec).map(d => d.id);
                tps = allTps.filter(t => decIds.includes(t.desa_id));
            } else {
                tps = allTps;
            }
        }
        const sel = document.getElementById('filter-tps');
        if (sel) {
            sel.innerHTML = '<option value="">— Semua TPS —</option>';
            tps.forEach(t => sel.innerHTML += `<option value="${t.id}">${t.nama}</option>`);
        }
    }

    function triggerExport() {
        const form = document.getElementById('filter-form');
        const queryParams = new URLSearchParams(new FormData(form)).toString();
        window.location.href = `{{ route('pemetaan-dukungan.export') }}?${queryParams}`;
    }

    // Set dynamic dropdown values on page load if filters are active
    document.addEventListener('DOMContentLoaded', () => {
        const selectedKec = "{{ request('kecamatan_id') }}";
        const selectedDesa = "{{ request('desa_id') }}";
        const selectedTps = "{{ request('tps_id') }}";

        if (selectedKec) {
            loadDesaFilter(selectedKec);
            const desaSel = document.getElementById('filter-desa');
            if (desaSel) desaSel.value = selectedDesa;
        }
        if (selectedDesa || selectedKec) {
            loadTpsFilter(selectedDesa);
            const tpsSel = document.getElementById('filter-tps');
            if (tpsSel) tpsSel.value = selectedTps;
        }
    });
</script>
@endpush
