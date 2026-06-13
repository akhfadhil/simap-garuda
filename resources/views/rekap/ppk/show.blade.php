@extends('layouts.app')
@section('title', 'Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')
<div class="mb-8 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('ppk.rekap.index') }}"
           class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
            ← Kembali
        </a>
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
            // PPK — {{ $kecamatan->nama }}
        </p>
        <h1 class="font-display text-4xl tracking-[2px] text-orange-400">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
    </div>
    <a href="{{ route('ppk.rekap.export', $jenis) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-orange-400 hover:bg-orange-500 text-white transition flex-shrink-0">
        ↓ Export Excel
    </a>
</div>

{{-- Summary cards --}}
@php
    $totalDpt    = $rekaps->sum(fn($r) => $r->dpt_lk + $r->dpt_pr);
    $totalHadir  = $rekaps->sum(fn($r) => ($r->pengguna_dpt_lk + $r->pengguna_dpt_pr + $r->pengguna_dptb_lk + $r->pengguna_dptb_pr + $r->pengguna_dpk_lk + $r->pengguna_dpk_pr));
    $totalTdkSah = $rekaps->sum('suara_tidak_sah');
    $showDetail = request()->boolean('detail');
@endphp
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total DPT</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalDpt) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total Hadir</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalHadir) }}</p>
        <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
            {{ $totalDpt > 0 ? round(($totalHadir/$totalDpt)*100,1) : 0 }}% partisipasi
        </p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Suara Tidak Sah</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalTdkSah) }}</p>
    </div>
</div>

