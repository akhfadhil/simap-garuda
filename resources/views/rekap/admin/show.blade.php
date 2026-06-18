@extends('layouts.app')
@section('title', 'Rekap ' . \App\Models\RekapHeader::JENIS_LABELS[$jenis])

@section('content')
@php
    $totalParty = collect($kecStats)->sum(fn ($stats) => $stats['suara_sah'] ?? 0);
    $totalTps = collect($kecStats)->sum(fn ($stats) => $stats['tps_count'] ?? 0);
    $totalFinal = collect($kecStats)->sum(fn ($stats) => $stats['final'] ?? 0);
    $totalDraft = $rekaps->where('status', 'draft')->count();
    $totalBelumInput = max(0, $totalTps - $rekaps->count());
    $showDetail = request()->boolean('detail');

    $baseQuery = [];
    if ($jenis === 'dprd_kab' && $selectedDapilId) {
        $baseQuery['dapil_id'] = $selectedDapilId;
    }

    $detailDesasByKecamatan = $kecamatans->mapWithKeys(function ($kecamatan) {
        return [
            $kecamatan->id => $kecamatan->desas->map(fn ($desa) => [
                'id' => $desa->id,
                'nama' => $desa->nama,
            ])->values(),
        ];
    });

    $formatCell = function ($value, string $classes = '') {
        $content = is_null($value) ? '&mdash;' : number_format($value);

        return new \Illuminate\Support\HtmlString('<td class="' . e($classes) . '">' . $content . '</td>');
    };
@endphp

<div class="mb-8 flex items-end justify-between gap-4">
    <div>
        <a href="{{ route('admin.rekap.index') }}"
           class="inline-flex items-center gap-2 text-xs dark:text-gray-500 text-gray-400 hover:text-red-500 transition font-medium mb-4">
            &larr; Kembali
        </a>
        <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">// Admin Partai - Rekapitulasi</p>
        <h1 class="font-display text-4xl tracking-[2px] text-red-600">
            {{ strtoupper(\App\Models\RekapHeader::JENIS_LABELS[$jenis]) }}
        </h1>
    </div>
    <a href="{{ route('admin.rekap.export', $jenis) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white transition flex-shrink-0">
        Export Excel
    </a>
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

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Total Suara {{ config('party.short_name') }}</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalParty) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">TPS Final</p>
        <p class="font-display text-3xl text-red-600">{{ $totalFinal }}/{{ $totalTps }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Draft</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalDraft) }}</p>
    </div>
    <div class="dark:bg-gray-800 bg-white rounded-xl p-5 border dark:border-gray-700 border-gray-200 shadow-sm">
        <p class="text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">Belum Input</p>
        <p class="font-display text-3xl text-red-600">{{ number_format($totalBelumInput) }}</p>
    </div>
</div>

