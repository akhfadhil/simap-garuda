@extends('layouts.app')
@section('title', 'Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')

{{-- Header --}}
<div class="mb-8 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('admin.rekap.index') }}"
           class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
            ← Kembali
        </a>
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin — Rekapitulasi</p>
        <h1 class="font-display text-4xl tracking-[2px] text-red-600">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
    </div>
    <button onclick="openExportModal()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition flex-shrink-0">
        ↓ Export Excel
    </button>
</div>

@if($jenis === 'dprd_kab')
<form method="GET" action="{{ route('admin.rekap.show', $jenis) }}"
      class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-4 mb-6">
    <label for="dapil_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
        Dapil DPRD Kabupaten
    </label>
    <select id="dapil_id" name="dapil_id" onchange="this.form.submit()"
            class="w-full md:w-80 dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
        @foreach($dapils as $dapil)
        <option value="{{ $dapil->id }}" {{ (int) $selectedDapilId === (int) $dapil->id ? 'selected' : '' }}>
            {{ $dapil->nama }}
        </option>
        @endforeach
    </select>
</form>
@endif

{{-- Summary cards --}}
@php
    $totalDpt    = $rekaps->sum(fn($r) => $r->dpt_lk + $r->dpt_pr);
    $totalHadir  = $rekaps->sum(fn($r) => $r->pengguna_dpt_lk + $r->pengguna_dpt_pr + $r->pengguna_dptb_lk + $r->pengguna_dptb_pr + $r->pengguna_dpk_lk + $r->pengguna_dpk_pr);
    $totalTdkSah = $rekaps->sum('suara_tidak_sah');
    $totalFinal  = $rekaps->where('status','final')->count();
    $totalRekap  = $rekaps->count();
    $showDetail  = request()->boolean('detail');
    $canUnlockRekap = Auth::user()->role === 'admin';
    $detailBaseQuery = request()->except(['detail', 'detail_kecamatan_id', 'detail_desa_id']);
    $detailBaseUrl = route('admin.rekap.show', $jenis) . (count($detailBaseQuery) ? '?' . http_build_query($detailBaseQuery) : '');

    $rows1 = [
        ['label'=>'DPT Laki-laki',            'field'=>'dpt_lk'],
        ['label'=>'DPT Perempuan',             'field'=>'dpt_pr'],
        ['label'=>'DPT Jumlah',                'sum'=>['dpt_lk','dpt_pr'], 'bold'=>true],
        ['label'=>'Pengguna DPT LK',           'field'=>'pengguna_dpt_lk'],
        ['label'=>'Pengguna DPT PR',           'field'=>'pengguna_dpt_pr'],
        ['label'=>'Pengguna DPT Jumlah',       'sum'=>['pengguna_dpt_lk','pengguna_dpt_pr'], 'bold'=>true],
        ['label'=>'Pengguna DPTB LK',          'field'=>'pengguna_dptb_lk'],
        ['label'=>'Pengguna DPTB PR',          'field'=>'pengguna_dptb_pr'],
        ['label'=>'Pengguna DPTB Jumlah',      'sum'=>['pengguna_dptb_lk','pengguna_dptb_pr'], 'bold'=>true],
        ['label'=>'Pengguna DPK LK',           'field'=>'pengguna_dpk_lk'],
        ['label'=>'Pengguna DPK PR',           'field'=>'pengguna_dpk_pr'],
        ['label'=>'Pengguna DPK Jumlah',       'sum'=>['pengguna_dpk_lk','pengguna_dpk_pr'], 'bold'=>true],
        ['label'=>'Total Pengguna Hak Pilih LK', 'sum'=>['pengguna_dpt_lk','pengguna_dptb_lk','pengguna_dpk_lk'], 'bold'=>true],
        ['label'=>'Total Pengguna Hak Pilih PR', 'sum'=>['pengguna_dpt_pr','pengguna_dptb_pr','pengguna_dpk_pr'], 'bold'=>true],
        ['label'=>'Total Pengguna Hak Pilih',  'sum'=>['pengguna_dpt_lk','pengguna_dpt_pr','pengguna_dptb_lk','pengguna_dptb_pr','pengguna_dpk_lk','pengguna_dpk_pr'], 'bold'=>true],
    ];
    $rows2 = [
        ['label'=>'Surat Suara Diterima',  'field'=>'ss_diterima'],
        ['label'=>'Surat Suara Digunakan', 'field'=>'ss_digunakan'],
        ['label'=>'Surat Suara Rusak',     'field'=>'ss_rusak'],
        ['label'=>'Surat Suara Sisa',      'field'=>'ss_sisa', 'bold'=>true],
    ];
    $rows3 = [
        ['label'=>'Disabilitas Laki-laki', 'field'=>'disabilitas_lk'],
        ['label'=>'Disabilitas Perempuan', 'field'=>'disabilitas_pr'],
        ['label'=>'Disabilitas Jumlah',    'sum'=>['disabilitas_lk','disabilitas_pr'], 'bold'=>true],
    ];

    // Helper: ambil agregat per kecamatan yang sudah dihitung di controller.
    $getKecVal = function($kecamatan, $row) use ($kecStats) {
        $stats = $kecStats[$kecamatan->id] ?? [];
        return isset($row['field'])
            ? ($stats[$row['field']] ?? 0)
            : collect($row['sum'])->sum(fn($f) => $stats[$f] ?? 0);
    };

    $canFlagCells = Auth::user()->role === 'admin';
    $rowKeyFor = fn($row) => isset($row['field']) && $row['field'] ? $row['field'] : 'sum:' . implode('+', $row['sum']);
    $totalPenggunaRowKey = 'sum:pengguna_dpt_lk+pengguna_dpt_pr+pengguna_dptb_lk+pengguna_dptb_pr+pengguna_dpk_lk+pengguna_dpk_pr';
    $mismatchRowKeys = [$totalPenggunaRowKey, 'ss_digunakan'];
    $isMismatchRow = fn(string $rowKey) => in_array($rowKey, $mismatchRowKeys, true);
    $statsTotalPengguna = fn(array $stats) => (int) ($stats['pengguna_dpt_lk'] ?? 0) + (int) ($stats['pengguna_dpt_pr'] ?? 0) + (int) ($stats['pengguna_dptb_lk'] ?? 0) + (int) ($stats['pengguna_dptb_pr'] ?? 0) + (int) ($stats['pengguna_dpk_lk'] ?? 0) + (int) ($stats['pengguna_dpk_pr'] ?? 0);
    $statsHasMismatch = fn(array $stats) => $statsTotalPengguna($stats) !== (int) ($stats['ss_digunakan'] ?? 0);
    $rekapTotalPengguna = fn($r) => (int) $r->pengguna_dpt_lk + (int) $r->pengguna_dpt_pr + (int) $r->pengguna_dptb_lk + (int) $r->pengguna_dptb_pr + (int) $r->pengguna_dpk_lk + (int) $r->pengguna_dpk_pr;
    $rekapHasMismatch = fn($r) => $r && $rekapTotalPengguna($r) !== (int) $r->ss_digunakan;
    $collectionHasMismatch = fn($items) => $items->sum(fn($r) => $rekapTotalPengguna($r)) !== $items->sum('ss_digunakan');
    $flaggedClasses = 'bg-red-500/20 text-red-600 dark:bg-red-500/20 dark:text-red-200 ring-1 ring-inset ring-red-400/60';
    $renderKecFlaggedCell = function($kecamatan, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($kecCellFlags, $kecDirectCellFlags, $canFlagCells, $jenis, $flaggedClasses) {
        $flagged = $kecCellFlags->has($kecamatan->id . ':' . $rowKey);
        $directFlagged = $kecDirectCellFlags->has($kecamatan->id . ':' . $rowKey);
        $classes = trim($baseClass . ' relative group ' . (($autoFlagged || $flagged) ? $flaggedClasses : ''));
        $content = is_null($value) ? '&mdash;' : number_format($value);
        $button = '';

        if ($canFlagCells) {
            $buttonClass = $directFlagged
                ? 'opacity-100 bg-red-500 text-white border-red-500'
                : 'opacity-0 group-hover:opacity-100 bg-white dark:bg-gray-900 text-red-500 border-red-400';
            $buttonTitle = $directFlagged ? 'Hapus tanda merah dari kabupaten' : 'Tandai merah dari kabupaten';
            $button = '<form method="POST" action="' . e(route('admin.rekap.cell-flag', $jenis)) . '" data-flag-form class="absolute top-1 right-1 transition-opacity ' . ($directFlagged ? 'opacity-100' : 'opacity-0 group-hover:opacity-100') . '">'
                . csrf_field()
                . '<input type="hidden" name="level" value="kecamatan">'
                . '<input type="hidden" name="entity_id" value="' . e($kecamatan->id) . '">'
                . '<input type="hidden" name="row_key" value="' . e($rowKey) . '">'
                . '<button type="submit" title="' . e($buttonTitle) . '" class="js-flag-button block w-4 h-4 rounded-full border text-[10px] leading-3 font-bold ' . $buttonClass . '">!</button>'
                . '</form>';
        }

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '" data-flag-scope="kec" data-kec-id="' . e($kecamatan->id) . '" data-row-key="' . e($rowKey) . '" data-direct-flagged="' . ($directFlagged ? '1' : '0') . '" data-auto-flagged="' . ($autoFlagged ? '1' : '0') . '"><span>' . $content . '</span>' . $button . '</td>');
    };
    $isInlineEditableRow = function(string $rowKey) {
        $baseEditable = [
            'dpt_lk', 'dpt_pr',
            'pengguna_dpt_lk', 'pengguna_dpt_pr',
            'pengguna_dptb_lk', 'pengguna_dptb_pr',
            'pengguna_dpk_lk', 'pengguna_dpk_pr',
            'ss_diterima', 'ss_digunakan', 'ss_rusak',
            'disabilitas_lk', 'disabilitas_pr',
            'suara_tidak_sah',
        ];

        return in_array($rowKey, $baseEditable, true)
            || str_starts_with($rowKey, 'calon:')
            || str_starts_with($rowKey, 'partai:')
            || str_starts_with($rowKey, 'caleg:');
    };
    $renderAdminTpsCell = function($tps, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($tpsCellFlags, $canFlagCells, $jenis, $flaggedClasses, $isInlineEditableRow) {
        $flagged = $tpsCellFlags->has($tps->id . ':' . $rowKey);
        $classes = trim($baseClass . ' relative group ' . (($autoFlagged || $flagged) ? $flaggedClasses : ''));
        $rawValue = is_null($value) ? 0 : (int) $value;
        $content = is_null($value) ? '&mdash;' : number_format($value);
        $editableAttrs = $isInlineEditableRow($rowKey)
            ? ' data-inline-editable="1" data-edit-original="' . e($rawValue) . '" data-edit-value="' . e($rawValue) . '"'
            : '';
        $button = '';

        if ($canFlagCells) {
            $buttonClass = $flagged
                ? 'opacity-100 bg-red-500 text-white border-red-500'
                : 'opacity-0 group-hover:opacity-100 bg-white dark:bg-gray-900 text-red-500 border-red-400';
            $buttonTitle = $flagged ? 'Hapus tanda merah' : 'Tandai merah';
            $button = '<form method="POST" action="' . e(route('admin.rekap.cell-flag', $jenis)) . '" data-flag-form class="absolute top-1 right-1 transition-opacity ' . ($flagged ? 'opacity-100' : 'opacity-0 group-hover:opacity-100') . '">'
                . csrf_field()
                . '<input type="hidden" name="level" value="tps">'
                . '<input type="hidden" name="entity_id" value="' . e($tps->id) . '">'
                . '<input type="hidden" name="row_key" value="' . e($rowKey) . '">'
                . '<button type="submit" title="' . e($buttonTitle) . '" class="js-flag-button block w-4 h-4 rounded-full border text-[10px] leading-3 font-bold ' . $buttonClass . '">!</button>'
                . '</form>';
        }

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '" data-flag-scope="tps" data-tps-id="' . e($tps->id) . '" data-desa-id="' . e($tps->desa_id) . '" data-row-key="' . e($rowKey) . '" data-direct-flagged="' . ($flagged ? '1' : '0') . '" data-auto-flagged="' . ($autoFlagged ? '1' : '0') . '"' . $editableAttrs . '><span class="js-cell-value">' . $content . '</span>' . $button . '</td>');
    };
    $renderAdminFlaggedTotalCell = function($desa, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($desaCellFlags, $kecDirectCellFlags, $flaggedClasses) {
        $parentFlagged = $kecDirectCellFlags->has($desa->kecamatan_id . ':' . $rowKey);
        $classes = trim($baseClass . ' ' . (($autoFlagged || $desaCellFlags->has($desa->id . ':' . $rowKey)) ? $flaggedClasses : ''));
        $content = is_null($value) ? '&mdash;' : number_format($value);

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '" data-flag-scope="desa" data-desa-id="' . e($desa->id) . '" data-row-key="' . e($rowKey) . '" data-parent-flagged="' . ($parentFlagged ? '1' : '0') . '" data-auto-flagged="' . ($autoFlagged ? '1' : '0') . '"><span>' . $content . '</span></td>');
    };
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total DPT</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalDpt) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total Hadir</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalHadir) }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">{{ $totalDpt > 0 ? round(($totalHadir/$totalDpt)*100,1) : 0 }}% partisipasi</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Suara Tidak Sah</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalTdkSah) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">TPS Terisi</p>
        <p class="font-display text-3xl text-red-600">{{ $totalFinal }}/{{ $totalRekap }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">difinalisasi</p>
    </div>
</div>

{{-- ══════════════════════════════════════
     REKAP TOTAL KABUPATEN (kolom = kecamatan)
══════════════════════════════════════ --}}
<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Rekap Total Kabupaten</p>
</div>

{{-- ══ SATU TABEL BESAR REKAP TOTAL KABUPATEN ══ --}}
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-4">
    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:220px">
                @foreach($kecamatans as $__kec) <col style="width:110px"> @endforeach
                <col style="width:110px">
            </colgroup>
            <thead>
                <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                    <th class="text-left px-5 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold truncate">Keterangan</th>
                    @foreach($kecamatans as $kec)
                    <th class="text-center px-3 py-3 text-[10px] uppercase font-semibold whitespace-nowrap
                        {{ (($kecStats[$kec->id]['final'] ?? 0) > 0)
                           ? 'dark:text-gray-500 text-gray-400' : 'text-red-400' }}">
                        {{ $kec->nama }}
                    </th>
                    @endforeach
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>

            {{-- ── Section I ── --}}
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Section I — DPT &amp; Pengguna Hak Pilih
                </td>
            </tr>
            @foreach($rows1 as $row)
            @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                <td class="px-5 py-2 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                @foreach($kecamatans as $kec)
                @php
                    $rowKey = $rowKeyFor($row);
                    $val = $getKecVal($kec, $row);
                    $rowTotal += $val;
                    $autoFlagged = $isMismatchRow($rowKey) && $statsHasMismatch($kecStats[$kec->id] ?? []);
                @endphp
                {!! $renderKecFlaggedCell($kec, $rowKey, $val, 'px-3 py-2 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-red-500 {{ $isMismatchRow($rowKeyFor($row)) && $collectionHasMismatch($rekaps) ? $flaggedClasses : '' }}">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach

            {{-- ── Section II ── --}}
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Section II — Surat Suara
                </td>
            </tr>
            @foreach($rows2 as $row)
            @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                <td class="px-5 py-2 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                @foreach($kecamatans as $kec)
                @php
                    $rowKey = $rowKeyFor($row);
                    $val = $getKecVal($kec, $row);
                    $rowTotal += $val;
                    $autoFlagged = $isMismatchRow($rowKey) && $statsHasMismatch($kecStats[$kec->id] ?? []);
                @endphp
                {!! $renderKecFlaggedCell($kec, $rowKey, $val, 'px-3 py-2 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-red-500 {{ $isMismatchRow($rowKeyFor($row)) && $collectionHasMismatch($rekaps) ? $flaggedClasses : '' }}">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach

            {{-- ── Section III ── --}}
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Section III — Pemilih Disabilitas
                </td>
            </tr>
            @foreach($rows3 as $row)
            @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                <td class="px-5 py-2 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                @foreach($kecamatans as $kec)
                @php $val = $getKecVal($kec, $row); $rowTotal += $val; @endphp
                {!! $renderKecFlaggedCell($kec, $rowKeyFor($row), $val, 'px-3 py-2 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500')) !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-red-500">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach

            {{-- ── Section IV ── --}}
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Section IV — Perolehan Suara
                </td>
            </tr>
            @if(in_array($jenis, ['ppwp','dpd','gubernur','bupati']))
            @foreach($master['calons'] as $calon)
            @php $rowTotal = 0; $name = in_array($jenis, ['ppwp','gubernur','bupati']) ? $calon->nama_paslon : $calon->nama_calon; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2.5 text-sm dark:text-gray-200 text-gray-700">
                    <span class="text-xs dark:text-gray-500 text-gray-400 mr-1">{{ $calon->nomor_urut }}.</span>{{ $name }}
                </td>
                @foreach($kecamatans as $kec)
                @php
                    $val = $kecCalonTotals[$kec->id][$calon->id] ?? 0;
                    $rowTotal += $val;
                @endphp
                {!! $renderKecFlaggedCell($kec, 'calon:' . $calon->id, $val, 'px-3 py-2.5 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                <td class="px-3 py-2.5 text-center font-bold text-red-500">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach
            @else
            @foreach($master['partais'] as $partai)
            <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                    {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                </td>
            </tr>
                @php $partaiRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                    <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                    @foreach($kecamatans as $kec)
                    @php $spKec = $kecPartaiTotals[$kec->id][$partai->id] ?? 0; $partaiRowTotal += $spKec; @endphp
                    {!! $renderKecFlaggedCell($kec, 'partai:' . $partai->id, $spKec, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-red-500">{{ number_format($partaiRowTotal) }}</td>
                </tr>
                @foreach($partai->calegs as $caleg)
                @php $calegRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-2"><div class="flex items-center gap-2"><span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span><span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span></div></td>
                    @foreach($kecamatans as $kec)
                    @php $scKec = $kecCalegTotals[$kec->id][$caleg->id] ?? 0; $calegRowTotal += $scKec; @endphp
                    {!! $renderKecFlaggedCell($kec, 'caleg:' . $caleg->id, $scKec, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-teal-400">{{ number_format($calegRowTotal) }}</td>
                </tr>
                @endforeach
                @php $grandTotal = 0; @endphp
                <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                    <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Sah</td>
                    @foreach($kecamatans as $kec)
                    @php $colTotal = $kecPartaiGrandTotals[$kec->id][$partai->id] ?? 0; $grandTotal += $colTotal; @endphp
                    {!! $renderKecFlaggedCell($kec, 'partai_total:' . $partai->id, $colTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-teal-400">{{ number_format($grandTotal) }}</td>
                </tr>
            @endforeach
            @endif

            {{-- ── Section V ── --}}
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Section V — Suara Sah, Tidak Sah &amp; Total
                </td>
            </tr>
            @foreach([['label'=>'Jumlah Suara Sah','field'=>'suara_sah'],['label'=>'Jumlah Suara Tidak Sah','field'=>'suara_tidak_sah']] as $row)
            @php $rowTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2 text-sm dark:text-gray-300 text-gray-600">{{ $row['label'] }}</td>
                @foreach($kecamatans as $kec)
                @php $val = $getKecVal($kec, $row); $rowTotal += $val; @endphp
                {!! $renderKecFlaggedCell($kec, $row['field'], $val, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-red-500">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach
            @php $rowTotalAll = 0; @endphp
            <tr class="dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2 text-sm font-bold dark:text-gray-200 text-gray-800">Jumlah Seluruh Suara</td>
                @foreach($kecamatans as $kec)
                @php $val = $kecStats[$kec->id]['suara_total'] ?? 0; $rowTotalAll += $val; @endphp
                {!! $renderKecFlaggedCell($kec, 'suara_total', $val, 'px-3 py-2 text-center font-bold dark:text-gray-200 text-gray-700') !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-red-500">{{ number_format($rowTotalAll) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════
     DETAIL PER KECAMATAN (accordion)
══════════════════════════════════════ --}}
<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Detail Per Kecamatan</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('admin.rekap.show', $jenis) }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
        @foreach($detailBaseQuery as $key => $value)
            @if(is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="flex-1">
            <label for="detail_kecamatan_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Pilih Kecamatan
            </label>
            <select id="detail_kecamatan_id" name="detail_kecamatan_id" onchange="loadDetailDesa(this.value)"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                <option value="">— Pilih Kecamatan —</option>
                @foreach($kecamatans as $kec)
                <option value="{{ $kec->id }}" {{ (int) $detailKecamatanId === (int) $kec->id ? 'selected' : '' }}>
                    {{ $kec->nama }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label for="detail_desa_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Pilih Desa
            </label>
            <select id="detail_desa_id" name="detail_desa_id"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none"
                    {{ $detailDesaOptions->isEmpty() ? 'disabled' : '' }}>
                <option value="">Semua Desa</option>
                @foreach($detailDesaOptions as $desaOption)
                <option value="{{ $desaOption->id }}" {{ (int) $detailDesaId === (int) $desaOption->id ? 'selected' : '' }}>
                    {{ $desaOption->nama }}
                </option>
                @endforeach
            </select>
        </div>

        <button type="submit" name="detail" value="1"
                class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition whitespace-nowrap">
            Tampilkan Detail
        </button>
        @if($showDetail)
    <a href="{{ $detailBaseUrl }}"
       class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
        Sembunyikan Detail
    </a>
        @endif
    </form>

    @if($showDetail && $canUnlockRekap)
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t dark:border-gray-700 border-gray-200 pt-4">
        <p id="inline-edit-status" class="text-xs dark:text-gray-500 text-gray-400">
            Edit inline aktif untuk cell TPS pada detail yang sedang ditampilkan.
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="inline-edit-toggle"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition">
                Edit
            </button>
            <button type="button" id="inline-edit-save"
                    class="hidden inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold bg-teal-500 hover:bg-teal-600 text-white transition">
                Simpan
            </button>
        </div>
    </div>
    @endif
</div>

@if(!$showDetail || $detailKecamatans->isEmpty())
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-6 mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Detail TPS tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
        Halaman awal dibuat ringan. Pilih satu kecamatan untuk melihat rincian per desa dan TPS.
    </p>
</div>
@else
@foreach($detailKecamatans as $kecamatan)
@php
    $kecTpsIds = $kecamatan->desas->flatMap(fn($d) => $d->tps->pluck('id'))->toArray();
    $kecRekaps = $detailRekaps->whereIn('tps_id', $kecTpsIds);
    $kecFinal  = $kecRekaps->where('status','final')->count();
    $kecHasFlag = $kecCellFlags->keys()->contains(fn($key) => str_starts_with($key, $kecamatan->id . ':'));
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">

    {{-- Header accordion kecamatan --}}
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200 cursor-pointer dark:hover:bg-gray-750 hover:bg-gray-50 transition"
         onclick="toggleKec({{ $kecamatan->id }})">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $kecamatan->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $kecFinal }}/{{ count($kecTpsIds) }} TPS difinalisasi</p>
        </div>
        <div class="flex items-center gap-3">
            <span title="Ada data yang perlu diperbaiki"
                  data-progress-alert="kec"
                  data-kec-id="{{ $kecamatan->id }}"
                  class="{{ $kecHasFlag ? 'inline-flex' : 'hidden' }} h-5 w-5 items-center justify-center rounded-full border border-red-400 bg-red-500 text-[10px] font-bold leading-none text-white shadow-sm">!</span>
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-red-500" style="width:{{ count($kecTpsIds) > 0 ? round(($kecFinal/count($kecTpsIds))*100) : 0 }}%"></div>
            </div>
            <span id="arrow-kec-{{ $kecamatan->id }}" class="dark:text-gray-500 text-gray-400 text-xs">▸</span>
        </div>
    </div>

    {{-- Isi accordion: per desa → TPS sebagai kolom --}}
    <div id="kec-{{ $kecamatan->id }}" class="hidden">
    @foreach($kecamatan->desas as $desa)
    @php
        $desaTpsIds = $desa->tps->pluck('id')->toArray();
        $desaRekaps = $detailRekaps->whereIn('tps_id', $desaTpsIds);
        $desaFinal  = $detailRekaps->whereIn('tps_id', $desaTpsIds)->where('status','final')->count();
        $desaHasFlag = $desaCellFlags->keys()->contains(fn($key) => str_starts_with($key, $desa->id . ':'));
        $desaAutoMismatch = $collectionHasMismatch($desaRekaps);
    @endphp

    {{-- Sub-header desa --}}
    <div class="flex items-center justify-between px-6 py-3 dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-100 cursor-pointer"
         onclick="toggleDesa({{ $desa->id }})">
        <div>
            <p class="text-xs font-semibold dark:text-gray-300 text-gray-600">{{ $desa->nama }}</p>
            <p class="text-[10px] dark:text-gray-500 text-gray-400">{{ $desaFinal }}/{{ $desa->tps->count() }} TPS difinalisasi</p>
        </div>
        <div class="flex items-center gap-3">
            <span title="Ada data yang perlu diperbaiki"
                  data-progress-alert="desa"
                  data-desa-id="{{ $desa->id }}"
                  class="{{ $desaHasFlag ? 'inline-flex' : 'hidden' }} h-5 w-5 items-center justify-center rounded-full border border-red-400 bg-red-500 text-[10px] font-bold leading-none text-white shadow-sm">!</span>
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-red-500" style="width:{{ $desa->tps->count() > 0 ? round(($desaFinal/$desa->tps->count())*100) : 0 }}%"></div>
            </div>
        </div>
        <span id="arrow-desa-{{ $desa->id }}" class="dark:text-gray-500 text-gray-400 text-xs">▸</span>
    </div>

    <div id="desa-{{ $desa->id }}" class="hidden">

        {{-- ══ SATU TABEL BESAR PER DESA ══ --}}
        <div class="overflow-x-auto rekap-table-scroll">
            <table class="w-full text-sm table-fixed rekap-sticky-header">
                <colgroup>
                    <col style="width:220px">
                    @foreach($desa->tps as $__tps) <col style="width:110px"> @endforeach
                    <col style="width:110px">
                </colgroup>
                <thead>
                    <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                        <th class="text-left px-5 py-2 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Keterangan</th>
                        @foreach($desa->tps as $tps)
                        <th class="text-center px-3 py-2 text-[10px] uppercase font-semibold whitespace-nowrap
    {{ ($detailRekaps[$tps->id] ?? null)?->status === 'final'
        ? 'dark:text-gray-500 text-gray-400'
        : 'text-red-400' }}">{{ $tps->nama }}</th>
                        @endforeach
                        <th class="text-center px-3 py-2 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>

                {{-- ── Section I ── --}}
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                        Section I — DPT &amp; Pengguna Hak Pilih
                    </td>
                </tr>
                @foreach($rows1 as $row)
                @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                    <td class="px-5 py-1.5 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                    @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $rowKey = $rowKeyFor($row);
                        $val = $r ? (isset($row['field']) ? ($r->{$row['field']} ?? 0) : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0)) : null;
                        $rowTotal += $val ?? 0;
                        $autoFlagged = $isMismatchRow($rowKey) && $rekapHasMismatch($r);
                    @endphp
                    {!! $renderAdminTpsCell($tps, $rowKey, $r ? $val : null, 'px-3 py-1.5 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, $rowKeyFor($row), $rowTotal, 'px-3 py-1.5 text-center font-bold text-red-500', $isMismatchRow($rowKeyFor($row)) && $desaAutoMismatch) !!}
                </tr>
                @endforeach

                {{-- ── Section II ── --}}
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                        Section II — Surat Suara
                    </td>
                </tr>
                @foreach($rows2 as $row)
                @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                    <td class="px-5 py-1.5 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                    @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $rowKey = $rowKeyFor($row);
                        $val = $r ? ($r->{$row['field']} ?? 0) : null;
                        $rowTotal += $val ?? 0;
                        $autoFlagged = $isMismatchRow($rowKey) && $rekapHasMismatch($r);
                    @endphp
                    {!! $renderAdminTpsCell($tps, $rowKey, $r ? $val : null, 'px-3 py-1.5 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, $rowKeyFor($row), $rowTotal, 'px-3 py-1.5 text-center font-bold text-red-500', $isMismatchRow($rowKeyFor($row)) && $desaAutoMismatch) !!}
                </tr>
                @endforeach

                {{-- ── Section III ── --}}
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                        Section III — Pemilih Disabilitas
                    </td>
                </tr>
                @foreach($rows3 as $row)
                @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                    <td class="px-5 py-1.5 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $val = $r ? (isset($row['field']) ? ($r->{$row['field']} ?? 0) : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0)) : null; $rowTotal += $val ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, $rowKeyFor($row), $r ? $val : null, 'px-3 py-1.5 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500')) !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, $rowKeyFor($row), $rowTotal, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                @endforeach

                {{-- ── Section IV ── --}}
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                        Section IV — Perolehan Suara
                    </td>
                </tr>
                @if(in_array($jenis, ['ppwp','dpd','gubernur','bupati']))
                @foreach($master['calons'] as $calon)
                @php $rowTotal = 0; $name = in_array($jenis, ['ppwp','gubernur','bupati']) ? $calon->nama_paslon : $calon->nama_calon; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-1.5 text-sm dark:text-gray-200 text-gray-700"><span class="text-xs dark:text-gray-500 text-gray-400 mr-1">{{ $calon->nomor_urut }}.</span>{{ $name }}</td>
                    @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $suara = $r
                            ? (
                                in_array($jenis, ['ppwp', 'gubernur', 'bupati'])
                                    ? match ($jenis) {
                                        'ppwp'     => $r->ppwpSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                        'gubernur' => $r->gubernurSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                        'bupati'   => $r->bupatiSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                    }
                                    : ($r->dpdSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0)
                            )
                            : 0;
                        $rowTotal += $suara;
                    @endphp
                    {!! $renderAdminTpsCell($tps, 'calon:' . $calon->id, $r ? $suara : null, 'px-3 py-1.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'calon:' . $calon->id, $rowTotal, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                @endforeach
                @else
                @foreach($master['partais'] as $partai)
                <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                        {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                    </td>
                </tr>
                @php $partaiRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                    <td class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $sp = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : null; $partaiRowTotal += $sp ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'partai:' . $partai->id, $r ? $sp : null, 'px-3 py-1.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'partai:' . $partai->id, $partaiRowTotal, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                @foreach($partai->calegs as $caleg)
                @php $calegRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 last:border-0 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-1.5"><div class="flex items-center gap-2"><span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span><span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span></div></td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $sc = $r ? ($r->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : null; $calegRowTotal += $sc ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'caleg:' . $caleg->id, $r ? $sc : null, 'px-3 py-1.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'caleg:' . $caleg->id, $calegRowTotal, 'px-3 py-1.5 text-center font-bold text-teal-400') !!}
                </tr>
                @endforeach
                @php $grandTotal = 0; @endphp
                <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                    <td class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Sah</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $sp = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0; $sc_sum = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0; $colTotal = $r ? ($sp + $sc_sum) : null; $grandTotal += $colTotal ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'partai_total:' . $partai->id, $r ? $colTotal : null, 'px-3 py-1.5 text-center font-bold text-teal-400') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'partai_total:' . $partai->id, $grandTotal, 'px-3 py-1.5 text-center font-bold text-teal-400') !!}
                </tr>
                @endforeach
                @endif

                {{-- ── Section V ── --}}
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                        Section V — Suara Sah, Tidak Sah &amp; Total
                    </td>
                </tr>
                @php $rowTotalSah = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-1.5 text-sm dark:text-gray-300 text-gray-600">Jumlah Suara Sah</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $sah = $r ? $r->suara_sah : null; $rowTotalSah += $sah ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'suara_sah', $r ? $sah : null, 'px-3 py-1.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'suara_sah', $rowTotalSah, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                @php $rowTotalTdk = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-1.5 text-sm dark:text-gray-300 text-gray-600">Jumlah Suara Tidak Sah</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $tdk = $r ? $r->suara_tidak_sah : null; $rowTotalTdk += $tdk ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'suara_tidak_sah', $r ? $tdk : null, 'px-3 py-1.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'suara_tidak_sah', $rowTotalTdk, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                @php $rowTotalAll = 0; @endphp
                <tr class="dark:bg-gray-700/20 bg-gray-50">
                    <td class="px-5 py-1.5 text-sm font-bold dark:text-gray-200 text-gray-800">Jumlah Seluruh Suara</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $all = $r ? ($r->suara_sah + $r->suara_tidak_sah) : null; $rowTotalAll += $all ?? 0; @endphp
                    {!! $renderAdminTpsCell($tps, 'suara_total', $r ? $all : null, 'px-3 py-1.5 text-center font-bold dark:text-gray-200 text-gray-700') !!}
                    @endforeach
                    {!! $renderAdminFlaggedTotalCell($desa, 'suara_total', $rowTotalAll, 'px-3 py-1.5 text-center font-bold text-red-500') !!}
                </tr>
                <tr class="dark:bg-gray-700/10 bg-gray-50 border-t dark:border-gray-700 border-gray-200">
                    <td class="px-5 py-1.5 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider">Status</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; @endphp
                    <td class="px-3 py-1.5 text-center">
                        @if(!$r)
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] px-2 py-1 rounded font-semibold bg-gray-500/20 dark:text-gray-400 text-gray-500 border border-gray-400/30">Kosong</span>
                            </div>
                        @elseif($r->status === 'final')
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] px-2 py-1 rounded font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">Final</span>
                                @if($canUnlockRekap)
                                    <a href="{{ route('admin.rekap.edit-tps', [$jenis, $tps]) }}"
                                       class="text-[9px] px-2 py-0.5 rounded font-semibold border border-red-400/40 text-red-400 hover:bg-red-400/10 transition whitespace-nowrap">
                                        Edit
                                    </a>
                                    <button onclick="openUnlockModal({{ $r->tps_id }}, '{{ addslashes($tps->nama) }}')"
                                            class="text-[9px] px-2 py-0.5 rounded font-semibold border border-orange-400/40 text-orange-400 hover:bg-orange-400/10 transition whitespace-nowrap">
                                        ↩ Buka
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[9px] px-2 py-1 rounded font-semibold bg-orange-400/20 text-orange-400 border border-orange-400/40">Draft</span>
                                @if($canUnlockRekap)
                                    <a href="{{ route('admin.rekap.edit-tps', [$jenis, $tps]) }}"
                                       class="text-[9px] px-2 py-0.5 rounded font-semibold border border-red-400/40 text-red-400 hover:bg-red-400/10 transition whitespace-nowrap">
                                        Edit
                                    </a>
                                @endif
                            </div>
                        @endif
                    </td>
                    @endforeach
                    <td></td>
                </tr>

                </tbody>
            </table>
        </div>
        {{-- ══ END TABEL BESAR PER DESA ══ --}}

    </div>{{-- end desa --}}
    @endforeach
    </div>{{-- end kec accordion --}}
</div>
@endforeach
@endif

{{-- Export Modal --}}
<div id="export-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="dark:bg-gray-800 bg-white rounded-2xl border dark:border-gray-700 border-gray-200 w-full max-w-md shadow-2xl p-8">
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-1 font-semibold">// Export</p>
        <h2 class="font-display text-2xl tracking-wide text-red-600 mb-6">EXPORT EXCEL</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Level Export</label>
                <select id="export-level" onchange="updateExportFilter()"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Level —</option>
                    <option value="tps">Tingkat TPS</option>
                    <option value="desa">Tingkat Desa / PPS</option>
                    <option value="kecamatan">Tingkat Kecamatan / PPK</option>
                    <option value="kabupaten">Tingkat Kabupaten</option>
                </select>
            </div>

            <div id="export-filter-kec" class="hidden">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                <select id="export-kec" onchange="loadExportDesa(this.value)"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Kecamatan —</option>
                    @foreach($kecamatans as $kec)
                    <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div id="export-filter-desa" class="hidden">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">Desa</label>
                <select id="export-desa" onchange="loadExportTps(this.value)"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih Desa —</option>
                </select>
            </div>

            <div id="export-filter-tps" class="hidden">
                <label class="block text-xs font-semibold dark:text-gray-400 text-gray-600 uppercase tracking-wider mb-2">TPS</label>
                <select id="export-tps"
                        class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                    <option value="">— Pilih TPS —</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="button" onclick="closeExportModal()"
                    class="flex-1 border dark:border-gray-600 border-gray-300 dark:text-gray-400 text-gray-500 py-2.5 rounded-lg text-sm font-medium dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                Batal
            </button>
            <a id="export-download-btn" href="#"
               class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold transition text-center opacity-50 pointer-events-none">
                ↓ Download
            </a>
        </div>
    </div>
</div>

@if($canUnlockRekap)
{{-- Modal Unlock Rekap --}}
<div id="modal-unlock" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeUnlockModal()"></div>
    <div class="relative dark:bg-gray-800 bg-white rounded-2xl shadow-2xl border dark:border-gray-700 border-gray-200 w-full max-w-sm p-6">
        <div class="flex items-start gap-4 mb-5">
            <div class="w-10 h-10 rounded-full bg-orange-400/15 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold dark:text-gray-100 text-gray-800 text-sm mb-1">Buka Rekap untuk Diedit</p>
                <p id="unlock-label" class="text-xs dark:text-gray-400 text-gray-500 leading-relaxed"></p>
            </div>
        </div>
        <form id="unlock-form" method="POST">
            @csrf
            <div class="flex gap-2">
                <button type="button" onclick="closeUnlockModal()"
                        class="flex-1 px-4 py-2.5 rounded-xl text-xs font-semibold border dark:border-gray-600 border-gray-300
                               dark:text-gray-400 text-gray-500 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl text-xs font-semibold bg-orange-500 hover:bg-orange-600 text-white transition">
                    ↩ Ya, Buka
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    const allDesas = @json(\App\Models\Desa::orderBy('nama')->get(['id','nama','kecamatan_id']));
    const allTps   = @json(\App\Models\Tps::orderBy('nama')->get(['id','nama','desa_id']));
    const baseUrl  = '{{ route('admin.rekap.export', $jenis) }}';
    const exportDownloadBase = '{{ url('admin/rekap/export/download') }}';
    const inlineUpdateUrl = '{{ route('admin.rekap.inline-update', $jenis) }}';
    const csrfToken = '{{ csrf_token() }}';
    const flagClasses = ['bg-red-500/20', 'text-red-600', 'dark:bg-red-500/20', 'dark:text-red-200', 'ring-1', 'ring-inset', 'ring-red-400/60'];
    const totalPenggunaRowKey = 'sum:pengguna_dpt_lk+pengguna_dpt_pr+pengguna_dptb_lk+pengguna_dptb_pr+pengguna_dpk_lk+pengguna_dpk_pr';
    const legislativeParties = @json(collect($master['partais'] ?? [])->map(fn($partai) => [
        'id' => $partai->id,
        'caleg_ids' => $partai->calegs->pluck('id')->values(),
    ])->values());
    const inlineSumFormulas = {
        'sum:dpt_lk+dpt_pr': ['dpt_lk', 'dpt_pr'],
        'sum:pengguna_dpt_lk+pengguna_dpt_pr': ['pengguna_dpt_lk', 'pengguna_dpt_pr'],
        'sum:pengguna_dptb_lk+pengguna_dptb_pr': ['pengguna_dptb_lk', 'pengguna_dptb_pr'],
        'sum:pengguna_dpk_lk+pengguna_dpk_pr': ['pengguna_dpk_lk', 'pengguna_dpk_pr'],
        'sum:pengguna_dpt_lk+pengguna_dptb_lk+pengguna_dpk_lk': ['pengguna_dpt_lk', 'pengguna_dptb_lk', 'pengguna_dpk_lk'],
        'sum:pengguna_dpt_pr+pengguna_dptb_pr+pengguna_dpk_pr': ['pengguna_dpt_pr', 'pengguna_dptb_pr', 'pengguna_dpk_pr'],
        'sum:pengguna_dpt_lk+pengguna_dpt_pr+pengguna_dptb_lk+pengguna_dptb_pr+pengguna_dpk_lk+pengguna_dpk_pr': ['pengguna_dpt_lk', 'pengguna_dpt_pr', 'pengguna_dptb_lk', 'pengguna_dptb_pr', 'pengguna_dpk_lk', 'pengguna_dpk_pr'],
        'ss_sisa': ['ss_diterima', 'ss_digunakan', 'ss_rusak'],
        'sum:disabilitas_lk+disabilitas_pr': ['disabilitas_lk', 'disabilitas_pr'],
        'suara_total': ['suara_sah', 'suara_tidak_sah'],
    };
    const numberFormatter = new Intl.NumberFormat('en-US');
    let inlineEditMode = false;

    function isFlaggedCell(cell) {
        return cell?.classList.contains('bg-red-500/20') || cell?.dataset.autoFlagged === '1';
    }
    function setFlaggedCell(cell, flagged) {
        if (!cell) return;
        const effectiveFlagged = flagged || cell.dataset.autoFlagged === '1';
        flagClasses.forEach(cls => cell.classList.toggle(cls, effectiveFlagged));
    }
    function hasManualOrParentFlag(cell) {
        return cell?.dataset.directFlagged === '1' || cell?.dataset.parentFlagged === '1';
    }
    function setAutoFlaggedCell(cell, flagged) {
        if (!cell) return;
        cell.dataset.autoFlagged = flagged ? '1' : '0';
        setFlaggedCell(cell, hasManualOrParentFlag(cell));
    }
    function setFlagButtonState(cell, flagged) {
        const form = cell.querySelector('[data-flag-form]');
        const button = cell.querySelector('.js-flag-button');
        if (!form || !button) return;

        form.classList.toggle('opacity-100', flagged);
        form.classList.toggle('opacity-0', !flagged);
        form.classList.toggle('group-hover:opacity-100', !flagged);

        ['bg-red-500', 'text-white', 'border-red-500', 'opacity-100'].forEach(cls => button.classList.toggle(cls, flagged));
        ['opacity-0', 'group-hover:opacity-100', 'bg-white', 'dark:bg-gray-900', 'text-red-500', 'border-red-400'].forEach(cls => button.classList.toggle(cls, !flagged));
        button.title = flagged ? 'Hapus tanda merah' : 'Tandai merah';
    }
    function tpsCellsFor(desaId, rowKey) {
        return Array.from(document.querySelectorAll('[data-flag-scope="tps"]'))
            .filter(cell => cell.dataset.desaId == desaId && cell.dataset.rowKey === rowKey);
    }
    function desaCellsFor(desaId, rowKey) {
        return Array.from(document.querySelectorAll('[data-flag-scope="desa"]'))
            .filter(cell => cell.dataset.desaId == desaId && cell.dataset.rowKey === rowKey);
    }
    function kecCellsFor(kecId, rowKey) {
        return Array.from(document.querySelectorAll('[data-flag-scope="kec"]'))
            .filter(cell => cell.dataset.kecId == kecId && cell.dataset.rowKey === rowKey);
    }
    function setProgressAlert(scope, id, flagged) {
        document.querySelectorAll(`[data-progress-alert="${scope}"]`).forEach(item => {
            const matches = scope === 'desa'
                ? item.dataset.desaId == id
                : item.dataset.kecId == id;
            if (!matches) return;

            item.classList.toggle('hidden', !flagged);
            item.classList.toggle('inline-flex', flagged);
        });
    }
    function refreshDesaProgressAlert(desaId) {
        const hasFlag = Array.from(document.querySelectorAll('[data-flag-scope="tps"]'))
            .some(cell => cell.dataset.desaId == desaId && isFlaggedCell(cell))
            || Array.from(document.querySelectorAll('[data-flag-scope="desa"]'))
                .some(cell => cell.dataset.desaId == desaId && (isFlaggedCell(cell) || cell.dataset.parentFlagged === '1'));

        setProgressAlert('desa', desaId, hasFlag);
    }
    function refreshKecProgressAlert(kecId) {
        const desaIds = allDesas.filter(desa => desa.kecamatan_id == kecId).map(desa => String(desa.id));
        const hasFlag = Array.from(document.querySelectorAll('[data-flag-scope="kec"]'))
            .some(cell => cell.dataset.kecId == kecId && (isFlaggedCell(cell) || cell.dataset.directFlagged === '1'))
            || Array.from(document.querySelectorAll('[data-flag-scope="tps"]'))
                .some(cell => desaIds.includes(String(cell.dataset.desaId)) && isFlaggedCell(cell));

        setProgressAlert('kec', kecId, hasFlag);
    }
    function refreshDesaFlag(desaId, rowKey) {
        const hasParentFlag = desaCellsFor(desaId, rowKey).some(cell => cell.dataset.parentFlagged === '1');
        const hasFlag = hasParentFlag || tpsCellsFor(desaId, rowKey).some(isFlaggedCell);
        desaCellsFor(desaId, rowKey).forEach(cell => setFlaggedCell(cell, hasFlag));
        refreshDesaProgressAlert(desaId);
    }
    function refreshKecFlag(kecId, rowKey) {
        const desaIds = allDesas.filter(desa => desa.kecamatan_id == kecId).map(desa => String(desa.id));
        const hasDirectFlag = kecCellsFor(kecId, rowKey).some(cell => cell.dataset.directFlagged === '1');
        const hasFlag = hasDirectFlag || Array.from(document.querySelectorAll('[data-flag-scope="tps"]'))
            .some(cell => desaIds.includes(String(cell.dataset.desaId)) && cell.dataset.rowKey === rowKey && isFlaggedCell(cell));
        kecCellsFor(kecId, rowKey).forEach(cell => setFlaggedCell(cell, hasFlag));
        refreshKecProgressAlert(kecId);
    }
    function refreshKecDownlineFlags(kecId, rowKey, flagged) {
        allDesas
            .filter(desa => desa.kecamatan_id == kecId)
            .forEach(desa => {
                desaCellsFor(desa.id, rowKey).forEach(cell => {
                    cell.dataset.parentFlagged = flagged ? '1' : '0';
                });
                refreshDesaFlag(desa.id, rowKey);
            });

        refreshKecFlag(kecId, rowKey);
    }
    function refreshRollupFlags(desaId, rowKey) {
        refreshDesaFlag(desaId, rowKey);
        const desa = allDesas.find(item => item.id == desaId);
        if (desa) refreshKecFlag(desa.kecamatan_id, rowKey);
    }

    function inlineEditableCells() {
        return Array.from(document.querySelectorAll('[data-inline-editable="1"]'));
    }
    function inlineTpsCells() {
        return Array.from(document.querySelectorAll('[data-flag-scope="tps"]'));
    }
    function inlineValue(cell) {
        const input = cell.querySelector('.js-inline-input');
        const raw = input ? input.value : cell.dataset.editValue;
        const span = cell.querySelector('.js-cell-value');
        const value = parseInt((raw || span?.textContent || '0').replace(/,/g, ''), 10);
        return Number.isNaN(value) || value < 0 ? 0 : value;
    }
    function setInlineCellValue(cell, value) {
        const safeValue = Number.isFinite(value) && value > 0 ? value : 0;
        const span = cell.querySelector('.js-cell-value');

        cell.dataset.editValue = String(safeValue);
        if (span) span.textContent = numberFormatter.format(safeValue);
    }
    function tpsCellFor(tpsId, rowKey) {
        return inlineTpsCells().find(cell => cell.dataset.tpsId == tpsId && cell.dataset.rowKey === rowKey);
    }
    function tpsCellsForRowPrefix(tpsId, prefix) {
        return inlineTpsCells().filter(cell => cell.dataset.tpsId == tpsId && cell.dataset.rowKey?.startsWith(prefix));
    }
    function setInlineStatus(message, isError = false) {
        const target = document.getElementById('inline-edit-status');
        if (!target) return;
        target.textContent = message;
        target.classList.toggle('text-red-500', isError);
        target.classList.toggle('dark:text-gray-500', !isError);
        target.classList.toggle('text-gray-400', !isError);
    }
    function refreshInlineRowTotal(desaId, rowKey) {
        const total = inlineTpsCells()
            .filter(cell => cell.dataset.desaId == desaId && cell.dataset.rowKey === rowKey)
            .reduce((sum, cell) => sum + inlineValue(cell), 0);

        desaCellsFor(desaId, rowKey).forEach(cell => {
            const span = cell.querySelector('span');
            if (span) span.textContent = numberFormatter.format(total);
        });
    }
    function refreshInlineMismatchFlagsForDesa(desaId) {
        const totalPengguna = inlineTpsCells()
            .filter(cell => cell.dataset.desaId == desaId && cell.dataset.rowKey === totalPenggunaRowKey)
            .reduce((sum, cell) => sum + inlineValue(cell), 0);
        const suratDigunakan = inlineTpsCells()
            .filter(cell => cell.dataset.desaId == desaId && cell.dataset.rowKey === 'ss_digunakan')
            .reduce((sum, cell) => sum + inlineValue(cell), 0);
        const flagged = totalPengguna !== suratDigunakan;

        desaCellsFor(desaId, totalPenggunaRowKey).forEach(cell => setAutoFlaggedCell(cell, flagged));
        desaCellsFor(desaId, 'ss_digunakan').forEach(cell => setAutoFlaggedCell(cell, flagged));
        refreshDesaFlag(desaId, totalPenggunaRowKey);
        refreshDesaFlag(desaId, 'ss_digunakan');

        const desa = allDesas.find(item => item.id == desaId);
        if (desa) {
            refreshKecFlag(desa.kecamatan_id, totalPenggunaRowKey);
            refreshKecFlag(desa.kecamatan_id, 'ss_digunakan');
        }
    }
    function refreshInlineMismatchFlagsForTps(tpsId) {
        const totalCell = tpsCellFor(tpsId, totalPenggunaRowKey);
        const usedCell = tpsCellFor(tpsId, 'ss_digunakan');
        if (!totalCell || !usedCell) return;

        const flagged = inlineValue(totalCell) !== inlineValue(usedCell);
        setAutoFlaggedCell(totalCell, flagged);
        setAutoFlaggedCell(usedCell, flagged);

        refreshInlineMismatchFlagsForDesa(totalCell.dataset.desaId);
    }
    function refreshInlinePartaiTotal(tpsId, partaiId, refreshRowTotal = true) {
        const target = tpsCellFor(tpsId, `partai_total:${partaiId}`);
        if (!target) return;

        const partaiCell = tpsCellFor(tpsId, `partai:${partaiId}`);
        const party = legislativeParties.find(item => String(item.id) === String(partaiId));
        const calegTotal = (party?.caleg_ids || []).reduce((sum, calegId) => {
            const cell = tpsCellFor(tpsId, `caleg:${calegId}`);
            return sum + (cell ? inlineValue(cell) : 0);
        }, 0);
        const total = (partaiCell ? inlineValue(partaiCell) : 0) + calegTotal;

        setInlineCellValue(target, total);
        if (refreshRowTotal) {
            refreshInlineRowTotal(target.dataset.desaId, target.dataset.rowKey);
        }
    }
    function refreshInlineLegislativePartyTotals(tpsId, refreshRowTotal = true) {
        legislativeParties.forEach(party => refreshInlinePartaiTotal(tpsId, party.id, refreshRowTotal));
    }
    function refreshInlineSuaraSah(tpsId, refreshRowTotal = true) {
        const target = tpsCellFor(tpsId, 'suara_sah');
        if (!target) return;

        const candidateCells = tpsCellsForRowPrefix(tpsId, 'calon:');
        let total = 0;

        if (candidateCells.length) {
            total = candidateCells.reduce((sum, cell) => sum + inlineValue(cell), 0);
        } else {
            refreshInlineLegislativePartyTotals(tpsId, refreshRowTotal);
            total = legislativeParties.reduce((sum, party) => {
                const cell = tpsCellFor(tpsId, `partai_total:${party.id}`);
                return sum + (cell ? inlineValue(cell) : 0);
            }, 0);
        }

        setInlineCellValue(target, total);
        if (refreshRowTotal) {
            refreshInlineRowTotal(target.dataset.desaId, 'suara_sah');
        }
        refreshInlineTpsSum(tpsId, 'suara_total', refreshRowTotal);
    }
    function refreshAllInlineRowTotals() {
        const totals = {};

        inlineTpsCells().forEach(cell => {
            const key = `${cell.dataset.desaId}:${cell.dataset.rowKey}`;
            totals[key] = (totals[key] || 0) + inlineValue(cell);
        });

        document.querySelectorAll('[data-flag-scope="desa"]').forEach(cell => {
            const span = cell.querySelector('span');
            const key = `${cell.dataset.desaId}:${cell.dataset.rowKey}`;
            if (span) span.textContent = numberFormatter.format(totals[key] || 0);
        });
    }
    function refreshInlineTpsSum(tpsId, rowKey, refreshRowTotal = true) {
        const fields = inlineSumFormulas[rowKey];
        if (!fields) return;

        const target = tpsCellFor(tpsId, rowKey);
        if (!target) return;

        const values = Object.fromEntries(fields.map(field => {
            const source = tpsCellFor(tpsId, field);
            return [field, source ? inlineValue(source) : 0];
        }));
        const total = rowKey === 'ss_sisa'
            ? Math.max(0, values.ss_diterima - values.ss_digunakan - values.ss_rusak)
            : fields.reduce((sum, field) => sum + values[field], 0);

        setInlineCellValue(target, total);
        if (refreshRowTotal) {
            refreshInlineRowTotal(target.dataset.desaId, rowKey);
        }
    }
    function refreshAllInlineComputedRows() {
        const tpsIds = [...new Set(inlineTpsCells().map(cell => cell.dataset.tpsId))];
        const formulaKeys = Object.keys(inlineSumFormulas);

        tpsIds.forEach(tpsId => {
            formulaKeys.forEach(rowKey => refreshInlineTpsSum(tpsId, rowKey, false));
            refreshInlineSuaraSah(tpsId, false);
            refreshInlineTpsSum(tpsId, 'suara_total', false);
            refreshInlineMismatchFlagsForTps(tpsId);
        });

        refreshAllInlineRowTotals();
    }
    function refreshInlineDerivedRows(cell) {
        refreshInlineRowTotal(cell.dataset.desaId, cell.dataset.rowKey);

        Object.entries(inlineSumFormulas)
            .filter(([, fields]) => fields.includes(cell.dataset.rowKey))
            .forEach(([rowKey]) => refreshInlineTpsSum(cell.dataset.tpsId, rowKey));

        if (
            cell.dataset.rowKey.startsWith('calon:')
            || cell.dataset.rowKey.startsWith('partai:')
            || cell.dataset.rowKey.startsWith('caleg:')
        ) {
            refreshInlineSuaraSah(cell.dataset.tpsId);
        }

        refreshInlineMismatchFlagsForTps(cell.dataset.tpsId);
    }
    function setInlineControls(editing) {
        document.getElementById('inline-edit-toggle')?.classList.toggle('hidden', editing);
        document.getElementById('inline-edit-save')?.classList.toggle('hidden', !editing);
    }
    function enterInlineEditMode() {
        if (inlineEditMode) return;
        const cells = inlineEditableCells();
        if (!cells.length) {
            setInlineStatus('Tidak ada cell TPS yang bisa diedit pada detail ini.', true);
            return;
        }

        inlineEditMode = true;
        setInlineControls(true);
        setInlineStatus('Mode edit aktif. Ubah angka pada cell TPS, lalu simpan perubahan.');

        cells.forEach(cell => {
            const span = cell.querySelector('.js-cell-value');
            if (!span || cell.querySelector('.js-inline-input')) return;

            const value = cell.dataset.editValue || '0';
            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.step = '1';
            input.value = value;
            input.className = 'js-inline-input w-24 rounded border border-red-300 bg-white px-2 py-1 text-center text-xs font-semibold text-gray-800 outline-none focus:ring-2 focus:ring-red-300/40 dark:border-red-500/50 dark:bg-gray-900 dark:text-gray-100';
            input.addEventListener('input', () => {
                cell.dataset.editValue = String(inlineValue(cell));
                refreshInlineDerivedRows(cell);
            });

            span.classList.add('hidden');
            cell.insertBefore(input, cell.querySelector('[data-flag-form]') || null);
        });
    }
    function leaveInlineEditMode(reset = false) {
        inlineEditableCells().forEach(cell => {
            const input = cell.querySelector('.js-inline-input');
            const span = cell.querySelector('.js-cell-value');
            if (!span) return;

            const value = reset ? parseInt(cell.dataset.editOriginal || '0', 10) : inlineValue(cell);
            cell.dataset.editValue = String(value);
            span.textContent = numberFormatter.format(value);
            span.classList.remove('hidden');
            input?.remove();
        });

        if (reset) {
            refreshAllInlineComputedRows();
        }

        inlineEditMode = false;
        setInlineControls(false);
        setInlineStatus(reset ? 'Perubahan dibatalkan.' : 'Perubahan tersimpan.');
    }
    async function saveInlineChanges() {
        const saveButton = document.getElementById('inline-edit-save');
        const changes = inlineEditableCells()
            .map(cell => ({
                cell,
                tps_id: parseInt(cell.dataset.tpsId || '0', 10),
                row_key: cell.dataset.rowKey,
                value: inlineValue(cell),
                original: parseInt(cell.dataset.editOriginal || '0', 10),
            }))
            .filter(item => item.value !== item.original)
            .map(({ tps_id, row_key, value }) => ({ tps_id, row_key, value }));

        if (!changes.length) {
            leaveInlineEditMode(true);
            setInlineStatus('Tidak ada perubahan untuk disimpan.');
            return;
        }

        saveButton.disabled = true;
        setInlineStatus('Menyimpan perubahan...');

        try {
            const response = await fetch(inlineUpdateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ changes }),
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Gagal menyimpan perubahan.');
            }

            inlineEditableCells().forEach(cell => {
                cell.dataset.editOriginal = String(inlineValue(cell));
            });
            leaveInlineEditMode(false);
        } catch (error) {
            setInlineStatus(error.message || 'Gagal menyimpan perubahan.', true);
        } finally {
            saveButton.disabled = false;
        }
    }

    document.getElementById('inline-edit-toggle')?.addEventListener('click', enterInlineEditMode);
    document.getElementById('inline-edit-save')?.addEventListener('click', saveInlineChanges);

    document.addEventListener('submit', async function(event) {
        const form = event.target.closest('[data-flag-form]');
        if (!form) return;

        event.preventDefault();

        const cell = form.closest('[data-flag-scope]');
        const button = form.querySelector('.js-flag-button');
        if (!cell || !button || button.disabled) return;

        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Gagal mengubah tanda.');
            }

            const payload = await response.json();
            const flagged = Boolean(payload.flagged);
            if (cell.dataset.flagScope === 'kec' || cell.dataset.flagScope === 'tps') {
                cell.dataset.directFlagged = flagged ? '1' : '0';
            }
            setFlaggedCell(cell, flagged);
            setFlagButtonState(cell, flagged);

            if (cell.dataset.flagScope === 'tps') {
                refreshRollupFlags(cell.dataset.desaId, cell.dataset.rowKey);
            }

            if (cell.dataset.flagScope === 'kec') {
                refreshKecDownlineFlags(cell.dataset.kecId, cell.dataset.rowKey, flagged);
            }
        } catch (error) {
            window.alert(error.message || 'Gagal mengubah tanda.');
        } finally {
            button.disabled = false;
        }
    });

    function toggleKec(id) {
        const el    = document.getElementById('kec-' + id);
        const arrow = document.getElementById('arrow-kec-' + id);
        el.classList.toggle('hidden');
        arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
    }
    function toggleDesa(id) {
        const el    = document.getElementById('desa-' + id);
        const arrow = document.getElementById('arrow-desa-' + id);
        el.classList.toggle('hidden');
        arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
    }
    function loadDetailDesa(kecId) {
        const sel = document.getElementById('detail_desa_id');
        if (!sel) return;

        sel.innerHTML = '<option value="">Semua Desa</option>';
        if (!kecId) {
            sel.disabled = true;
            return;
        }

        allDesas
            .filter(d => d.kecamatan_id == kecId)
            .forEach(d => sel.innerHTML += `<option value="${d.id}">${d.nama}</option>`);
        sel.disabled = false;
    }
    function openExportModal() {
        document.getElementById('export-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeExportModal() {
        document.getElementById('export-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    document.getElementById('export-modal').addEventListener('click', function(e) {
        if (e.target === this) closeExportModal();
    });

    function updateExportFilter() {
        const level = document.getElementById('export-level').value;
        document.getElementById('export-filter-kec').classList.add('hidden');
        document.getElementById('export-filter-desa').classList.add('hidden');
        document.getElementById('export-filter-tps').classList.add('hidden');
        document.getElementById('export-kec').value = '';
        disableDownload();

        if (level === 'kabupaten') { enableDownload('kabupaten'); return; }
        if (['tps','desa','kecamatan'].includes(level)) {
            document.getElementById('export-filter-kec').classList.remove('hidden');
        }
    }

    function loadExportDesa(kecId) {
        const level = document.getElementById('export-level').value;
        document.getElementById('export-filter-desa').classList.add('hidden');
        document.getElementById('export-filter-tps').classList.add('hidden');
        disableDownload();

        if (!kecId) return;
        if (level === 'kecamatan') { enableDownload('kecamatan', kecId); return; }

        const desas = allDesas.filter(d => d.kecamatan_id == kecId);
        const sel   = document.getElementById('export-desa');
        sel.innerHTML = '<option value="">— Pilih Desa —</option>';
        desas.forEach(d => sel.innerHTML += `<option value="${d.id}">${d.nama}</option>`);
        document.getElementById('export-filter-desa').classList.remove('hidden');
    }

    function loadExportTps(desaId) {
        const level = document.getElementById('export-level').value;
        document.getElementById('export-filter-tps').classList.add('hidden');
        disableDownload();

        if (!desaId) return;
        if (level === 'desa') { enableDownload('desa', null, desaId); return; }

        const tpsList = allTps.filter(t => t.desa_id == desaId);
        const sel     = document.getElementById('export-tps');
        sel.innerHTML = '<option value="">— Pilih TPS —</option>';
        tpsList.forEach(t => sel.innerHTML += `<option value="${t.id}" onchange="enableDownload('tps',null,null,this.value)">${t.nama}</option>`);
        document.getElementById('export-filter-tps').classList.remove('hidden');

        // Listen change on TPS select
        document.getElementById('export-tps').onchange = function() {
            if (this.value) enableDownload('tps', null, null, this.value);
            else disableDownload();
        };
    }

    function enableDownload(level, kecId = null, desaId = null, tpsId = null) {
        const jenis  = '{{ $jenis }}';
        const params = new URLSearchParams({ jenis, level });
        if (kecId)  params.set('kecamatan_id', kecId);
        if (desaId) params.set('desa_id', desaId);
        if (tpsId)  params.set('tps_id', tpsId);

        const btn = document.getElementById('export-download-btn');
        btn.href  = exportDownloadBase + '?' + params.toString();
        btn.classList.remove('opacity-50','pointer-events-none');
    }

    function disableDownload() {
        const btn = document.getElementById('export-download-btn');
        btn.href  = '#';
        btn.classList.add('opacity-50','pointer-events-none');
    }

    function openUnlockModal(tpsId, tpsNama) {
        if (!document.getElementById('unlock-form')) return;
        document.getElementById('unlock-label').textContent =
            'Status rekap ' + tpsNama + ' akan dikembalikan ke Draft dan KPPS dapat mengedit kembali.';
        document.getElementById('unlock-form').action =
            '{{ route("admin.rekap.unlock", $jenis) }}?tps_id=' + tpsId;
        document.getElementById('modal-unlock').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeUnlockModal() {
        if (!document.getElementById('modal-unlock')) return;
        document.getElementById('modal-unlock').classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>

@endpush
@endsection