@php
    $rows1 = [
        ['label'=>'DPT Laki-laki',            'field'=>'dpt_lk'],
        ['label'=>'DPT Perempuan',             'field'=>'dpt_pr'],
        ['label'=>'DPT Jumlah',                'sum'=>['dpt_lk','dpt_pr'], 'bold'=>true],
        ['label'=>'Pengguna DPT LK',           'field'=>'pengguna_dpt_lk'],
        ['label'=>'Pengguna DPT PR',           'field'=>'pengguna_dpt_pr'],
        ['label'=>'Pengguna DPTB LK',          'field'=>'pengguna_dptb_lk'],
        ['label'=>'Pengguna DPTB PR',          'field'=>'pengguna_dptb_pr'],
        ['label'=>'Pengguna DPK LK',           'field'=>'pengguna_dpk_lk'],
        ['label'=>'Pengguna DPK PR',           'field'=>'pengguna_dpk_pr'],
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

    // Helper: ambil agregat per desa yang sudah dihitung di controller.
    $getDesaVal = function($desa, $row) use ($desaStats) {
        $stats = $desaStats[$desa->id] ?? [];
        return isset($row['field'])
            ? ($stats[$row['field']] ?? 0)
            : collect($row['sum'])->sum(fn($f) => $stats[$f] ?? 0);
    };

    $rowKeyFor = fn($row) => isset($row['field']) ? $row['field'] : 'sum:' . implode('+', $row['sum']);
    $totalPenggunaRowKey = 'sum:pengguna_dpt_lk+pengguna_dpt_pr+pengguna_dptb_lk+pengguna_dptb_pr+pengguna_dpk_lk+pengguna_dpk_pr';
    $mismatchRowKeys = [$totalPenggunaRowKey, 'ss_digunakan'];
    $isMismatchRow = fn(string $rowKey) => in_array($rowKey, $mismatchRowKeys, true);
    $statsTotalPengguna = fn(array $stats) => (int) ($stats['pengguna_dpt_lk'] ?? 0) + (int) ($stats['pengguna_dpt_pr'] ?? 0) + (int) ($stats['pengguna_dptb_lk'] ?? 0) + (int) ($stats['pengguna_dptb_pr'] ?? 0) + (int) ($stats['pengguna_dpk_lk'] ?? 0) + (int) ($stats['pengguna_dpk_pr'] ?? 0);
    $statsHasMismatch = fn(array $stats) => $statsTotalPengguna($stats) !== (int) ($stats['ss_digunakan'] ?? 0);
    $rekapTotalPengguna = fn($r) => (int) $r->pengguna_dpt_lk + (int) $r->pengguna_dpt_pr + (int) $r->pengguna_dptb_lk + (int) $r->pengguna_dptb_pr + (int) $r->pengguna_dpk_lk + (int) $r->pengguna_dpk_pr;
    $rekapHasMismatch = fn($r) => $r && $rekapTotalPengguna($r) !== (int) $r->ss_digunakan;
    $collectionHasMismatch = fn($items) => $items->sum(fn($r) => $rekapTotalPengguna($r)) !== $items->sum('ss_digunakan');
    $flaggedClasses = 'bg-red-500/20 text-red-600 dark:bg-red-500/20 dark:text-red-200 ring-1 ring-inset ring-red-400/60';
    $renderFlaggedCell = function(bool $flagged, $value, string $baseClass = '') use ($flaggedClasses) {
        $classes = trim($baseClass . ' ' . ($flagged ? $flaggedClasses : ''));
        $content = is_null($value) ? '&mdash;' : number_format($value);

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '"><span>' . $content . '</span></td>');
    };
    $renderDesaCell = function($desa, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($cellFlags, $renderFlaggedCell) {
        $flagged = $cellFlags->has($desa->id . ':' . $rowKey);

        return $renderFlaggedCell($autoFlagged || $flagged, $value, $baseClass);
    };
    $renderDetailTpsCell = function($tps, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($tpsCellFlags, $renderFlaggedCell) {
        return $renderFlaggedCell($autoFlagged || $tpsCellFlags->has($tps->id . ':' . $rowKey), $value, $baseClass);
    };
    $renderDetailTotalCell = function($desa, string $rowKey, $value, string $baseClass = '', bool $autoFlagged = false) use ($cellFlags, $renderFlaggedCell) {
        return $renderFlaggedCell($autoFlagged || $cellFlags->has($desa->id . ':' . $rowKey), $value, $baseClass);
    };
@endphp

{{-- ══════════════════════════════════════
     REKAP TOTAL KECAMATAN (kolom = desa)
══════════════════════════════════════ --}}
<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Rekap Total Kecamatan</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:220px">
                @foreach($desas as $__desa) <col style="width:110px"> @endforeach
                <col style="width:110px">
            </colgroup>
            <thead>
                <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                    <th class="text-left px-5 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold truncate">Keterangan</th>
                    @foreach($desas as $desa)
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold whitespace-nowrap">{{ $desa->nama }}</th>
                    @endforeach
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach([
                ['title' => 'Section I - DPT & Pengguna Hak Pilih', 'rows' => $rows1],
                ['title' => 'Section II - Surat Suara', 'rows' => $rows2],
                ['title' => 'Section III - Pemilih Disabilitas', 'rows' => $rows3],
            ] as $sec)
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">{{ $sec['title'] }}</td>
                </tr>
                @foreach($sec['rows'] as $row)
                @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                    <td class="px-5 py-2 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                    @foreach($desas as $desa)
                    @php
                        $rowKey = $rowKeyFor($row);
                        $val = $getDesaVal($desa, $row);
                        $rowTotal += $val;
                        $autoFlagged = $isMismatchRow($rowKey) && $statsHasMismatch($desaStats[$desa->id] ?? []);
                    @endphp
                    {!! $renderDesaCell($desa, $rowKey, $val, 'px-3 py-2 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-orange-400 {{ $isMismatchRow($rowKeyFor($row)) && $collectionHasMismatch($rekaps) ? $flaggedClasses : '' }}">{{ number_format($rowTotal) }}</td>
                </tr>
                @endforeach
            @endforeach

            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Section IV - Perolehan Suara</td>
            </tr>
            @if(in_array($jenis, ['ppwp','gubernur','bupati','dpd']))
                @foreach($master['calons'] as $calon)
                @php $rowTotal = 0; $name = in_array($jenis, ['ppwp','gubernur','bupati']) ? $calon->nama_paslon : $calon->nama_calon; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-2.5 text-sm dark:text-gray-200 text-gray-700"><span class="text-xs dark:text-gray-500 text-gray-400 mr-1">{{ $calon->nomor_urut }}.</span>{{ $name }}</td>
                    @foreach($desas as $desa)
                    @php $val = $desaCalonTotals[$desa->id][$calon->id] ?? 0; $rowTotal += $val; @endphp
                    {!! $renderDesaCell($desa, 'calon:' . $calon->id, $val, 'px-3 py-2.5 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    <td class="px-3 py-2.5 text-center font-bold text-orange-400">{{ number_format($rowTotal) }}</td>
                </tr>
                @endforeach
            @else
                @foreach($master['partais'] as $partai)
                <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">{{ $partai->nomor_urut }}. {{ $partai->nama_partai }}</td>
                </tr>
                @php $partaiRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                    <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                    @foreach($desas as $desa)
                    @php $spDesa = $desaPartaiTotals[$desa->id][$partai->id] ?? 0; $partaiRowTotal += $spDesa; @endphp
                    {!! $renderDesaCell($desa, 'partai:' . $partai->id, $spDesa, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-orange-400">{{ number_format($partaiRowTotal) }}</td>
                </tr>
                @foreach($partai->calegs as $caleg)
                @php $calegRowTotal = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-2"><div class="flex items-center gap-2"><span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span><span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span></div></td>
                    @foreach($desas as $desa)
                    @php $scDesa = $desaCalegTotals[$desa->id][$caleg->id] ?? 0; $calegRowTotal += $scDesa; @endphp
                    {!! $renderDesaCell($desa, 'caleg:' . $caleg->id, $scDesa, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-teal-400">{{ number_format($calegRowTotal) }}</td>
                </tr>
                @endforeach
                @php $grandTotal = 0; @endphp
                <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                    <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Sah</td>
                    @foreach($desas as $desa)
                    @php $colTotal = $desaPartaiGrandTotals[$desa->id][$partai->id] ?? 0; $grandTotal += $colTotal; @endphp
                    {!! $renderDesaCell($desa, 'partai_total:' . $partai->id, $colTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                    @endforeach
                    <td class="px-3 py-2 text-center font-bold text-teal-400">{{ number_format($grandTotal) }}</td>
                </tr>
                @endforeach
            @endif

            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Section V - Suara Sah, Tidak Sah & Total</td>
            </tr>
            @foreach([['label'=>'Jumlah Suara Sah','field'=>'suara_sah'],['label'=>'Jumlah Suara Tidak Sah','field'=>'suara_tidak_sah']] as $row)
            @php $rowTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2 text-sm dark:text-gray-300 text-gray-600">{{ $row['label'] }}</td>
                @foreach($desas as $desa)
                @php $val = $getDesaVal($desa, $row); $rowTotal += $val; @endphp
                {!! $renderDesaCell($desa, $rowKeyFor($row), $val, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-orange-400">{{ number_format($rowTotal) }}</td>
            </tr>
            @endforeach
            @php $rowTotalAll = 0; @endphp
            <tr class="dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2 text-sm font-bold dark:text-gray-200 text-gray-800">Jumlah Seluruh Suara</td>
                @foreach($desas as $desa)
                @php $val = $desaStats[$desa->id]['suara_total'] ?? 0; $rowTotalAll += $val; @endphp
                {!! $renderDesaCell($desa, 'suara_total', $val, 'px-3 py-2 text-center font-bold dark:text-gray-200 text-gray-700') !!}
                @endforeach
                <td class="px-3 py-2 text-center font-bold text-orange-400">{{ number_format($rowTotalAll) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Detail Per Desa</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('ppk.rekap.show', $jenis) }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label for="detail_desa_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Pilih Desa
            </label>
            <select id="detail_desa_id" name="detail_desa_id"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-orange-400 focus:ring-0 focus:outline-none">
                <option value="">— Pilih Desa —</option>
                @foreach($desas as $desaOption)
                <option value="{{ $desaOption->id }}" {{ (int) $detailDesaId === (int) $desaOption->id ? 'selected' : '' }}>
                    {{ $desaOption->nama }}
                </option>
                @endforeach
            </select>
        </div>
        <button type="submit" name="detail" value="1"
                class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold bg-orange-400 hover:bg-orange-500 text-white transition whitespace-nowrap">
            Tampilkan Detail
        </button>
        @if($showDetail)
        <a href="{{ route('ppk.rekap.show', $jenis) }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
            Sembunyikan Detail
        </a>
        @endif
    </form>
</div>

@if(!$showDetail || $detailDesas->isEmpty())
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-6 mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Detail TPS tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">
        Pilih satu desa untuk melihat rincian per TPS.
    </p>
</div>
@else
@foreach($detailDesas as $desa)
@php $tpsIds = $desa->tps->pluck('id'); $desaRekaps = $detailRekaps->whereIn('tps_id', $tpsIds->toArray()); $desaFinal = $desaRekaps->where('status','final')->count(); $desaTotalTps = $desa->tps->count(); $desaHasFlag = $cellFlags->keys()->contains(fn($key) => str_starts_with($key, $desa->id . ':')); $desaAutoMismatch = $collectionHasMismatch($desaRekaps); @endphp

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200 cursor-pointer dark:hover:bg-gray-750 hover:bg-gray-50 transition"
         onclick="toggleDesa({{ $desa->id }})">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $desa->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $desaFinal }}/{{ $desaTotalTps }} TPS difinalisasi</p>
        </div>
        <div class="flex items-center gap-3">
            @if($desaHasFlag)
            <span title="Ada data yang perlu diperbaiki" class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-red-400 bg-red-500 text-[10px] font-bold leading-none text-white shadow-sm">!</span>
            @endif
            <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
                <div class="h-1.5 rounded-full bg-orange-400" style="width:{{ $desaTotalTps > 0 ? round(($desaFinal/$desaTotalTps)*100) : 0 }}%"></div>
            </div>
            <span id="arrow-desa-{{ $desa->id }}" class="dark:text-gray-500 text-gray-400 text-xs">▸</span>
        </div>
    </div>

    <div id="desa-{{ $desa->id }}" class="hidden">

        <div class="overflow-x-auto rekap-table-scroll">
            <table class="w-full text-sm table-fixed rekap-sticky-header">
                <colgroup>
                    <col style="width:220px">
                    @foreach($desa->tps as $__tps) <col style="width:110px"> @endforeach
                    <col style="width:110px">
                </colgroup>
                <thead>
                    <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                        <th class="text-left px-5 py-2.5 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold truncate">Keterangan</th>
                        @foreach($desa->tps as $tps)
                        <th class="text-center px-3 py-2.5 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold whitespace-nowrap">{{ $tps->nama }}</th>
                        @endforeach
                        <th class="text-center px-3 py-2.5 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                @foreach([
                    ['title' => 'Section I - DPT & Pengguna Hak Pilih', 'rows' => $rows1],
                    ['title' => 'Section II - Surat Suara', 'rows' => $rows2],
                    ['title' => 'Section III - Pemilih Disabilitas', 'rows' => $rows3],
                ] as $sec)
                    <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                        <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">{{ $sec['title'] }}</td>
                    </tr>
                    @foreach($sec['rows'] as $row)
                    @php $rowTotal = 0; $isBold = $row['bold'] ?? false; @endphp
                    <tr class="border-b dark:border-gray-700 border-gray-100 {{ $isBold ? 'dark:bg-gray-700/20 bg-gray-50' : 'dark:hover:bg-gray-750 hover:bg-gray-50' }}">
                        <td class="px-5 py-2 text-sm {{ $isBold ? 'font-bold dark:text-gray-200 text-gray-800' : 'dark:text-gray-300 text-gray-600' }}">{{ $row['label'] }}</td>
                        @foreach($desa->tps as $tps)
                        @php
                            $r = $detailRekaps[$tps->id] ?? null;
                            $rowKey = $rowKeyFor($row);
                            $val = $r ? (isset($row['field']) ? ($r->{$row['field']} ?? 0) : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0)) : null;
                            $rowTotal += $val ?? 0;
                            $autoFlagged = $isMismatchRow($rowKey) && $rekapHasMismatch($r);
                        @endphp
                        {!! $renderDetailTpsCell($tps, $rowKey, $r ? $val : null, 'px-3 py-2 text-center ' . ($isBold ? 'font-bold dark:text-gray-200 text-gray-700' : 'dark:text-gray-400 text-gray-500'), $autoFlagged) !!}
                        @endforeach
                        {!! $renderDetailTotalCell($desa, $rowKeyFor($row), $rowTotal, 'px-3 py-2 text-center font-bold text-orange-400', $isMismatchRow($rowKeyFor($row)) && $desaAutoMismatch) !!}
                    </tr>
                    @endforeach
                @endforeach
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Section IV - Perolehan Suara</td>
                </tr>
                @if(in_array($jenis, ['ppwp','gubernur','bupati','dpd']))
                    @foreach($master['calons'] as $calon)
                    @php $rowTotal = 0; $name = in_array($jenis, ['ppwp','gubernur','bupati']) ? $calon->nama_paslon : $calon->nama_calon; @endphp
                    <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                        <td class="px-5 py-2.5 text-sm dark:text-gray-200 text-gray-700"><span class="text-xs dark:text-gray-500 text-gray-400 mr-1">{{ $calon->nomor_urut }}.</span>{{ $name }}</td>
                        @foreach($desa->tps as $tps)
                        @php
                            $r = $detailRekaps[$tps->id] ?? null;
                            $s = $r ? match($jenis) {
                                'ppwp' => $r->ppwpSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                'gubernur' => $r->gubernurSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                'bupati' => $r->bupatiSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                                default => $r->dpdSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                            } : null;
                            $rowTotal += $s ?? 0;
                        @endphp
                        {!! $renderDetailTpsCell($tps, 'calon:' . $calon->id, $r ? $s : null, 'px-3 py-2.5 text-center dark:text-gray-400 text-gray-500') !!}
                        @endforeach
                        {!! $renderDetailTotalCell($desa, 'calon:' . $calon->id, $rowTotal, 'px-3 py-2.5 text-center font-bold text-orange-400') !!}
                    </tr>
                    @endforeach
                @else
                    @foreach($master['partais'] as $partai)
                    <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                        <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">{{ $partai->nomor_urut }}. {{ $partai->nama_partai }}</td>
                    </tr>
                    @php $partaiRowTotal = 0; @endphp
                    <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                        <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                        @foreach($desa->tps as $tps)
                        @php $r = $detailRekaps[$tps->id] ?? null; $sp = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : null; $partaiRowTotal += $sp ?? 0; @endphp
                        {!! $renderDetailTpsCell($tps, 'partai:' . $partai->id, $r ? $sp : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                        @endforeach
                        {!! $renderDetailTotalCell($desa, 'partai:' . $partai->id, $partaiRowTotal, 'px-3 py-2 text-center font-bold text-orange-400') !!}
                    </tr>
                    @foreach($partai->calegs as $caleg)
                    @php $calegRowTotal = 0; @endphp
                    <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                        <td class="px-5 py-2"><div class="flex items-center gap-2"><span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span><span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span></div></td>
                        @foreach($desa->tps as $tps)
                        @php $r = $detailRekaps[$tps->id] ?? null; $sc = $r ? ($r->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : null; $calegRowTotal += $sc ?? 0; @endphp
                        {!! $renderDetailTpsCell($tps, 'caleg:' . $caleg->id, $r ? $sc : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                        @endforeach
                        {!! $renderDetailTotalCell($desa, 'caleg:' . $caleg->id, $calegRowTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                    </tr>
                    @endforeach
                    @php $grandTotal = 0; @endphp
                    <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                        <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Sah</td>
                        @foreach($desa->tps as $tps)
                        @php $r = $detailRekaps[$tps->id] ?? null; $sp = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0; $sc_sum = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0; $colTotal = $r ? ($sp + $sc_sum) : null; $grandTotal += $colTotal ?? 0; @endphp
                        {!! $renderDetailTpsCell($tps, 'partai_total:' . $partai->id, $r ? $colTotal : null, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                        @endforeach
                        {!! $renderDetailTotalCell($desa, 'partai_total:' . $partai->id, $grandTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                    </tr>
                    @endforeach
                @endif
                <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                    <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Section V - Suara Sah, Tidak Sah & Total</td>
                </tr>
                @php $rowTotalSah = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-2 text-sm dark:text-gray-300 text-gray-600">Jumlah Suara Sah</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $sah = $r ? $r->suara_sah : null; $rowTotalSah += $sah ?? 0; @endphp
                    {!! $renderDetailTpsCell($tps, 'suara_sah', $r ? $sah : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderDetailTotalCell($desa, 'suara_sah', $rowTotalSah, 'px-3 py-2 text-center font-bold text-orange-400') !!}
                </tr>
                @php $rowTotalTdk = 0; @endphp
                <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                    <td class="px-5 py-2 text-sm dark:text-gray-300 text-gray-600">Jumlah Suara Tidak Sah</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $tdk = $r ? $r->suara_tidak_sah : null; $rowTotalTdk += $tdk ?? 0; @endphp
                    {!! $renderDetailTpsCell($tps, 'suara_tidak_sah', $r ? $tdk : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                    @endforeach
                    {!! $renderDetailTotalCell($desa, 'suara_tidak_sah', $rowTotalTdk, 'px-3 py-2 text-center font-bold text-orange-400') !!}
                </tr>
                @php $rowTotalAll = 0; @endphp
                <tr class="dark:bg-gray-700/20 bg-gray-50">
                    <td class="px-5 py-2 text-sm font-bold dark:text-gray-200 text-gray-800">Jumlah Seluruh Suara</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; $all = $r ? ($r->suara_sah + $r->suara_tidak_sah) : null; $rowTotalAll += $all ?? 0; @endphp
                    {!! $renderDetailTpsCell($tps, 'suara_total', $r ? $all : null, 'px-3 py-2 text-center font-bold dark:text-gray-200 text-gray-700') !!}
                    @endforeach
                    {!! $renderDetailTotalCell($desa, 'suara_total', $rowTotalAll, 'px-3 py-2 text-center font-bold text-orange-400') !!}
                </tr>
                <tr class="dark:bg-gray-700/10 bg-gray-50 border-t dark:border-gray-700 border-gray-200">
                    <td class="px-5 py-2 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider">Status</td>
                    @foreach($desa->tps as $tps)
                    @php $r = $detailRekaps[$tps->id] ?? null; @endphp
                    <td class="px-3 py-2 text-center">
                        @if(!$r) <span class="text-[9px] px-2 py-1 rounded font-semibold bg-gray-500/20 dark:text-gray-400 text-gray-500 border border-gray-400/30">Kosong</span>
                        @elseif($r->status === 'final') <span class="text-[9px] px-2 py-1 rounded font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">Final</span>
                        @else <span class="text-[9px] px-2 py-1 rounded font-semibold bg-orange-400/20 text-orange-400 border border-orange-400/40">Draft</span>
                        @endif
                    </td>
                    @endforeach
                    <td></td>
                </tr>
                </tbody>
            </table>
        </div>

        
    </div>{{-- end accordion --}}
</div>
@endforeach
@endif

@push('scripts')
<script>
function toggleDesa(id) {
    const el    = document.getElementById('desa-' + id);
    const arrow = document.getElementById('arrow-desa-' + id);
    el.classList.toggle('hidden');
    arrow.textContent = el.classList.contains('hidden') ? '▸' : '▾';
}
</script>
@endpush

@endsection
