<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 border-b dark:border-gray-700 border-gray-200 pb-5">
        <a href="{{ route('pemetaan-dukungan.index') }}" 
           class="inline-flex items-center justify-center p-2 rounded-lg border dark:border-gray-700 border-gray-300 dark:text-gray-400 text-gray-500 dark:hover:bg-gray-750 hover:bg-gray-100 transition">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
        </a>
        <div>
            <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold">// Pemetaan Dukungan</p>
            <h2 class="font-display text-3xl tracking-wide dark:text-white text-gray-900 mt-1">STATISTIK SEBARAN</h2>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total Supporters --}}
        <div class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-6 flex items-center gap-5 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-[var(--admin-primary-soft)] flex items-center justify-center text-[var(--admin-primary)] flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-variation-filled">groups</span>
            </div>
            <div>
                <p class="text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider">Total Dukungan Terdata</p>
                <h3 class="font-display text-3xl dark:text-white text-gray-900 mt-1">{{ number_format($totalPendukung) }} Orang</h3>
            </div>
        </div>

        {{-- Wilayah Terdata --}}
        <div class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-6 flex items-center gap-5 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-variation-filled">map</span>
            </div>
            <div>
                <p class="text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider">Scope Wilayah Kerja</p>
                <h3 class="font-display text-3xl dark:text-white text-gray-900 mt-1">
                    @if(Auth::user()->role === 'admin_partai')
                        {{ count($sebaranWilayah) }} Kecamatan
                    @else
                        {{ count($sebaranWilayah) }} Desa
                    @endif
                </h3>
            </div>
        </div>

        {{-- Target Relasi --}}
        <div class="dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-6 flex items-center gap-5 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500 flex-shrink-0">
                <span class="material-symbols-outlined text-2xl font-variation-filled">done_all</span>
            </div>
            <div>
                <p class="text-[10px] font-semibold dark:text-gray-500 text-gray-400 uppercase tracking-wider">Metode Pengumpulan</p>
                <h3 class="font-display text-3xl dark:text-white text-gray-900 mt-1">Survei Internal</h3>
            </div>
        </div>
    </div>

    {{-- Detail Distribution List --}}
    <div class="mt-6 dark:bg-gray-800 bg-white border dark:border-gray-700 border-gray-200 rounded-xl p-6 shadow-sm" style="margin-top: 1.5rem;">
        <h4 class="font-display text-xl tracking-wide dark:text-white text-gray-900 mb-6">
            @if(Auth::user()->role === 'admin_partai')
                DISTRIBUSI PENDUKUNG PER KECAMATAN
            @else
                DISTRIBUSI PENDUKUNG PER DESA
            @endif
        </h4>

        <div class="space-y-4">
            @php
                $maxJumlah = collect($sebaranWilayah)->max('jumlah') ?: 1;
            @endphp
            @forelse($sebaranWilayah as $item)
                @php
                    $pct = ($item['jumlah'] / $maxJumlah) * 100;
                @endphp
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="dark:text-gray-200 text-gray-800">
                            @if(Auth::user()->role === 'admin_partai')
                                Kecamatan {{ $item['nama'] }}
                            @else
                                Desa {{ $item['nama'] }}
                            @endif
                        </span>
                        <span class="dark:text-white text-gray-900">{{ number_format($item['jumlah']) }} Pendukung</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-850 h-3 rounded-full overflow-hidden">
                        <div class="bg-[var(--admin-primary)] h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-center py-8 text-xs text-gray-400 dark:text-gray-500">Belum ada data distribusi wilayah.</p>
            @endforelse
        </div>
    </div>
</div>