<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Rekap Total Kabupaten</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto rekap-table-scroll">
        <table class="w-full text-sm table-fixed rekap-sticky-header">
            <colgroup>
                <col style="width:240px">
                @foreach($kecamatans as $__kec) <col style="width:120px"> @endforeach
                <col style="width:120px">
            </colgroup>
            <thead>
                <tr class="border-b dark:border-gray-700 border-gray-200 dark:bg-gray-800 bg-white">
                    <th class="text-left px-5 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold truncate">Keterangan</th>
                    @foreach($kecamatans as $kec)
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold whitespace-nowrap">{{ $kec->nama }}</th>
                    @endforeach
                    <th class="text-center px-3 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
            <tr class="dark:bg-gray-900/60 bg-gray-100 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase font-semibold">
                    Perolehan Suara {{ config('party.name') }}
                </td>
            </tr>

            @foreach($master['partais'] as $partai)
            <tr class="dark:bg-gray-700/30 bg-gray-50 border-b dark:border-gray-700 border-gray-200">
                <td colspan="{{ $kecamatans->count() + 2 }}" class="px-5 py-1.5 text-xs font-bold dark:text-gray-300 text-gray-700">
                    {{ $partai->nomor_urut }}. {{ $partai->nama_partai }}
                </td>
            </tr>

            @php $partaiTotal = 0; @endphp
            <tr class="border-b dark:border-gray-700 border-gray-100 dark:bg-gray-700/20 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Suara Partai</td>
                @foreach($kecamatans as $kec)
                    @php $suara = $kecPartaiTotals[$kec->id][$partai->id] ?? 0; $partaiTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($partaiTotal, 'px-3 py-2 text-center font-bold text-red-500') !!}
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
                @foreach($kecamatans as $kec)
                    @php $suara = $kecCalegTotals[$kec->id][$caleg->id] ?? 0; $calegTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center dark:text-gray-400 text-gray-500') !!}
                @endforeach
                {!! $formatCell($calegTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            @php $partyTotal = 0; @endphp
            <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara {{ config('party.short_name') }}</td>
                @foreach($kecamatans as $kec)
                    @php $suara = $kecPartaiGrandTotals[$kec->id][$partai->id] ?? 0; $partyTotal += $suara; @endphp
                    {!! $formatCell($suara, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                @endforeach
                {!! $formatCell($partyTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mb-2">
    <p class="text-[10px] tracking-[3px] dark:text-gray-500 text-gray-400 uppercase font-semibold mb-3">// Detail Wilayah</p>
</div>

<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('admin.rekap.show', $jenis) }}" class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_auto_auto] gap-3 lg:items-end">
        @foreach($baseQuery as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <div>
            <label for="detail_kecamatan_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Kecamatan
            </label>
            <select id="detail_kecamatan_id" name="detail_kecamatan_id"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
                <option value="">Pilih Kecamatan</option>
                @foreach($kecamatans as $kecOption)
                <option value="{{ $kecOption->id }}" {{ (int) $detailKecamatanId === (int) $kecOption->id ? 'selected' : '' }}>
                    {{ $kecOption->nama }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="detail_desa_id" class="block text-[10px] tracking-[2px] dark:text-gray-500 text-gray-400 uppercase mb-2 font-semibold">
                Desa
            </label>
            <select id="detail_desa_id" name="detail_desa_id"
                    class="w-full dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 px-4 py-2.5 text-sm rounded-lg focus:border-red-500 focus:ring-0 focus:outline-none">
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
        <a href="{{ route('admin.rekap.show', array_merge(['jenis' => $jenis], $baseQuery)) }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs font-semibold border dark:border-gray-700 border-gray-300 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition">
            Sembunyikan Detail
        </a>
        @endif
    </form>
</div>

@if(!$showDetail || $detailKecamatans->isEmpty())
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm p-6 mb-8">
    <p class="text-sm font-semibold dark:text-gray-100 text-gray-800">Detail wilayah tidak dimuat otomatis</p>
    <p class="text-xs dark:text-gray-500 text-gray-400 mt-1">Pilih kecamatan untuk melihat rincian desa dan TPS.</p>
</div>
@else
@foreach($detailKecamatans as $kec)
@foreach($kec->desas as $desa)
@php
    $desaTpsIds = $desa->tps->pluck('id');
    $desaRekaps = $detailRekaps->whereIn('tps_id', $desaTpsIds->toArray());
    $desaFinal = $desaRekaps->where('status', 'final')->count();
    $desaTotalTps = $desa->tps->count();
@endphp
<div class="dark:bg-gray-800 bg-white rounded-xl border dark:border-gray-700 border-gray-200 shadow-sm mb-4 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700 border-gray-200">
        <div>
            <p class="font-semibold text-sm dark:text-gray-100 text-gray-800">{{ $kec->nama }} / {{ $desa->nama }}</p>
            <p class="text-[11px] dark:text-gray-500 text-gray-400 mt-0.5">{{ $desaFinal }}/{{ $desaTotalTps }} TPS difinalisasi</p>
        </div>
        <div class="w-24 h-1.5 dark:bg-gray-700 bg-gray-200 rounded-full">
            <div class="h-1.5 rounded-full bg-red-500" style="width:{{ $desaTotalTps > 0 ? round(($desaFinal/$desaTotalTps)*100) : 0 }}%"></div>
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
                {!! $formatCell($partaiTotal, 'px-3 py-2 text-center font-bold text-red-500') !!}
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

            @php $partyTotal = 0; @endphp
            <tr class="border-t-2 dark:border-gray-600 border-gray-300 dark:bg-gray-700/30 bg-gray-50">
                <td class="px-5 py-2 text-xs font-bold dark:text-gray-300 text-gray-700 uppercase">Total Suara {{ config('party.short_name') }}</td>
                @foreach($desa->tps as $tps)
                    @php
                        $r = $detailRekaps[$tps->id] ?? null;
                        $suaraPartai = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                        $suaraCaleg = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0;
                        $total = $r ? ($suaraPartai + $suaraCaleg) : null;
                        $partyTotal += $total ?? 0;
                    @endphp
                    {!! $formatCell($r ? $total : null, 'px-3 py-2 text-center font-bold text-teal-400') !!}
                @endforeach
                {!! $formatCell($partyTotal, 'px-3 py-2 text-center font-bold text-teal-400') !!}
            </tr>
            @endforeach

            <tr class="dark:bg-gray-700/10 bg-gray-50 border-t dark:border-gray-700 border-gray-200">
                <td class="px-5 py-2 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider">Status</td>
                @foreach($desa->tps as $tps)
                @php $r = $detailRekaps[$tps->id] ?? null; @endphp
                <td class="px-3 py-2 text-center" data-review-status-cell="{{ $tps->id }}">
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
            <tr class="dark:bg-gray-900/40 bg-white border-t dark:border-gray-700 border-gray-200">
                <td class="px-5 py-3 text-[10px] dark:text-gray-500 text-gray-400 uppercase font-semibold tracking-wider align-top">Tandai TPS</td>
                @foreach($desa->tps as $tps)
                @php $r = $detailRekaps[$tps->id] ?? null; @endphp
                <td class="px-2 py-3 align-top">
                    <form method="POST" action="{{ route('admin.rekap.review-status', ['jenis' => $jenis, 'tps' => $tps]) }}" class="space-y-2" data-review-form data-tps-id="{{ $tps->id }}" data-return-status="{{ $r?->difinalisasi_at ? 'final' : 'draft' }}">
                        @csrf
                        @if($r?->status === 'perlu_dicek')
                            <input type="hidden" name="status_internal" value="{{ $r->difinalisasi_at ? 'final' : 'draft' }}">
                            <button class="w-full px-2 py-1.5 rounded text-[10px] font-semibold border border-gray-300 dark:border-gray-600 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition" data-review-button>
                                Clear
                            </button>
                            @if($r->catatan_internal)
                            <p class="text-[10px] leading-snug dark:text-red-300 text-red-600" data-review-note>{{ $r->catatan_internal }}</p>
                            @endif
                        @else
                            <input type="hidden" name="status_internal" value="perlu_dicek">
                            <textarea name="catatan_internal" rows="2" placeholder="Catatan"
                                      class="w-full min-w-[96px] dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-200 text-gray-700 px-2 py-1.5 text-[10px] rounded focus:border-red-500 focus:ring-0 focus:outline-none"></textarea>
                            <button class="w-full px-2 py-1.5 rounded text-[10px] font-semibold bg-red-500 hover:bg-red-600 text-white transition" data-review-button>
                                Perlu Dicek
                            </button>
                        @endif
                    </form>
                </td>
                @endforeach
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endforeach
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const kecamatanSelect = document.getElementById('detail_kecamatan_id');
    const desaSelect = document.getElementById('detail_desa_id');
    const selectedDesaId = @json((int) $detailDesaId);
    const desasByKecamatan = @json($detailDesasByKecamatan);

    if (!kecamatanSelect || !desaSelect) {
        return;
    }

    const refreshDesaOptions = () => {
        const kecamatanId = kecamatanSelect.value;
        const options = desasByKecamatan[kecamatanId] || [];
        desaSelect.innerHTML = '<option value="">Semua Desa</option>';

        options.forEach((desa) => {
            const option = document.createElement('option');
            option.value = desa.id;
            option.textContent = desa.nama;
            option.selected = Number(desa.id) === selectedDesaId;
            desaSelect.appendChild(option);
        });
    };

    kecamatanSelect.addEventListener('change', refreshDesaOptions);
    refreshDesaOptions();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const statusBadgeHtml = (status, label) => {
        const classes = {
            final: 'bg-teal-500/20 text-teal-400 border-teal-500/40',
            perlu_dicek: 'bg-red-500/20 text-red-400 border-red-500/40',
            draft: 'bg-orange-400/20 text-orange-400 border-orange-400/40',
        };

        return `<span class="text-[9px] px-2 py-1 rounded font-semibold border ${classes[status] || classes.draft}">${label}</span>`;
    };
    const reviewFormHtml = (status, note, returnStatus) => {
        if (status === 'perlu_dicek') {
            const escapedNote = String(note || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            return `
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="status_internal" value="${returnStatus}">
                <button class="w-full px-2 py-1.5 rounded text-[10px] font-semibold border border-gray-300 dark:border-gray-600 dark:text-gray-300 text-gray-600 dark:hover:bg-gray-700 hover:bg-gray-100 transition" data-review-button>
                    Clear
                </button>
                ${escapedNote ? `<p class="text-[10px] leading-snug dark:text-red-300 text-red-600" data-review-note>${escapedNote}</p>` : ''}
            `;
        }

        return `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="status_internal" value="perlu_dicek">
            <textarea name="catatan_internal" rows="2" placeholder="Catatan"
                      class="w-full min-w-[96px] dark:bg-gray-900 bg-gray-50 border dark:border-gray-700 border-gray-300 dark:text-gray-200 text-gray-700 px-2 py-1.5 text-[10px] rounded focus:border-red-500 focus:ring-0 focus:outline-none"></textarea>
            <button class="w-full px-2 py-1.5 rounded text-[10px] font-semibold bg-red-500 hover:bg-red-600 text-white transition" data-review-button>
                Perlu Dicek
            </button>
        `;
    };

    document.querySelectorAll('[data-review-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('[data-review-button]');
            const tpsId = form.dataset.tpsId;
            const returnStatus = form.dataset.returnStatus || 'draft';
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

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const payload = await response.json();
                const statusCell = document.querySelector(`[data-review-status-cell="${tpsId}"]`);
                if (statusCell) {
                    statusCell.innerHTML = statusBadgeHtml(payload.status, payload.status_label);
                }

                form.innerHTML = reviewFormHtml(payload.status, payload.catatan_internal, returnStatus);
            } catch (error) {
                form.submit();
            } finally {
                button?.removeAttribute('disabled');
                button?.classList.remove('opacity-60');
            }
        });
    });
});
</script>
@endpush

@endsection
