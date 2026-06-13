<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RekapSheetExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $jenis;
    protected $label;
    protected $rekaps;
    protected $master;
    protected $tpsList;
    protected $level;
    protected $wilayah;
    protected $showTotal;

    // Track baris mana yang bold/header untuk styling
    protected $headerRows    = [];
    protected $subHeaderRows = [];
    protected $totalRows     = [];
    protected $sectionRows   = [];

    public function __construct($jenis, $label, $rekaps, $master, $tpsList, $level, $wilayah)
    {
        $this->jenis   = $jenis;
        $this->label   = $label;
        $this->rekaps  = $rekaps->keyBy('tps_id');
        $this->master  = $master;
        $this->tpsList = $tpsList;
        $this->level   = $level;
        $this->wilayah = $wilayah;
        $this->showTotal = $level !== 'kpps';
    }

    public function title(): string
    {
        return $this->label;
    }

    public function array(): array
    {
        $rows    = [];
        $tpsList = $this->tpsList;
        $rekaps  = $this->rekaps;

        // Header TPS kolom
        $tpsNames = $tpsList->map(fn($t) => $t->nama)->toArray();

        // ── JUDUL ──
        $rows[] = ['REKAPITULASI DATA PEMILU — ' . strtoupper($this->label)];
        $rows[] = [$this->wilayah];
        $rows[] = [''];
        $this->headerRows[] = 1;

        // ── SECTION I ──
        $rows[]             = ['SECTION I — DPT & PENGGUNA HAK PILIH'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $rows1 = [
            ['label' => 'DPT Laki-laki',           'field' => 'dpt_lk'],
            ['label' => 'DPT Perempuan',            'field' => 'dpt_pr'],
            ['label' => 'DPT Jumlah',               'sum'   => ['dpt_lk', 'dpt_pr'], 'bold' => true],
            ['label' => 'Pengguna DPT LK',          'field' => 'pengguna_dpt_lk'],
            ['label' => 'Pengguna DPT PR',          'field' => 'pengguna_dpt_pr'],
            ['label' => 'Pengguna DPT Jumlah',      'sum'   => ['pengguna_dpt_lk', 'pengguna_dpt_pr'], 'bold' => true],
            ['label' => 'Pengguna DPTB LK',         'field' => 'pengguna_dptb_lk'],
            ['label' => 'Pengguna DPTB PR',         'field' => 'pengguna_dptb_pr'],
            ['label' => 'Pengguna DPTB Jumlah',     'sum'   => ['pengguna_dptb_lk', 'pengguna_dptb_pr'], 'bold' => true],
            ['label' => 'Pengguna DPK LK',          'field' => 'pengguna_dpk_lk'],
            ['label' => 'Pengguna DPK PR',          'field' => 'pengguna_dpk_pr'],
            ['label' => 'Pengguna DPK Jumlah',      'sum'   => ['pengguna_dpk_lk', 'pengguna_dpk_pr'], 'bold' => true],
            ['label' => 'Total Pengguna Hak Pilih LK', 'sum' => ['pengguna_dpt_lk','pengguna_dptb_lk','pengguna_dpk_lk'], 'bold' => true],
            ['label' => 'Total Pengguna Hak Pilih PR', 'sum' => ['pengguna_dpt_pr','pengguna_dptb_pr','pengguna_dpk_pr'], 'bold' => true],
            ['label' => 'Total Pengguna Hak Pilih', 'sum'   => ['pengguna_dpt_lk','pengguna_dpt_pr','pengguna_dptb_lk','pengguna_dptb_pr','pengguna_dpk_lk','pengguna_dpk_pr'], 'bold' => true],
        ];
        foreach ($rows1 as $row) {
            $rowTotal = 0;
            $cells    = [$row['label']];
            foreach ($tpsList as $tps) {
                $r   = $rekaps[$tps->id] ?? null;
                $val = $r ? (isset($row['field']) ? ($r->{$row['field']} ?? 0) : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0)) : 0;
                $cells[]   = $val;
                $rowTotal += $val;
            }
            if ($this->showTotal) $cells[] = $rowTotal;
            $rows[] = $cells;
            if ($row['bold'] ?? false) $this->totalRows[] = count($rows);
        }

        $rows[] = [''];

        // ── SECTION II ──
        $rows[]              = ['SECTION II — SURAT SUARA'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $rows2 = [
            ['label' => 'Surat Suara Diterima',  'field' => 'ss_diterima'],
            ['label' => 'Surat Suara Digunakan', 'field' => 'ss_digunakan'],
            ['label' => 'Surat Suara Rusak',     'field' => 'ss_rusak'],
            ['label' => 'Surat Suara Sisa',      'field' => 'ss_sisa', 'bold' => true],
        ];
        foreach ($rows2 as $row) {
            $rowTotal = 0;
            $cells    = [$row['label']];
            foreach ($tpsList as $tps) {
                $r   = $rekaps[$tps->id] ?? null;
                $val = $r ? ($r->{$row['field']} ?? 0) : 0;
                $cells[]   = $val;
                $rowTotal += $val;
            }
            if ($this->showTotal) $cells[] = $rowTotal;
            $rows[] = $cells;
            if ($row['bold'] ?? false) $this->totalRows[] = count($rows);
        }

        $rows[] = [''];

        // ── SECTION III ──
        $rows[]              = ['SECTION III — PEMILIH DISABILITAS'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $rows3 = [
            ['label' => 'Disabilitas Laki-laki', 'field' => 'disabilitas_lk'],
            ['label' => 'Disabilitas Perempuan', 'field' => 'disabilitas_pr'],
            ['label' => 'Disabilitas Jumlah',    'sum'   => ['disabilitas_lk', 'disabilitas_pr'], 'bold' => true],
        ];
        foreach ($rows3 as $row) {
            $rowTotal = 0;
            $cells    = [$row['label']];
            foreach ($tpsList as $tps) {
                $r   = $rekaps[$tps->id] ?? null;
                $val = $r ? (isset($row['field']) ? ($r->{$row['field']} ?? 0) : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0)) : 0;
                $cells[]   = $val;
                $rowTotal += $val;
            }
            if ($this->showTotal) $cells[] = $rowTotal;
            $rows[] = $cells;
            if ($row['bold'] ?? false) $this->totalRows[] = count($rows);
        }

        $rows[] = [''];

        // ── SECTION IV ──
        $rows[]              = ['SECTION IV — PEROLEHAN SUARA'];
        $this->sectionRows[] = count($rows);
       $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $masterJenis = $this->master[$this->jenis] ?? null;

        if (in_array($this->jenis, ['ppwp', 'gubernur', 'bupati', 'dpd'], true)) {
            $calons = $masterJenis['calons'] ?? collect();
            foreach ($calons as $calon) {
                $rowTotal = 0;
                $name     = in_array($this->jenis, ['ppwp', 'gubernur', 'bupati'], true) ? $calon->nama_paslon : $calon->nama_calon;
                $cells    = [$calon->nomor_urut . '. ' . $name];
                foreach ($tpsList as $tps) {
                    $r        = $rekaps[$tps->id] ?? null;
                    $suaraMap = $r ? match ($this->jenis) {
                        'ppwp' => $r->ppwpSuaras->pluck('suara', 'calon_id'),
                        'gubernur' => $r->gubernurSuaras->pluck('suara', 'calon_id'),
                        'bupati' => $r->bupatiSuaras->pluck('suara', 'calon_id'),
                        default => $r->dpdSuaras->pluck('suara', 'calon_id'),
                    } : collect();
                    $s        = $suaraMap[$calon->id] ?? 0;
                    $cells[]   = $s;
                    $rowTotal += $s;
                }
                if ($this->showTotal) $cells[] = $rowTotal;
                $rows[] = $cells;
            }
        } else {
            $partais = $masterJenis['partais'] ?? collect();
            foreach ($partais as $partai) {
                // Header partai
                $rows[]                = ['— ' . $partai->nomor_urut . '. ' . $partai->nama_partai];
                $this->subHeaderRows[] = count($rows);

                // Suara partai
                $rowTotal = 0;
                $cells    = ['  Suara Partai'];
                foreach ($tpsList as $tps) {
                    $r   = $rekaps[$tps->id] ?? null;
                    $sp  = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                    $cells[]   = $sp;
                    $rowTotal += $sp;
                }
                if ($this->showTotal) $cells[] = $rowTotal;
                $rows[] = $cells;

                // Per caleg
                foreach ($partai->calegs as $caleg) {
                    $rowTotal = 0;
                    $cells    = ['  ' . $caleg->nomor_urut . '. ' . $caleg->nama_caleg];
                    foreach ($tpsList as $tps) {
                        $r   = $rekaps[$tps->id] ?? null;
                        $sc  = $r ? ($r->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0) : 0;
                        $cells[]   = $sc;
                        $rowTotal += $sc;
                    }
                    if ($this->showTotal) $cells[] = $rowTotal;
                    $rows[] = $cells;
                }

                // Total suara sah
                $grandTotal = 0;
                $cells      = ['  Total Suara Sah'];
                foreach ($tpsList as $tps) {
                    $r      = $rekaps[$tps->id] ?? null;
                    $sp     = $r ? ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) : 0;
                    $scSum  = $r ? $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara') : 0;
                    $col    = $sp + $scSum;
                    $cells[]     = $col;
                    $grandTotal += $col;
                }
                if ($this->showTotal) $cells[] = $grandTotal;
                $rows[]            = $cells;
                $this->totalRows[] = count($rows);
                $rows[]            = [''];
            }
        }
        // ── SECTION V ──
        $rows[]              = ['SECTION V — SUARA SAH, TIDAK SAH & TOTAL'];
        $this->sectionRows[] = count($rows);
        $rows[] = $this->showTotal
            ? array_merge(['Keterangan'], $tpsNames, ['Total'])
            : array_merge(['Keterangan'], $tpsNames);
        $this->subHeaderRows[] = count($rows);

        $rows5 = [
            ['label' => 'Jumlah Suara Sah',      'field' => 'suara_sah'],
            ['label' => 'Jumlah Suara Tidak Sah', 'field' => 'suara_tidak_sah'],
        ];
        foreach ($rows5 as $row) {
            $rowTotal = 0;
            $cells    = [$row['label']];
            foreach ($tpsList as $tps) {
                $r   = $rekaps[$tps->id] ?? null;
                $val = $r ? ($r->{$row['field']} ?? 0) : 0;
                $cells[]   = $val;
                $rowTotal += $val;
            }
            if ($this->showTotal) $cells[] = $rowTotal;
            $rows[] = $cells;
        }

        // Total seluruh suara
        $grandTotal = 0;
        $cells      = ['Jumlah Seluruh Suara'];
        foreach ($tpsList as $tps) {
            $r   = $rekaps[$tps->id] ?? null;
            $val = $r ? (($r->suara_sah ?? 0) + ($r->suara_tidak_sah ?? 0)) : 0;
            $cells[]     = $val;
            $grandTotal += $val;
        }
        if ($this->showTotal) $cells[] = $grandTotal;
        $rows[]            = $cells;
        $this->totalRows[] = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();

        // Style judul
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $styles = [];

        // Baris judul
        $styles[1] = [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $styles[2] = [
            'font'      => ['italic' => true, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // Section headers
        foreach ($this->sectionRows as $row) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $styles[$row] = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ];
        }

        // Sub-header (kolom TPS)
        foreach ($this->subHeaderRows as $row) {
            $styles[$row] = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E6B9E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        // Baris total/bold
        foreach ($this->totalRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF5FB']],
            ];
        }

        // Border semua data
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
