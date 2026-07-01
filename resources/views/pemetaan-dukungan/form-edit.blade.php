@php
$inputClass = "w-full dark:bg-gray-900 bg-white border dark:border-gray-700 border-gray-300 dark:text-gray-100 text-gray-800 px-4 py-2.5 text-sm rounded-lg focus:border-[var(--admin-primary)] focus:ring-1 focus:ring-[var(--admin-primary)] focus:outline-none transition-all duration-150";
$labelClass = "block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2";
@endphp

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('pemetaan-dukungan.index') }}" 
           class="inline-flex items-center gap-1.5 text-xs dark:text-gray-400 text-gray-500 dark:hover:text-white hover:text-gray-900 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali ke Daftar
        </a>
        <h2 class="font-display text-3xl tracking-wide dark:text-white text-gray-900 mt-2">EDIT PENDUKUNG</h2>
        <p class="text-xs dark:text-gray-500 text-gray-400">Silakan ubah formulir data pendukung di bawah ini dengan lengkap.</p>
    </div>

    {{-- Error display --}}
    @if ($errors->any())
    <div class="p-4 mb-5 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border dark:border-red-900 border-red-200">
        <p class="font-semibold mb-1">Ada beberapa kesalahan pengisian:</p>
        <ul class="list-disc pl-5 space-y-0.5 text-xs">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('pemetaan-dukungan.update', $pendukung) }}" enctype="multipart/form-data" 
          class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-6 md:p-8 space-y-6 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Nama Lengkap --}}
        <div>
            <label class="{{ $labelClass }}">Nama Lengkap <span class="text-red-500">*</span></label>
            <input type="text" name="nama" value="{{ old('nama', $pendukung->nama) }}" placeholder="cth: Ahmad Fauzi" class="{{ $inputClass }}" required>
        </div>

        {{-- NIK --}}
        <div>
            <label class="{{ $labelClass }}">NIK (Nomor Induk Kependudukan) <span class="text-red-500">*</span></label>
            <input type="text" name="nik" value="{{ old('nik', $pendukung->nik) }}" placeholder="16 digit angka KTP" pattern="[0-9]{16}" maxlength="16" class="{{ $inputClass }}" required>
            <span class="block text-[10px] dark:text-gray-500 text-gray-400 mt-1">Harus tepat 16 digit angka dan belum pernah terdaftar sebelumnya.</span>
        </div>

        {{-- Kontak WhatsApp / HP & Alamat --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="{{ $labelClass }}">No. HP / WhatsApp <span class="text-red-500">*</span></label>
                <input type="text" name="no_hp" value="{{ old('no_hp', $pendukung->no_hp) }}" placeholder="cth: 08123456789" class="{{ $inputClass }}" required>
            </div>
            <div>
                <label class="{{ $labelClass }}">Ganti Dokumen KTP <span class="text-xs dark:text-gray-600 text-gray-400 font-normal">(opsional)</span></label>
                <input type="file" name="ktp" accept="image/*,application/pdf" class="w-full text-xs dark:text-gray-400 text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[var(--admin-primary-soft)] file:text-[var(--admin-primary)] hover:file:bg-[var(--admin-primary)]/20 file:cursor-pointer">
                @if($pendukung->ktp_path)
                    <span class="block text-[10px] text-green-500 mt-1 flex items-center gap-1 font-medium">
                        <span class="material-symbols-outlined text-xs">check_circle</span>
                        KTP Sudah Terunggah
                    </span>
                @else
                    <span class="block text-[10px] dark:text-gray-500 text-gray-400 mt-1">Format: JPG, PNG, PDF. Maksimal 5MB.</span>
                @endif
            </div>
        </div>

        {{-- Alamat --}}
        <div>
            <label class="{{ $labelClass }}">Alamat Lengkap <span class="text-red-500">*</span></label>
            <textarea name="alamat" rows="3" placeholder="cth: Jl. Raya No. 45 RT 02 RW 03" class="{{ $inputClass }}" required>{{ old('alamat', $pendukung->alamat) }}</textarea>
        </div>

        {{-- Wilayah Dropdowns --}}
        <div class="border-t dark:border-gray-700 border-gray-200 pt-6 space-y-6">
            <h3 class="text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Penentuan Wilayah</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Kecamatan --}}
                @if(Auth::user()->role === 'admin_partai')
                <div>
                    <label class="{{ $labelClass }}">Kecamatan <span class="text-red-500">*</span></label>
                    <select name="kecamatan_id" id="select-kecamatan" onchange="loadDesa(this.value)" class="{{ $inputClass }}" required>
                        @foreach($kecamatans as $kec)
                        <option value="{{ $kec->id }}" {{ old('kecamatan_id', $pendukung->kecamatan_id) == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="{{ $labelClass }}">Kecamatan</label>
                    <input type="text" value="{{ $kecamatans->first()?->nama ?? '-' }}" class="w-full dark:bg-gray-800/60 bg-gray-100 border dark:border-gray-700 border-gray-200 dark:text-gray-500 text-gray-400 px-4 py-2.5 text-sm rounded-lg cursor-not-allowed" readonly>
                </div>
                @endif

                {{-- Desa --}}
                @if(in_array(Auth::user()->role, ['admin_partai', 'korcam'], true))
                <div>
                    <label class="{{ $labelClass }}">Desa <span class="text-red-500">*</span></label>
                    <select name="desa_id" id="select-desa" onchange="loadTps(this.value)" class="{{ $inputClass }}" required>
                        <option value="">— Pilih Desa —</option>
                        @foreach($desas as $ds)
                        <option value="{{ $ds->id }}" {{ old('desa_id', $pendukung->desa_id) == $ds->id ? 'selected' : '' }}>{{ $ds->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="{{ $labelClass }}">Desa</label>
                    <input type="text" value="{{ $desas->first()?->nama ?? '-' }}" class="w-full dark:bg-gray-800/60 bg-gray-100 border dark:border-gray-700 border-gray-200 dark:text-gray-500 text-gray-400 px-4 py-2.5 text-sm rounded-lg cursor-not-allowed" readonly>
                </div>
                @endif

                {{-- TPS --}}
                <div>
                    <label class="{{ $labelClass }}">TPS <span class="text-xs dark:text-gray-600 text-gray-400 font-normal">(opsional)</span></label>
                    <select name="tps_id" id="select-tps" class="{{ $inputClass }}">
                        <option value="">— Pilih TPS —</option>
                        @foreach($tpsList as $tp)
                        <option value="{{ $tp->id }}" {{ old('tps_id', $pendukung->tps_id) == $tp->id ? 'selected' : '' }}>{{ $tp->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Catatan --}}
        <div class="border-t dark:border-gray-700 border-gray-200 pt-6">
            <label class="{{ $labelClass }}">Catatan Tambahan</label>
            <textarea name="catatan" rows="3" placeholder="Informasi pendukung tambahan..." class="{{ $inputClass }}">{{ old('catatan', $pendukung->catatan) }}</textarea>
        </div>

        {{-- Submit Button --}}
        <div class="flex gap-4 pt-4 border-t dark:border-gray-700 border-gray-200">
            <a href="{{ route('pemetaan-dukungan.index') }}" 
               class="flex-1 text-center border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 py-3 rounded-xl text-sm font-semibold dark:hover:bg-gray-750 hover:bg-gray-100 transition duration-150">
                Batal
            </a>
            <button type="submit" 
                    class="flex-1 bg-[var(--admin-primary)] hover:opacity-90 text-white py-3 rounded-xl text-sm font-bold shadow-lg shadow-black/10 transition duration-150">
                Simpan Perubahan →
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const allDesas = @json($desas->map(fn($d) => ['id'=>$d->id,'nama'=>$d->nama,'kecamatan_id'=>$d->kecamatan_id]));
    const allTps   = @json($tpsList->map(fn($t) => ['id'=>$t->id,'nama'=>$t->nama,'desa_id'=>$t->desa_id]));

    function loadDesa(kecId, selectedDesaId = null) {
        const desas = allDesas.filter(d => d.kecamatan_id == kecId);
        const sel = document.getElementById('select-desa');
        if (sel) {
            sel.innerHTML = '<option value="">— Pilih Desa —</option>';
            desas.forEach(d => sel.innerHTML += `<option value="${d.id}" ${d.id == selectedDesaId ? 'selected' : ''}>${d.nama}</option>`);
            if (!selectedDesaId) {
                loadTps('');
            }
        }
    }

    function loadTps(desaId, selectedTpsId = null) {
        const tps = allTps.filter(t => t.desa_id == desaId);
        const sel = document.getElementById('select-tps');
        if (sel) {
            sel.innerHTML = '<option value="">— Pilih TPS —</option>';
            tps.forEach(t => sel.innerHTML += `<option value="${t.id}" ${t.id == selectedTpsId ? 'selected' : ''}>${t.nama}</option>`);
        }
    }

    // Load dynamic dropdown values on page load based on current pendukung data
    document.addEventListener('DOMContentLoaded', () => {
        const userRole = "{{ Auth::user()->role }}";
        const selectedKec = "{{ old('kecamatan_id', $pendukung->kecamatan_id) }}";
        const selectedDesa = "{{ old('desa_id', $pendukung->desa_id) }}";
        const selectedTps = "{{ old('tps_id', $pendukung->tps_id) }}";

        if (userRole === 'admin_partai' && selectedKec) {
            loadDesa(selectedKec, selectedDesa);
        }
        if ((userRole === 'admin_partai' || userRole === 'korcam') && selectedDesa) {
            loadTps(selectedDesa, selectedTps);
        }
    });
</script>
@endpush
