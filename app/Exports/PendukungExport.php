<?php

namespace App\Exports;

use App\Models\Pendukung;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PendukungExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?int $kecamatanId = null,
        private readonly ?int $desaId = null,
        private readonly ?int $tpsId = null,
        private readonly ?string $search = null
    ) {}

    public function title(): string
    {
        return 'Data Pendukung';
    }

    public function array(): array
    {
        $query = Pendukung::with(['kecamatan', 'desa', 'tps', 'creator']);

        if ($this->kecamatanId) {
            $query->where('kecamatan_id', $this->kecamatanId);
        }
        if ($this->desaId) {
            $query->where('desa_id', $this->desaId);
        }
        if ($this->tpsId) {
            $query->where('tps_id', $this->tpsId);
        }
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        $pendukungs = $query->latest()->get();

        $rows = [];
        $no = 1;
        foreach ($pendukungs as $p) {
            $rows[] = [
                $no++,
                $p->nama,
                "'" . $p->nik, // Prefix with apostrophe to keep as string in Excel
                $p->no_hp,
                $p->alamat,
                $p->kecamatan?->nama ?? '-',
                $p->desa?->nama ?? '-',
                $p->tps?->nama ?? '-',
                $p->catatan ?? '-',
                $p->creator?->name ?? '-',
                $p->created_at->format('d/m/Y H:i'),
            ];
        }

        $headings = [
            'No',
            'Nama Pendukung',
            'NIK',
            'No. HP / WA',
            'Alamat',
            'Kecamatan',
            'Desa',
            'TPS',
            'Catatan',
            'Dibuat Oleh',
            'Waktu Input',
        ];

        return array_merge([
            ['DATA PENDUKUNG PARTAI GARUDA'],
            ['Dicetak pada', now()->format('d/m/Y H:i')],
            [''],
            $headings,
        ], $rows);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastCol}1");

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'BB152C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
            ],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BB152C']],
            ],
            "A1:{$lastCol}{$lastRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 24,
            'C' => 20,
            'D' => 16,
            'E' => 30,
            'F' => 18,
            'G' => 18,
            'H' => 12,
            'I' => 24,
            'J' => 18,
            'K' => 18,
        ];
    }
}
