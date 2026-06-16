@php
    $summary = $electionSummary ?? ['scope_label' => '-', 'sections' => []];
    $overview = $summary['overview'] ?? [
        'total_suara_garuda' => 0,
        'total_tps' => 0,
        'input_tps' => 0,
        'missing_tps_count' => 0,
        'missing_tps' => [],
        'review_tps_count' => 0,
        'review_tps' => [],
        'regions' => ['label' => null, 'strong' => [], 'weak' => []],
    ];
    $sections = collect($summary['sections'] ?? []);
    $missingTps = collect($overview['missing_tps'] ?? []);
    $reviewTps = collect($overview['review_tps'] ?? []);
    $regions = $overview['regions'] ?? ['label' => null, 'strong' => [], 'weak' => []];
    $strongRegions = collect($regions['strong'] ?? []);
    $weakRegions = collect($regions['weak'] ?? []);
    $canExportTpsReports = auth()->user()?->role === 'admin';
@endphp

<section class="mb-12">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="admin-mono admin-muted-soft tracking-[.24em] text-[10px] uppercase">// Pemenang Sementara</p>
            <h2 class="admin-display text-3xl lg:text-4xl admin-text leading-tight mt-1">RINGKASAN PEMILIHAN AKTIF</h2>
        </div>
        <p class="admin-mono admin-muted-soft text-[11px] uppercase">{{ $summary['scope_label'] ?? '-' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5 mb-5">
        <div class="admin-glass rounded-lg p-5">
            <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.2em]">Total Suara Garuda</p>
            <p class="admin-display admin-text text-4xl leading-none mt-2">{{ number_format($overview['total_suara_garuda'] ?? 0) }}</p>
            <p class="admin-muted text-xs mt-2">Akumulasi suara partai dan caleg Garuda.</p>
        </div>
        <div class="admin-glass rounded-lg p-5">
            <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.2em]">TPS Masuk</p>
            <p class="admin-display admin-text text-4xl leading-none mt-2">{{ number_format($overview['input_tps'] ?? 0) }}/{{ number_format($overview['total_tps'] ?? 0) }}</p>
            <p class="admin-muted text-xs mt-2">TPS yang sudah memiliki input rekap aktif.</p>
        </div>
        <div class="admin-glass rounded-lg p-5">
            <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.2em]">TPS Belum Masuk</p>
            <p class="admin-display role-accent text-4xl leading-none mt-2">{{ number_format($overview['missing_tps_count'] ?? 0) }}</p>
            <p class="admin-muted text-xs mt-2">Prioritas follow up struktur lapangan.</p>
            @if($canExportTpsReports)
                <a href="{{ route('admin.rekap.export.missing-tps') }}" class="admin-mono role-accent inline-flex items-center gap-1 text-[10px] font-bold uppercase mt-3">
                    Export <span class="material-symbols-outlined text-sm">download</span>
                </a>
            @endif
        </div>
        <div class="admin-glass rounded-lg p-5">
            <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.2em]">TPS Perlu Dicek</p>
            <p class="admin-display role-accent text-4xl leading-none mt-2">{{ number_format($overview['review_tps_count'] ?? 0) }}</p>
            <p class="admin-muted text-xs mt-2">TPS bermasalah yang butuh koreksi internal.</p>
            @if($canExportTpsReports)
                <a href="{{ route('admin.rekap.export.review-tps') }}" class="admin-mono role-accent inline-flex items-center gap-1 text-[10px] font-bold uppercase mt-3">
                    Export <span class="material-symbols-outlined text-sm">download</span>
                </a>
            @endif
        </div>
        <div class="admin-glass rounded-lg p-5">
            <p class="admin-mono admin-muted-soft text-[10px] uppercase tracking-[.2em]">Jenis Aktif</p>
            <p class="admin-display admin-text text-4xl leading-none mt-2">{{ number_format($overview['active_jenis_count'] ?? 0) }}</p>
            <p class="admin-muted text-xs mt-2">DPR RI, DPRD Provinsi, dan DPRD Kabupaten.</p>
        </div>
    </div>

    @if($missingTps->isNotEmpty())
        <div class="admin-glass rounded-lg p-5 mb-5">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="admin-display admin-text text-2xl leading-none uppercase">TPS Belum Masuk</p>
                    <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">5 prioritas pertama</p>
                </div>
                <span class="material-symbols-outlined role-accent text-xl">assignment_late</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2">
                @foreach($missingTps as $tps)
                    <div class="admin-surface-strong rounded-md px-3 py-2.5">
                        <p class="admin-text text-sm font-semibold truncate">{{ $tps['label'] }}</p>
                        <p class="admin-mono admin-muted-soft text-[10px] uppercase truncate">{{ $tps['meta'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($reviewTps->isNotEmpty())
        <div class="admin-glass rounded-lg p-5 mb-5">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="admin-display admin-text text-2xl leading-none uppercase">TPS Perlu Dicek</p>
                    <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">5 koreksi internal terbaru</p>
                </div>
                <span class="material-symbols-outlined role-accent text-xl">report</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2">
                @foreach($reviewTps as $tps)
                    <div class="admin-surface-strong rounded-md px-3 py-2.5">
                        <p class="admin-text text-sm font-semibold truncate">{{ $tps['label'] }}</p>
                        <p class="admin-mono admin-muted-soft text-[10px] uppercase truncate">{{ $tps['meta'] }}</p>
                        @if(!empty($tps['note']))
                            <p class="admin-muted text-xs mt-1 line-clamp-2">{{ $tps['note'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(($regions['label'] ?? null) && ($strongRegions->isNotEmpty() || $weakRegions->isNotEmpty()))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
            <div class="admin-glass rounded-lg p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="admin-display admin-text text-2xl leading-none uppercase">{{ $regions['label'] }} Kuat</p>
                        <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">3 suara Garuda tertinggi</p>
                    </div>
                    <span class="material-symbols-outlined role-accent text-xl">trending_up</span>
                </div>
                <div class="space-y-2">
                    @forelse($strongRegions as $region)
                        <div class="flex items-center justify-between gap-3 admin-surface-strong rounded-md px-3 py-2.5">
                            <p class="admin-text text-sm font-semibold truncate">{{ $region['label'] }}</p>
                            <p class="admin-mono text-xs font-bold text-gray-950 dark:text-white whitespace-nowrap">{{ number_format($region['suara']) }}</p>
                        </div>
                    @empty
                        <p class="admin-muted text-sm">Belum ada data wilayah kuat.</p>
                    @endforelse
                </div>
            </div>
            <div class="admin-glass rounded-lg p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="admin-display admin-text text-2xl leading-none uppercase">{{ $regions['label'] }} Lemah</p>
                        <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">3 suara Garuda terendah</p>
                    </div>
                    <span class="material-symbols-outlined role-accent text-xl">trending_down</span>
                </div>
                <div class="space-y-2">
                    @forelse($weakRegions as $region)
                        <div class="flex items-center justify-between gap-3 admin-surface-strong rounded-md px-3 py-2.5">
                            <p class="admin-text text-sm font-semibold truncate">{{ $region['label'] }}</p>
                            <p class="admin-mono text-xs font-bold text-gray-950 dark:text-white whitespace-nowrap">{{ number_format($region['suara']) }}</p>
                        </div>
                    @empty
                        <p class="admin-muted text-sm">Belum ada data wilayah lemah.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($sections->isEmpty())
        <div class="admin-glass rounded-lg p-6">
            <p class="admin-muted text-sm">Belum ada pemilihan aktif atau data rekap belum tersedia.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sections as $section)
                @php
                    $rows = collect($section['rows'] ?? []);
                    $top = $rows->first();
                    $headline = $top && (int) ($top['suara'] ?? 0) > 0
                        ? 'Unggul sementara: ' . $top['label'] . (!empty($top['meta']) ? ' - ' . $top['meta'] : '')
                        : 'Belum ada suara';
                @endphp
                <article class="admin-glass rounded-lg p-5">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="admin-display admin-text tracking-wide text-2xl leading-none uppercase">{{ $section['title'] }}</p>
                            <h3 class="admin-text text-base font-bold truncate mt-1">{{ $headline }}</h3>
                            <p class="admin-mono admin-muted-soft text-[10px] uppercase mt-1">{{ $section['subtitle'] }}</p>
                        </div>
                        <span class="material-symbols-outlined role-accent text-xl">leaderboard</span>
                    </div>

                    <div class="space-y-2">
                        @forelse($rows as $row)
                            <div class="flex items-center justify-between gap-3 rounded-md admin-surface-strong px-3 py-2.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="admin-mono text-gray-950 dark:text-white text-xs font-bold w-5 shrink-0">{{ $row['rank'] }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold admin-text truncate">{{ $row['label'] }}</p>
                                        @if(!empty($row['meta']))
                                            <p class="admin-mono text-gray-950 dark:text-gray-400 text-[10px] uppercase truncate">{{ $row['meta'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="admin-mono text-xs font-bold text-gray-950 dark:text-white whitespace-nowrap">{{ number_format($row['suara']) }}</p>
                                    <p class="admin-mono text-[10px] font-semibold role-accent whitespace-nowrap">{{ number_format($row['persentase'] ?? 0, 2) }}%</p>
                                </div>
                            </div>
                        @empty
                            <p class="admin-muted text-sm">Belum ada data suara untuk jenis ini.</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
