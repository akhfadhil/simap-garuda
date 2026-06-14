<?php

namespace App\Exports;

use App\Models\RekapHeader;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapExport implements WithMultipleSheets
{
    protected $rekaps;
    protected $master;
    protected $tpsList;
    protected $level;
    protected $wilayah;
    protected $desas;
    protected $jenis; // jenis yang dipilih (untuk Korcam & Admin)

    public function __construct($rekaps, $master, $tpsList, $level, $wilayah, $desas = null, $jenis = null)
    {
        $this->rekaps  = $rekaps;
        $this->master  = $master;
        $this->tpsList = $tpsList;
        $this->level   = $level;
        $this->wilayah = $wilayah;
        $this->desas   = $desas;
        $this->jenis   = $jenis;
    }

    public function sheets(): array
    {
        $sheets = [];

        if (in_array($this->level, ['ppk', 'admin']) && $this->desas && $this->jenis) {
            $jenis       = $this->jenis;
            $label       = RekapHeader::JENIS_LABELS[$jenis];
            $masterJenis = $this->master[$jenis] ?? [];

            // Sheet 1: Rekap total (kolom = desa)
            $sheets[] = new RekapTotalSheetExport(
                $jenis,
                'Rekap_' . $label,
                $this->rekaps,
                $masterJenis,
                $this->desas,
                $this->level,
                $this->wilayah
            );

            // Sheet 2+: Per desa (kolom = TPS)
            foreach ($this->desas as $desa) {
                $tpsDesaList    = $this->tpsList->where('desa_id', $desa->id)->values();
                if ($tpsDesaList->isEmpty()) continue;

                $rekapsFiltered = $this->rekaps->whereIn('tps_id', $tpsDesaList->pluck('id'));
                $sheetTitle     = substr($desa->nama, 0, 28);

                $sheets[] = new RekapSheetExport(
                    $jenis,
                    $sheetTitle,
                    $rekapsFiltered,
                    $this->master,
                    $tpsDesaList,
                    $this->level,
                    $desa->nama . ' — ' . $this->wilayah
                );
            }
        } else {
            // Kordes: 1 sheet flat
            $jenis = $this->jenis ?? 'ppwp';
            $sheets[] = new RekapSheetExport(
                $jenis,
                RekapHeader::JENIS_LABELS[$jenis],
                $this->rekaps,
                $this->master,
                $this->tpsList,
                $this->level,
                $this->wilayah
            );
        }

        return $sheets;
    }
}
