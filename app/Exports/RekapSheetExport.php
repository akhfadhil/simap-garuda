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

class RekapSheetExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    protected $jenis;

    protected $label;

    protected $rekaps;

    protected $master;

    protected $tpsList;

    protected $level;

    protected $wilayah;

    protected $showTotal;

    protected $headerRows = [];

    protected $subHeaderRows = [];

    protected $totalRows = [];

    protected $sectionRows = [];

    public function __construct($jenis, $label, $rekaps, $master, $tpsList, $level, $wilayah)
    {
        $this->jenis = $jenis;
        $this->label = $label;
        $this->rekaps = $rekaps->keyBy('tps_id');
        $this->master = $master;
        $this->tpsList = $tpsList;
        $this->level = $level;
        $this->wilayah = $wilayah;
        $this->showTotal = $level !== 'saksi_tps';
    }

    public function title(): string
    {
        return $this->label;
    }

    public function array(): array
    {
        $this->headerRows = [];
        $this->subHeaderRows = [];
        $this->totalRows = [];
        $this->sectionRows = [];

        $rows = [];
        $tpsList = $this->tpsList;
        $rekaps = $this->rekaps;
        $tpsNames = $tpsList->map(fn ($tps) => $tps->nama)->toArray();

        $rows[] = ['REKAPITULASI SUARA GARUDA - '.strtoupper($this->label)];
        $rows[] = [$this->wilayah];
        $rows[] = [''];
        $this->headerRows[] = 1;

        $rows[] = ['SECTION I - PEROLEHAN SUARA GARUDA'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $masterJenis = $this->master[$this->jenis] ?? null;
        $partais = $masterJenis['partais'] ?? collect();

        foreach ($partais as $partai) {
            $rows[] = ['- '.$partai->nomor_urut.'. '.$partai->nama_partai];
            $this->subHeaderRows[] = count($rows);

            $rowTotal = 0;
            $cells = ['  Suara Partai'];
            foreach ($tpsList as $tps) {
                $rekap = $rekaps[$tps->id] ?? null;
                $suara = $rekap ? ($rekap->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                $cells[] = $suara;
                $rowTotal += $suara;
            }
            if ($this->showTotal) {
                $cells[] = $rowTotal;
            }
            $rows[] = $cells;

            foreach ($partai->calegs as $caleg) {
                $rowTotal = 0;
                $cells = ['  '.$caleg->nomor_urut.'. '.$caleg->nama_caleg];
                foreach ($tpsList as $tps) {
                    $rekap = $rekaps[$tps->id] ?? null;
                    $suara = $rekap ? ($rekap->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : 0;
                    $cells[] = $suara;
                    $rowTotal += $suara;
                }
                if ($this->showTotal) {
                    $cells[] = $rowTotal;
                }
                $rows[] = $cells;
            }

            $grandTotal = 0;
            $cells = ['  Total Suara Garuda'];
            foreach ($tpsList as $tps) {
                $rekap = $rekaps[$tps->id] ?? null;
                $suaraPartai = $rekap ? ($rekap->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                $suaraCaleg = $rekap ? $rekap->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0;
                $total = $suaraPartai + $suaraCaleg;
                $cells[] = $total;
                $grandTotal += $total;
            }
            if ($this->showTotal) {
                $cells[] = $grandTotal;
            }
            $rows[] = $cells;
            $this->totalRows[] = count($rows);
            $rows[] = [''];
        }

        $rows[] = ['SECTION II - STATUS INPUT TPS'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $finalCount = 0;
        $inputCount = 0;
        $cells = ['Status'];
        foreach ($tpsList as $tps) {
            $status = ($rekaps[$tps->id] ?? null)?->status;
            if ($status) {
                $inputCount++;
            }
            if ($status === 'final') {
                $finalCount++;
            }
            $cells[] = $status ? ucfirst($status) : 'Kosong';
        }
        if ($this->showTotal) {
            $cells[] = $finalCount.'/'.$tpsList->count().' final, '.$inputCount.' masuk';
        }
        $rows[] = $cells;
        $this->totalRows[] = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $styles = [];

        $styles[1] = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $styles[2] = [
            'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
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

        $lastRow = $sheet->getHighestRow();
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
