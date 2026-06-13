<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WritesImportReport;
use App\Models\Desa;
use App\Models\RekapDpdCalon;
use App\Models\RekapDpdSuara;
use App\Models\RekapHeader;
use App\Models\Tps;
use App\Services\RekapAdminCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDpdFolder extends Command
{
    use WritesImportReport;

    /*
     * CARA PAKAI IMPORTER DPD FOLDER
     * -------------------------------------------------------------------------
     * Importer ini dibuat khusus untuk data historis DPD dari file Excel lama.
     * Pola file yang didukung:
     *
     * - Satu folder berisi banyak file Excel.
     * - Satu file Excel mewakili satu kecamatan.
     * - Nama file mengikuti pola "DPD - NAMAKECAMATAN.xlsx".
     * - Di dalam satu file, tiap sheet mewakili satu desa.
     * - Nama sheet dipakai sebagai nama desa utama.
     * - Sheet pembuka seperti "DPD" akan dilewati otomatis kalau tidak punya
     *   header TPS di baris 13.
     *
     * Contoh lokasi file:
     *
     *   storage/import/DPD/DPD - BANGOREJO.xlsx
     *   storage/import/DPD/DPD - BANYUWANGI.xlsx
     *
     * WAJIB cek dulu tanpa mengubah database:
     *
     *   php artisan import:dpd-folder "storage/import/DPD" --dry-run
     *
     * Tulis detail masalah/koreksi ke file txt tanpa limit tampilan console:
     *
     *   php artisan import:dpd-folder "storage/import/DPD" --dry-run --report
     *   php artisan import:dpd-folder "storage/import/DPD" --dry-run --report=storage/app/import-reports/dpd.txt
     *
     * Kalau --report tidak diberi path, file otomatis dibuat di:
     *
     *   storage/app/import-reports/import-folder-dpd-YYYYMMDD-HHMMSS.txt
     *
     * Cek satu kecamatan saja:
     *
     *   php artisan import:dpd-folder "storage/import/DPD" --only=Bangorejo --dry-run
     *
     * Cek satu desa/sheet saja di dalam kecamatan:
     *
     *   php artisan import:dpd-folder "storage/import/DPD" --only=Bangorejo --desa=Sambirejo --dry-run
     *
     * Import asli ke database setelah dry-run bersih:
     *
     *   php artisan import:dpd-folder "storage/import/DPD"
     *
     * Hasil import:
     *
     * - Membuat TPS jika belum ada pada desa yang cocok.
     * - Menyinkronkan master calon DPD dari baris 45-57.
     * - Menyimpan atau memperbarui rekap_headers untuk jenis "dpd".
     * - Menyimpan suara calon DPD ke rekap_dpd_suaras.
     * - Status rekap diset "final" karena ini data hasil historis.
     */
    protected $signature = 'import:dpd-folder
        {path=storage/import/DPD : Folder berisi file DPD per kecamatan atau satu file Excel DPD}
        {--dry-run : Validasi dan tampilkan ringkasan tanpa menyimpan ke database}
        {--only=* : Batasi import ke nama kecamatan tertentu}
        {--desa=* : Batasi import ke nama desa/sheet tertentu}
        {--report= : Tulis detail masalah dan koreksi ke file txt; kosongkan nilainya untuk path otomatis}';

    protected $description = 'Import rekap DPD dari folder Excel per kecamatan dan sheet per desa.';

    private const CALON_START_ROW = 45;

    private const CALON_END_ROW = 57;

    private const DESA_ALIASES = [
        'Kabat' => [
            'Pakisataji' => 'Pakistaji',
        ],
        'Kalibaru' => [
            'Kalibaru Kulon' => 'Kalibarukulon',
            'Kalibaru Manis' => 'Kalibarumanis',
            'Kalibaru Wetan' => 'Kalibaruwetan',
        ],
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path) && ! is_dir($path)) {
            $this->error('Path tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        $files = $this->filesFromPath($path);

        if ($files === []) {
            $this->error('Tidak ada file Excel DPD yang ditemukan di: '.$path);

            return self::FAILURE;
        }

        $rows = [];
        $corrections = [];
        $missing = [];
        $invalid = [];
        $warnings = [];
        $calons = [];

        foreach ($files as $file) {
            $kecamatan = $this->kecamatanFromFile($file);

            if (! $this->shouldImportKecamatan($kecamatan)) {
                continue;
            }

            $spreadsheet = IOFactory::load($file);
            $fileCalons = $this->calonsFromWorkbook($spreadsheet);

            if ($fileCalons === []) {
                $invalid[] = basename($file).': daftar calon DPD tidak terbaca dari baris '.self::CALON_START_ROW.'-'.self::CALON_END_ROW.'.';

                continue;
            }

            if ($calons === []) {
                $calons = $fileCalons;
            } elseif ($this->calonSignature($calons) !== $this->calonSignature($fileCalons)) {
                $invalid[] = basename($file).': daftar calon DPD berbeda dari file sebelumnya.';

                continue;
            }

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                if (str_starts_with(strtolower($sheet->getTitle()), 'copy of ')) {
                    $warnings[] = basename($file)." / {$sheet->getTitle()}: sheet salinan dilewati.";

                    continue;
                }

                $tpsColumns = $this->tpsColumns($sheet);

                if ($tpsColumns === []) {
                    continue;
                }

                $sheetDesa = $this->titleName($sheet->getTitle());
                $desaNama = $this->resolveDesaName($kecamatan, $sheetDesa);
                $d9Desa = $this->titleName((string) $this->cell($sheet, 'D9'));

                if (! $this->shouldImportDesa($kecamatan, $desaNama)) {
                    continue;
                }

                if ($d9Desa !== '' && $this->resolveDesaName($kecamatan, $d9Desa) !== $desaNama) {
                    $warnings[] = basename($file)." / {$sheet->getTitle()}: D9 berisi {$d9Desa}; nama sheet {$sheetDesa} dipakai.";
                }

                if (! $this->findDesa($kecamatan, $desaNama)) {
                    $missing[] = "{$kecamatan} / {$desaNama}: desa tidak ditemukan.";

                    continue;
                }

                foreach ($tpsColumns as $column => $tpsNama) {
                    $record = $this->recordFromSheet(
                        $sheet,
                        $column,
                        $kecamatan,
                        $desaNama,
                        strtoupper($tpsNama),
                        basename($file),
                        $calons
                    );

                    if ($this->recordLooksIncomplete($record)) {
                        $invalid[] = "{$record['source_file']} / {$record['desa']} / {$record['tps']}: pengguna ada, tetapi suara calon/total suara kosong.";

                        continue;
                    }

                    $this->normalizeRecord($record, $corrections);
                    $rows[] = $record;
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        if ($rows === []) {
            $this->error('Tidak ada data TPS yang terbaca dari file.');
            $this->printProblems($missing, $invalid, $warnings);
            $reportPath = $this->writeImportReport('Import folder DPD', [
                'Calon terbaca' => count($calons),
                'TPS terbaca' => 0,
                'TPS tidak aman diimpor' => count($invalid),
                'Koreksi data' => count($corrections),
            ], [
                'Data wilayah tidak cocok' => $missing,
                'Data TPS tidak aman diimpor' => $invalid,
                'Catatan struktur Excel' => $warnings,
                'Daftar koreksi' => $corrections,
            ]);

            if ($reportPath) {
                $this->info('Detail laporan import ditulis ke: '.$reportPath);
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('DRY RUN: database tidak diubah.');
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $calons);

            return $missing === [] && $invalid === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($missing !== [] || $invalid !== []) {
            $this->error('Import dibatalkan karena masih ada data yang tidak aman untuk diimpor.');
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $calons);

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows, $calons) {
            $calonIds = $this->syncCalons($calons);

            foreach ($rows as $row) {
                $desa = $this->findDesa($row['kecamatan'], $row['desa']);

                $tps = Tps::firstOrCreate([
                    'desa_id' => $desa->id,
                    'nama' => $row['tps'],
                ]);

                $rekap = RekapHeader::updateOrCreate(
                    ['tps_id' => $tps->id, 'jenis' => 'dpd'],
                    [
                        'dpt_lk' => $row['dpt_lk'],
                        'dpt_pr' => $row['dpt_pr'],
                        'pengguna_dpt_lk' => $row['pengguna_dpt_lk'],
                        'pengguna_dpt_pr' => $row['pengguna_dpt_pr'],
                        'pengguna_dptb_lk' => $row['pengguna_dptb_lk'],
                        'pengguna_dptb_pr' => $row['pengguna_dptb_pr'],
                        'pengguna_dpk_lk' => $row['pengguna_dpk_lk'],
                        'pengguna_dpk_pr' => $row['pengguna_dpk_pr'],
                        'ss_diterima' => $row['ss_diterima'],
                        'ss_digunakan' => $row['ss_digunakan'],
                        'ss_rusak' => $row['ss_rusak'],
                        'ss_sisa' => $row['ss_sisa'],
                        'disabilitas_lk' => $row['disabilitas_lk'],
                        'disabilitas_pr' => $row['disabilitas_pr'],
                        'suara_tidak_sah' => $row['suara_tidak_sah'],
                        'status' => 'final',
                        'difinalisasi_at' => Carbon::now(),
                    ]
                );

                foreach ($row['suara'] as $nomorUrut => $suara) {
                    RekapDpdSuara::updateOrCreate(
                        ['rekap_id' => $rekap->id, 'calon_id' => $calonIds[$nomorUrut]],
                        ['suara' => $suara]
                    );
                }

                RekapDpdSuara::where('rekap_id', $rekap->id)
                    ->whereNotIn('calon_id', array_values($calonIds))
                    ->delete();
            }
        });

        RekapAdminCache::flushAggregate();
        $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $calons);

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        return is_file($path) || is_dir($path) ? $path : base_path($path);
    }

    private function filesFromPath(string $path): array
    {
        $files = is_file($path) ? [$path] : glob(rtrim($path, DIRECTORY_SEPARATOR).'/*.xlsx');
        sort($files);

        return $files;
    }

    private function kecamatanFromFile(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);
        $name = preg_replace('/^DPD\s*-\s*/i', '', $name);

        return $this->titleName(str_replace('_', ' ', $name));
    }

    private function shouldImportKecamatan(string $kecamatan): bool
    {
        $only = array_map(
            fn ($value) => strtolower($this->titleName((string) $value)),
            (array) $this->option('only')
        );

        return $only === [] || in_array(strtolower($kecamatan), $only, true);
    }

    private function shouldImportDesa(string $kecamatan, string $desa): bool
    {
        $only = array_map(
            fn ($value) => strtolower($this->resolveDesaName($kecamatan, $this->titleName((string) $value))),
            (array) $this->option('desa')
        );

        return $only === [] || in_array(strtolower($desa), $only, true);
    }

    private function calonsFromWorkbook($spreadsheet): array
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($this->tpsColumns($sheet) === []) {
                continue;
            }

            $calons = [];

            for ($row = self::CALON_START_ROW; $row <= self::CALON_END_ROW; $row++) {
                $nomor = $this->intCell($sheet, "B{$row}");
                $nama = trim((string) $this->cell($sheet, "C{$row}"));

                if ($nomor > 0 && $nama !== '') {
                    $calons[$nomor] = [
                        'nama' => $nama,
                        'row' => $row,
                    ];
                }
            }

            if ($calons !== []) {
                ksort($calons);

                return $calons;
            }
        }

        return [];
    }

    private function calonSignature(array $calons): string
    {
        return md5(json_encode(collect($calons)->map(fn ($calon) => $calon['nama'])->all()));
    }

    private function tpsColumns(Worksheet $sheet): array
    {
        $columns = [];

        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn(13));
        for ($col = Coordinate::columnIndexFromString('E'); $col <= $lastColumn; $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $tpsNama = trim((string) $this->cell($sheet, "{$column}13"));

            if (preg_match('/^TPS\s+\d{3}$/i', $tpsNama)) {
                $columns[$column] = $tpsNama;
            }
        }

        return $columns;
    }

    private function recordFromSheet(Worksheet $sheet, string $column, string $kecamatan, string $desaNama, string $tpsNama, string $sourceFile, array $calons): array
    {
        $suara = [];

        foreach ($calons as $nomorUrut => $calon) {
            $suara[$nomorUrut] = $this->intCell($sheet, $column.$calon['row']);
        }

        return [
            'source_file' => $sourceFile,
            'kecamatan' => $kecamatan,
            'desa' => $desaNama,
            'tps' => $tpsNama,
            'dpt_lk' => $this->intCell($sheet, "{$column}14"),
            'dpt_pr' => $this->intCell($sheet, "{$column}15"),
            'pengguna_dpt_lk' => $this->intCell($sheet, "{$column}19"),
            'pengguna_dpt_pr' => $this->intCell($sheet, "{$column}20"),
            'pengguna_dptb_lk' => $this->intCell($sheet, "{$column}22"),
            'pengguna_dptb_pr' => $this->intCell($sheet, "{$column}23"),
            'pengguna_dpk_lk' => $this->intCell($sheet, "{$column}25"),
            'pengguna_dpk_pr' => $this->intCell($sheet, "{$column}26"),
            'pengguna_total_excel' => $this->intCell($sheet, "{$column}30"),
            'ss_diterima' => $this->intCell($sheet, "{$column}33"),
            'ss_digunakan' => $this->intCell($sheet, "{$column}34"),
            'ss_rusak' => $this->intCell($sheet, "{$column}35"),
            'ss_sisa' => $this->intCell($sheet, "{$column}36"),
            'disabilitas_lk' => $this->intCell($sheet, "{$column}39"),
            'disabilitas_pr' => $this->intCell($sheet, "{$column}40"),
            'suara' => $suara,
            'suara_sah_excel' => $this->intCell($sheet, "{$column}60"),
            'suara_tidak_sah' => $this->intCell($sheet, "{$column}61"),
            'suara_total_excel' => $this->intCell($sheet, "{$column}62"),
        ];
    }

    private function normalizeRecord(array &$row, array &$corrections): void
    {
        $label = "{$row['kecamatan']} / {$row['desa']} / {$row['tps']}";
        $suaraSah = array_sum($row['suara']);

        if ($row['suara_sah_excel'] !== $suaraSah) {
            $corrections[] = "{$label}: suara sah Excel {$row['suara_sah_excel']} disesuaikan ke jumlah calon {$suaraSah}.";
        }

        $penggunaTotal = $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr']
            + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr']
            + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr'];

        if ($row['pengguna_total_excel'] !== $penggunaTotal) {
            $before = $row['pengguna_dpt_pr'];
            $row['pengguna_dpt_pr'] = max(0, $row['pengguna_dpt_pr'] + ($row['pengguna_total_excel'] - $penggunaTotal));
            $corrections[] = "{$label}: total pengguna Excel {$row['pengguna_total_excel']} tidak sama dengan rincian {$penggunaTotal}; pengguna_dpt_pr disesuaikan {$before} -> {$row['pengguna_dpt_pr']}.";
            $penggunaTotal = $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr']
                + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr']
                + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr'];
        }

        $authoritativeTotal = $row['ss_digunakan'] > 0 ? $row['ss_digunakan'] : $row['suara_total_excel'];
        if ($authoritativeTotal <= 0) {
            $authoritativeTotal = $suaraSah + $row['suara_tidak_sah'];
        }

        if ($penggunaTotal !== $authoritativeTotal) {
            $before = $row['pengguna_dpt_pr'];
            $row['pengguna_dpt_pr'] = max(0, $row['pengguna_dpt_pr'] + ($authoritativeTotal - $penggunaTotal));
            $corrections[] = "{$label}: pengguna hak pilih {$penggunaTotal} tidak sama dengan surat suara digunakan {$authoritativeTotal}; pengguna_dpt_pr disesuaikan {$before} -> {$row['pengguna_dpt_pr']}.";
        }

        $expectedTidakSah = max(0, $authoritativeTotal - $suaraSah);
        if ($row['suara_tidak_sah'] !== $expectedTidakSah) {
            $corrections[] = "{$label}: suara tidak sah {$row['suara_tidak_sah']} disesuaikan ke surat suara digunakan - suara calon {$expectedTidakSah}.";
            $row['suara_tidak_sah'] = $expectedTidakSah;
        }

        $suaraTotal = $suaraSah + $row['suara_tidak_sah'];
        if ($row['suara_total_excel'] !== $suaraTotal) {
            $corrections[] = "{$label}: total suara Excel {$row['suara_total_excel']} tidak sama dengan hasil koreksi {$suaraTotal}.";
        }

        if ($row['ss_digunakan'] !== $authoritativeTotal) {
            $corrections[] = "{$label}: surat suara digunakan {$row['ss_digunakan']} disesuaikan ke {$authoritativeTotal}.";
            $row['ss_digunakan'] = $authoritativeTotal;
        }

        $ssSisa = max(0, $row['ss_diterima'] - $row['ss_digunakan'] - $row['ss_rusak']);
        if ($row['ss_sisa'] !== $ssSisa) {
            $corrections[] = "{$label}: surat suara sisa {$row['ss_sisa']} disesuaikan ke diterima-digunakan-rusak {$ssSisa}.";
            $row['ss_sisa'] = $ssSisa;
        }
    }

    private function recordLooksIncomplete(array $row): bool
    {
        $penggunaTotal = $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr']
            + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr']
            + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr'];

        return $penggunaTotal > 0
            && array_sum($row['suara']) === 0
            && $row['suara_total_excel'] === 0;
    }

    private function syncCalons(array $calons): array
    {
        $ids = [];

        foreach ($calons as $nomorUrut => $calon) {
            $model = RekapDpdCalon::updateOrCreate(
                ['nomor_urut' => $nomorUrut],
                ['nama_calon' => $calon['nama']]
            );
            $ids[$nomorUrut] = $model->id;
        }

        return $ids;
    }

    private function resolveDesaName(string $kecamatan, string $desa): string
    {
        return self::DESA_ALIASES[$kecamatan][$desa] ?? $desa;
    }

    private function findDesa(string $kecamatan, string $desa): ?Desa
    {
        return Desa::query()
            ->whereRaw('LOWER(nama) = ?', [strtolower($desa)])
            ->whereHas('kecamatan', fn ($query) => $query->whereRaw('LOWER(nama) = ?', [strtolower($kecamatan)]))
            ->first();
    }

    private function printReport(array $rows, array $corrections, array $missing, array $invalid, array $warnings, array $calons): void
    {
        $reportSummary = [
            'Calon terbaca' => count($calons),
            'TPS terbaca' => count($rows),
            'File terbaca' => collect($rows)->pluck('source_file')->unique()->count(),
            'Kecamatan terbaca' => collect($rows)->pluck('kecamatan')->unique()->count(),
            'Desa terbaca' => collect($rows)->map(fn ($row) => $row['kecamatan'].' / '.$row['desa'])->unique()->count(),
            'TPS tidak aman diimpor' => count($invalid),
            'Koreksi data' => count($corrections),
        ];

        $this->newLine();
        $this->info('Import folder DPD selesai.');
        foreach ($reportSummary as $label => $value) {
            $this->line($label.': '.$value);
        }

        $summary = collect($rows)
            ->groupBy('kecamatan')
            ->map(fn ($items, $kecamatan) => [
                'Kecamatan' => $kecamatan,
                'Desa' => $items->pluck('desa')->unique()->count(),
                'TPS' => $items->count(),
                'DPT' => $items->sum(fn ($row) => $row['dpt_lk'] + $row['dpt_pr']),
                'Pengguna' => $items->sum(fn ($row) => $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr'] + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr'] + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr']),
                'Sah' => $items->sum(fn ($row) => array_sum($row['suara'])),
                'Tidak Sah' => $items->sum('suara_tidak_sah'),
            ])
            ->values()
            ->all();

        $this->table(['Kecamatan', 'Desa', 'TPS', 'DPT', 'Pengguna', 'Sah', 'Tidak Sah'], $summary);

        $this->line('Daftar calon DPD:');
        foreach ($calons as $nomor => $calon) {
            $this->line("- {$nomor}. {$calon['nama']}");
        }

        $this->printProblems($missing, $invalid, $warnings);

        if ($corrections !== []) {
            $this->warn('Daftar koreksi (maksimal 100 ditampilkan):');
            foreach (array_slice($corrections, 0, 100) as $message) {
                $this->line('- '.$message);
            }

            if (count($corrections) > 100) {
                $this->line('- ... '.(count($corrections) - 100).' koreksi lainnya tidak ditampilkan.');
            }
        }

        $reportPath = $this->writeImportReport('Import folder DPD', $reportSummary, [
            'Data wilayah tidak cocok' => $missing,
            'Data TPS tidak aman diimpor' => $invalid,
            'Catatan struktur Excel' => $warnings,
            'Daftar koreksi' => $corrections,
        ]);

        if ($reportPath) {
            $this->info('Detail laporan import ditulis ke: '.$reportPath);
        }
    }

    private function printProblems(array $missing, array $invalid, array $warnings): void
    {
        if ($missing !== []) {
            $this->warn('Data wilayah tidak cocok:');
            foreach ($missing as $message) {
                $this->line('- '.$message);
            }
        }

        if ($invalid !== []) {
            $this->warn('Data TPS tidak aman diimpor:');
            foreach (array_slice($invalid, 0, 100) as $message) {
                $this->line('- '.$message);
            }

            if (count($invalid) > 100) {
                $this->line('- ... '.(count($invalid) - 100).' data TPS lainnya tidak ditampilkan.');
            }
        }

        if ($warnings !== []) {
            $this->warn('Catatan struktur Excel:');
            foreach ($warnings as $message) {
                $this->line('- '.$message);
            }
        }
    }

    private function intCell(Worksheet $sheet, string $cell): int
    {
        $value = $this->cell($sheet, $cell);

        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round((float) $value);
    }

    private function cell(Worksheet $sheet, string $cell): mixed
    {
        return $sheet->getCell($cell)->getCalculatedValue();
    }

    private function titleName(string $value): string
    {
        return Str::of($value)->lower()->title()->toString();
    }
}
