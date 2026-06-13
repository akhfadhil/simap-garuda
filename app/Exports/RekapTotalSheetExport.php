<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RekapTotalSheetExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $jenis;
    protected $label;
    protected $rekaps;
    protected $master;
    protected $desas;
    protected $level;
    protected $wilayah;

    protected $headerRows    = [];
    protected $subHeaderRows = [];
    protected $totalRows     = [];
    protected $sectionRows   = [];

    public function __construct($jenis, $label, $rekaps, $master, $desas, $level, $wilayah)
    {
        $this->jenis   = $jenis;
        $this->label   = $label;
        $this->rekaps  = $rekaps->keyBy('tps_id');
        $this->master  = $master;
        $this->desas   = $desas;
        $this->level   = $level;
        $this->wilayah = $wilayah;
    }

    public function title(): string
    {
        return substr($this->label, 0, 31);
    }

    // Helper: sum nilai field untuk semua TPS di satu desa
    private function desaVal($desa, $row): int
    {
        return $desa->tps->sum(function($tps) use ($row) {
            $r = $this->rekaps[$tps->id] ?? null;
            if (!$r) return 0;
            return isset($row['field'])
                ? ($r->{$row['field']} ?? 0)
                : collect($row['sum'])->sum(fn($f) => $r->$f ?? 0);
        });
    }

    public function array(): array
    {
        $rows     = [];
        $desaList = $this->desas;
        $desaNames = $desaList->map(fn($d) => $d->nama)->toArray();

        // ── JUDUL ──
        $rows[] = ['REKAPITULASI ' . strtoupper($this->label) . ' — ' . strtoupper($this->wilayah)];
        $rows[] = [''];
        $this->headerRows[] = 1;

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

        // Helper build section rows
        $buildSection = function($title, $rowDefs) use ($desaList, $desaNames, &$rows) {
            $rows[]              = [$title];
            $this->sectionRows[] = count($rows);
            $rows[]              = array_merge(['Keterangan'], $desaNames, ['Total']);
            $this->subHeaderRows[] = count($rows);

            foreach ($rowDefs as $row) {
                $rowTotal = 0;
                $cells    = [$row['label']];
                foreach ($desaList as $desa) {
                    $val       = $this->desaVal($desa, $row);
                    $cells[]   = $val;
                    $rowTotal += $val;
                }
                $cells[] = $rowTotal;
                $rows[]  = $cells;
                if ($row['bold'] ?? false) $this->totalRows[] = count($rows);
            }
            $rows[] = [''];
        };

        $buildSection('SECTION I — DPT & PENGGUNA HAK PILIH', $rows1);
        $buildSection('SECTION II — SURAT SUARA', $rows2);
        $buildSection('SECTION III — PEMILIH DISABILITAS', $rows3);

        // ── SECTION IV ──
        $rows[]              = ['SECTION IV — PEROLEHAN SUARA'];
        $this->sectionRows[] = count($rows);
        $rows[]              = array_merge(['Keterangan'], $desaNames, ['Total']);
        $this->subHeaderRows[] = count($rows);

        if (in_array($this->jenis, ['ppwp', 'gubernur', 'bupati', 'dpd'], true)) {
            $calons = $this->master['calons'] ?? collect();
            foreach ($calons as $calon) {
                $rowTotal = 0;
                $name     = in_array($this->jenis, ['ppwp', 'gubernur', 'bupati'], true) ? $calon->nama_paslon : $calon->nama_calon;
                $cells    = [$calon->nomor_urut . '. ' . $name];
                foreach ($desaList as $desa) {
                    $val = $desa->tps->sum(function($tps) use ($calon) {
                        $r = $this->rekaps[$tps->id] ?? null;
                        if (!$r) return 0;
                        return match ($this->jenis) {
                            'ppwp' => $r->ppwpSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                            'gubernur' => $r->gubernurSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                            'bupati' => $r->bupatiSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                            default => $r->dpdSuaras->firstWhere('calon_id', $calon->id)?->suara ?? 0,
                        };
                    });
                    $cells[]   = $val;
                    $rowTotal += $val;
                }
                $cells[] = $rowTotal;
                $rows[]  = $cells;
            }
        } else {
            $partais = $this->master['partais'] ?? collect();
            foreach ($partais as $partai) {
                $rows[]                = ['— ' . $partai->nomor_urut . '. ' . $partai->nama_partai];
                $this->subHeaderRows[] = count($rows);

                // Suara partai
                $rowTotal = 0;
                $cells    = ['  Suara Partai'];
                foreach ($desaList as $desa) {
                    $val = $desa->tps->sum(fn($tps) => ($this->rekaps[$tps->id] ?? null)?->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0);
                    $cells[]   = $val;
                    $rowTotal += $val;
                }
                $cells[] = $rowTotal;
                $rows[]  = $cells;

                // Per caleg
                foreach ($partai->calegs as $caleg) {
                    $rowTotal = 0;
                    $cells    = ['  ' . $caleg->nomor_urut . '. ' . $caleg->nama_caleg];
                    foreach ($desaList as $desa) {
                        $val = $desa->tps->sum(fn($tps) => ($this->rekaps[$tps->id] ?? null)?->calegSuaras->firstWhere('caleg_id', $caleg->id)?->suara ?? 0);
                        $cells[]   = $val;
                        $rowTotal += $val;
                    }
                    $cells[] = $rowTotal;
                    $rows[]  = $cells;
                }

                // Total suara sah partai
                $grandTotal = 0;
                $cells      = ['  Total Suara Sah'];
                foreach ($desaList as $desa) {
                    $val = $desa->tps->sum(function($tps) use ($partai) {
                        $r     = $this->rekaps[$tps->id] ?? null;
                        if (!$r) return 0;
                        $sp    = $r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0;
                        $scSum = $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara');
                        return $sp + $scSum;
                    });
                    $cells[]     = $val;
                    $grandTotal += $val;
                }
                $cells[]           = $grandTotal;
                $rows[]            = $cells;
                $this->totalRows[] = count($rows);
                $rows[]            = [''];
            }
        }

        $rows[] = [''];

        // ── SECTION V ──
        $rows[]              = ['SECTION V — SUARA SAH, TIDAK SAH & TOTAL'];
        $this->sectionRows[] = count($rows);
        $rows[]              = array_merge(['Keterangan'], $desaNames, ['Total']);
        $this->subHeaderRows[] = count($rows);

        foreach ([
            ['label' => 'Jumlah Suara Sah',       'field' => 'suara_sah'],
            ['label' => 'Jumlah Suara Tidak Sah',  'field' => 'suara_tidak_sah'],
        ] as $row) {
            $rowTotal = 0;
            $cells    = [$row['label']];
            foreach ($desaList as $desa) {
                $val = $desa->tps->sum(fn($tps) => $this->rekaps[$tps->id]?->{$row['field']} ?? 0);
                $cells[]   = $val;
                $rowTotal += $val;
            }
            $cells[] = $rowTotal;
            $rows[]  = $cells;
        }

        $grandTotal = 0;
        $cells      = ['Jumlah Seluruh Suara'];
        foreach ($desaList as $desa) {
            $val = $desa->tps->sum(fn($tps) => ($this->rekaps[$tps->id] ? ($this->rekaps[$tps->id]->suara_sah + $this->rekaps[$tps->id]->suara_tidak_sah) : 0));
            $cells[]     = $val;
            $grandTotal += $val;
        }
        $cells[]           = $grandTotal;
        $rows[]            = $cells;
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
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        foreach ($this->sectionRows as $row) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $styles[$row] = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ];
        }

        foreach ($this->subHeaderRows as $row) {
            $styles[$row] = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E6B9E']],
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
