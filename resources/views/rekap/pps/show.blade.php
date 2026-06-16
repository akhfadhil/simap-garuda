@extends('layouts.app')
@section('title', 'Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')
@php
    $totalGaruda = $rekaps->sum('suara_sah');
    $totalFinal = $rekaps->where('status', 'final')->count();
    $totalDraft = $rekaps->where('status', 'draft')->count();
    $totalTps = $tpsList->count();
    $totalBelumInput = max(0, $totalTps - $rekaps->count());

    $formatCell = function ($value, string $classes = '') {
        $content = is_null($value) ? '&mdash;' : number_format($value);

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '">' . $content . '</td>');
    };
@endphp

<div class="mb-8 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('pps.rekap.index') }}"
           class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
            &larr; Kembali
        </a>
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
            // KORDES - {{ $desa->nama }}
        </p>
        <h1 class="font-display text-4xl tracking-[2px] text-teal-400">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
    </div>
    <a href="{{ route('pps.rekap.export', $jenis) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-teal-500 hover:bg-teal-600 text-white transition flex-shrink-0">
        Export Excel
    </a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total Suara Garuda</p>
        <p class="font-display text-3xl text-teal-400">{{ number_format($totalGaruda) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">TPS Final</p>
        <p class="font-display text-3xl text-teal-400">{{ $totalFinal }}/{{ $totalTps }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Draft</p>
        <p class="font-display text-3xl text-teal-400">{{ number_format($totalDraft) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Belum Input</p>
        <p class="font-display text-3xl text-teal-400">{{ number_format($totalBelumInput) }}</p>
    </div>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-4">
    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:240px">
                @foreach($tpsList as $__tps) <col style="width:110px"> @endforeach
                <col style="width:120px">
            </colgroup>
            <thead>
                <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                    <th class="text-left px-5 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold truncate">Keterangan</th>
                    @foreach($tpsList as $tps)
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold whitespace-nowrap">{{ $tps->nama }}</th>
                    @endforeach
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $tpsList->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Perolehan Suara Partai Garuda
                </td>
            </tr>

            @foreach($master['partais'] as $partai)
            <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $tpsList->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                    {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                </td>
            </tr>

            @php $partaiRowTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2.5 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                @foreach($tpsList as $tps)
                    @php
                        $r = $rekaps[$tps->id] ?? null;
                        $suara = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : null;
                        $partaiRowTotal += $suara ?? 0;
                    @endphp
                    {!! $formatCell($r ? $suara : null, 'px-3 py-2.5 text-center font-semibold dark:text-gray-200 text-gray-700') !!}
                @endforeach
                {!! $formatCell($partaiRowTotal, 'px-3 py-2.5 text-center font-bold text-orange-400') !!}
            </tr>

            @foreach($partai->calegs as $caleg)
            @php $calegRowTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:hover:bg-gray-750 hover:bg-gray-50">
                <td class="px-5 py-2.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xs dark:text-gray-500 text-gray-400 w-4">{{ $caleg->nomor_urut }}.</span>
                        <span class="text-sm dark:text-gray-200 text-gray-700">{{ $caleg->nama_caleg }}</span>
                    </div>
                </td>
                @foreach($tpsList as $tps)
                    @php
                        $r = $rekaps[$tps->id] ?? null;
                        $suara = $r ? ($r->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : null;
                        $calegRowTotal += $suara ?? 0;
                    @endphp
                    {!! $formatCell($r ? $suara : null, 'px-3 py-2.5 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($calegRowTotal, 'px-3 py-2.5 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            @php $partaiTotal = 0; @endphp
            <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                <td class="px-5 py-2.5 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara Garuda</td>
                @foreach($tpsList as $tps)
                    @php
                        $r = $rekaps[$tps->id] ?? null;
                        $suaraPartai = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                        $suaraCaleg = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0;
                        $total = $r ? ($suaraPartai + $suaraCaleg) : null;
                        $partaiTotal += $total ?? 0;
                    @endphp
                    {!! $formatCell($r ? $total : null, 'px-3 py-2.5 text-center font-bold text-teal-400') !!}
                @endforeach
                {!! $formatCell($partaiTotal, 'px-3 py-2.5 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $tpsList->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Status Input TPS
                </td>
            </tr>
            <tr class="dark:bg-gray-700/10 bg-gray-50 border-t dark:border-gray-700 border-gray-200">
                <td class="px-5 py-2.5 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider">Status</td>
                @foreach($tpsList as $tps)
                @php $r = $rekaps[$tps->id] ?? null; @endphp
                <td class="px-3 py-2.5 text-center">
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

@endsection
