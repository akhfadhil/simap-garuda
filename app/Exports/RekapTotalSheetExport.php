<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapTotalSheetExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    protected $jenis;

    protected $label;

    protected $rekaps;

    protected $master;

    protected $desas;

    protected $level;

    protected $wilayah;

    protected $headerRows = [];

    protected $subHeaderRows = [];

    protected $totalRows = [];

    protected $sectionRows = [];

    public function __construct($jenis, $label, $rekaps, $master, $desas, $level, $wilayah)
    {
        $this->jenis = $jenis;
        $this->label = $label;
        $this->rekaps = $rekaps->keyBy('tps_id');
        $this->master = $master;
        $this->desas = $desas;
        $this->level = $level;
        $this->wilayah = $wilayah;
    }

    public function title(): string
    {
        return substr($this->label, 0, 31);
    }

    public function array(): array
    {
        $this->headerRows = [];
        $this->subHeaderRows = [];
        $this->totalRows = [];
        $this->sectionRows = [];

        $rows = [];
        $desaList = $this->desas;
        $desaNames = $desaList->map(fn ($desa) => $desa->nama)->toArray();

        $rows[] = ['REKAPITULASI SUARA GARUDA - '.strtoupper($this->label).' - '.strtoupper($this->wilayah)];
        $rows[] = [''];
        $this->headerRows[] = 1;

        $rows[] = ['SECTION I - PEROLEHAN SUARA GARUDA'];
        $this->sectionRows[] = count($rows);
        $rows[] = array_merge(['Keterangan'], $desaNames, ['Total']);
        $this->subHeaderRows[] = count($rows);

        $partais = $this->master['partais'] ?? collect();
        foreach ($partais as $partai) {
            $rows[] = ['- '.$partai->nomor_urut.'. '.$partai->nama_partai];
            $this->subHeaderRows[] = count($rows);

            $rowTotal = 0;
            $cells = ['  Suara Partai'];
            foreach ($desaList as $desa) {
                $suara = $desa->tps->sum(fn ($tps) => ($this->rekaps[$tps->id] ?? null)?->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0);
                $cells[] = $suara;
                $rowTotal += $suara;
            }
            $cells[] = $rowTotal;
            $rows[] = $cells;

            foreach ($partai->calegs as $caleg) {
                $rowTotal = 0;
                $cells = ['  '.$caleg->nomor_urut.'. '.$caleg->nama_caleg];
                foreach ($desaList as $desa) {
                    $suara = $desa->tps->sum(fn ($tps) => ($this->rekaps[$tps->id] ?? null)?->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0);
                    $cells[] = $suara;
                    $rowTotal += $suara;
                }
                $cells[] = $rowTotal;
                $rows[] = $cells;
            }

            $grandTotal = 0;
            $cells = ['  Total Suara Garuda'];
            foreach ($desaList as $desa) {
                $total = $desa->tps->sum(function ($tps) use ($partai) {
                    $rekap = $this->rekaps[$tps->id] ?? null;
                    if (! $rekap) {
                        return 0;
                    }

                    $suaraPartai = $rekap->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0;
                    $suaraCaleg = $rekap->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara');

                    return $suaraPartai + $suaraCaleg;
                });
                $cells[] = $total;
                $grandTotal += $total;
            }
            $cells[] = $grandTotal;
            $rows[] = $cells;
            $this->totalRows[] = count($rows);
            $rows[] = [''];
        }

        $rows[] = ['SECTION II - STATUS INPUT TPS'];
        $this->sectionRows[] = count($rows);
        $rows[] = array_merge(['Keterangan'], $desaNames, ['Total']);
        $this->subHeaderRows[] = count($rows);

        $totalFinal = 0;
        $totalInput = 0;
        $totalTps = 0;
        $cells = ['Status'];
        foreach ($desaList as $desa) {
            $desaTpsCount = $desa->tps->count();
            $inputCount = 0;
            $finalCount = 0;

            foreach ($desa->tps as $tps) {
                $status = ($this->rekaps[$tps->id] ?? null)?->status;
                if ($status) {
                    $inputCount++;
                }
                if ($status === 'final') {
                    $finalCount++;
                }
            }

            $cells[] = $finalCount.'/'.$desaTpsCount.' final, '.$inputCount.' masuk';
            $totalFinal += $finalCount;
            $totalInput += $inputCount;
            $totalTps += $desaTpsCount;
        }
        $cells[] = $totalFinal.'/'.$totalTps.' final, '.$totalInput.' masuk';
        $rows[] = $cells;
        $this->totalRows[] = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastCol}1");

        $styles = [];

        $styles[1] = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        foreach ($this->sectionRows as $row) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $styles[$row] = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ];
        }

        foreach ($this->subHeaderRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E6B9E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        foreach ($this->totalRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF5FB']],
            ];
        }

        $styles["A1:{$lastCol}{$lastRow}"]['borders'] = [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
        ];

        return $styles;
    }

    public function columnWidths(): array
    {
        return ['A' => 35];
    }
}
