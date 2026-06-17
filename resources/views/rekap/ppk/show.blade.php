@extends('layouts.app')
@section('title', 'Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')
@php
    $totalGaruda = collect($desaStats)->sum(fn ($stats) => $stats['suara_sah'] ?? 0);
    $totalTps = $desas->sum(fn ($desa) => $desa->tps->count());
    $totalFinal = $rekaps->where('status', 'final')->count();
    $totalDraft = $rekaps->where('status', 'draft')->count();
    $totalBelumInput = max(0, $totalTps - $rekaps->count());
    $showDetail = request()->boolean('detail');

    $formatCell = function ($value, string $classes = '') {
        $content = is_null($value) ? '&mdash;' : number_format($value);

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '">' . $content . '</td>');
    };
@endphp

<div class="mb-8 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('korcam.rekap.index') }}"
           class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
            &larr; Kembali
        </a>
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
            // KORCAM - {{ $kecamatan->nama }}
        </p>
        <h1 class="font-display text-4xl tracking-[2px] text-orange-400">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
    </div>
    <a href="{{ route('korcam.rekap.export', $jenis) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-orange-400 hover:bg-orange-500 text-white transition flex-shrink-0">
        Export Excel
    </a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total Suara Garuda</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalGaruda) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">TPS Final</p>
        <p class="font-display text-3xl text-orange-400">{{ $totalFinal }}/{{ $totalTps }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Draft</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalDraft) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Belum Input</p>
        <p class="font-display text-3xl text-orange-400">{{ number_format($totalBelumInput) }}</p>
    </div>
</div>

<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Rekap Total Kecamatan</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:240px">
                @foreach($desas as $__desa) <col style="width:120px"> @endforeach
                <col style="width:120px">
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
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Perolehan Suara Partai Garuda
                </td>
            </tr>

            @foreach($master['partais'] as $partai)
            <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $desas->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                    {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                </td>
            </tr>

            @php $partaiTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                @foreach($desas as $desa)
                    @php $suara = $desaPartaiTotals[$desa->id][$partai->id] ?? 0; $partaiTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($partaiTotal, 'px-3 py-2 text-center font-bold text-orange-400') !!}
            </tr>

            @foreach($partai->calegs as $caleg)
            @php $calegTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span>
                        <span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span>
                    </div>
                </td>
                @foreach($desas as $desa)
                    @php $suara = $desaCalegTotals[$desa->id][$caleg->id] ?? 0; $calegTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($calegTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            @php $garudaTotal = 0; @endphp
            <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Garuda</td>
                @foreach($desas as $desa)
                    @php $suara = $desaPartaiGrandTotals[$desa->id][$partai->id] ?? 0; $garudaTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                @endforeach
                {!! $formatCell($garudaTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Detail Per Desa</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('korcam.rekap.show', $jenis) }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label for="detail_desa_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Pilih Desa
            </label>
            <select id="detail_desa_id" name="detail_desa_id"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-orange-400 focus:ring-0 focus:outline-none">
                <option value="">Pilih Desa</option>
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
        <a href="{{ route('korcam.rekap.show', $jenis) }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
            Sembunyikan Detail
        </a>
        @endif
    </form>
</div>

@if(!$showDetail || $detailDesas->isEmpty())
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-6 mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Detail TPS tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">Pilih satu desa untuk melihat rincian per TPS.</p>
</div>
@else
@foreach($detailDesas as $desa)
@php
    $tpsIds = $desa->tps->pluck('id');
    $desaRekaps = $detailRekaps->whereIn('tps_id', $tpsIds->toArray());
    $desaFinal = $desaRekaps->where('status', 'final')->count();
    $desaTotalTps = $desa->tps->count();
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $desa->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $desaFinal }}/{{ $desaTotalTps }} TPS difinalisasi</p>
        </div>
        <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
            <div class="h-1.5 rounded-full bg-orange-400" style="width:{{ $desaTotalTps > 0 ? round(($desaFinal/$desaTotalTps)*100) : 0 }}%"></div>
        </div>
    </div>

    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:240px">
                @foreach($desa->tps as $__tps) <col style="width:110px"> @endforeach
                <col style="width:120px">
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
            @foreach($master['partais'] as $partai)
            <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $desa->tps->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                    {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                </td>
            </tr>
            @php $partaiTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $suara = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : null;
                        $partaiTotal += $suara ?? 0;
                    @endphp
                    {!! $formatCell($r ? $suara : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($partaiTotal, 'px-3 py-2 text-center font-bold text-orange-400') !!}
            </tr>

            @foreach($partai->calegs as $caleg)
            @php $calegTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span>
                        <span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span>
                    </div>
                </td>
                @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $suara = $r ? ($r->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : null;
                        $calegTotal += $suara ?? 0;
                    @endphp
                    {!! $formatCell($r ? $suara : null, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($calegTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            @php $garudaTotal = 0; @endphp
            <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Garuda</td>
                @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $suaraPartai = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                        $suaraCaleg = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0;
                        $total = $r ? ($suaraPartai + $suaraCaleg) : null;
                        $garudaTotal += $total ?? 0;
                    @endphp
                    {!! $formatCell($r ? $total : null, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                @endforeach
                {!! $formatCell($garudaTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            <tr class="dark:bg-gray-700/10 bg-gray-50 border-t dark:border-gray-700 border-gray-200">
                <td class="px-5 py-2 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider">Status</td>
                @foreach($desa->tps as $tps)
                @php $r = $detailRekaps[$tps->id] ?? null; @endphp
                <td class="px-3 py-2 text-center">
                    @if(!$r)
                        <span class="text-[9px] px-2 py-1 rounded font-semibold bg-gray-500/20 dark:text-gray-400 text-gray-500 border border-gray-400/30">Kosong</span>
                    @elseif($r->status === 'final')
                        <span class="text-[9px] px-2 py-1 rounded font-semibold bg-teal-500/20 text-teal-400 border border-teal-500/40">Final</span>
                    @elseif($r->status === 'perlu_dicek')
                        <span class="text-[9px] px-2 py-1 rounded font-semibold bg-red-500/20 text-red-400 border border-red-500/40">Perlu Dicek</span>
                    @else
                        <span class="text-[9px] px-2 py-1 rounded font-semibold bg-orange-400/20 text-orange-400 border border-orange-400/40">Draft</span>
                    @endif
                </td>
                @endforeach
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif

@endsection
