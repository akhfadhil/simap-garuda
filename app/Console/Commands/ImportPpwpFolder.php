<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WritesImportReport;
use App\Models\Desa;
use App\Models\RekapHeader;
use App\Models\RekapPpwpCalon;
use App\Models\RekapPpwpSuara;
use App\Models\Tps;
use App\Services\RekapAdminCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportPpwpFolder extends Command
{
    use WritesImportReport;

    /*
     * CARA PAKAI IMPORTER PPWP FOLDER
     * -------------------------------------------------------------------------
     * Importer ini dibuat khusus untuk data historis PPWP dari file Excel lama.
     * Pola file yang didukung:
     *
     * - Satu folder berisi banyak file Excel.
     * - Satu file Excel mewakili satu kecamatan.
     * - Nama file mengikuti pola "PPWP - NAMAKECAMATAN.xlsx".
     * - Di dalam satu file, tiap sheet mewakili satu desa.
     * - Nama sheet dipakai sebagai nama desa utama.
     * - Sheet pembuka seperti "PPWP" akan dilewati otomatis kalau tidak punya
     *   header TPS di baris 13.
     *
     * Contoh lokasi file:
     *
     *   storage/import/PPWP/PPWP - BANGOREJO.xlsx
     *   storage/import/PPWP/PPWP - BANYUWANGI.xlsx
     *   storage/import/PPWP/PPWP - TEGALSARI.xlsx
     *
     * WAJIB cek dulu tanpa mengubah database:
     *
     *   php artisan import:ppwp-folder "storage/import/PPWP" --dry-run
     *
     * Tulis detail masalah/koreksi ke file txt tanpa limit tampilan console:
     *
     *   php artisan import:ppwp-folder "storage/import/PPWP" --dry-run --report
     *   php artisan import:ppwp-folder "storage/import/PPWP" --dry-run --report=storage/app/import-reports/ppwp.txt
     *
     * Kalau --report tidak diberi path, file otomatis dibuat di:
     *
     *   storage/app/import-reports/import-folder-ppwp-YYYYMMDD-HHMMSS.txt
     *
     * Cek satu kecamatan saja:
     *
     *   php artisan import:ppwp-folder "storage/import/PPWP" --only=Bangorejo --dry-run
     *
     * Cek satu desa/sheet saja di dalam kecamatan:
     *
     *   php artisan import:ppwp-folder "storage/import/PPWP" --only=Bangorejo --desa=Sambirejo --dry-run
     *
     * Import asli ke database setelah dry-run bersih:
     *
     *   php artisan import:ppwp-folder "storage/import/PPWP"
     *
     * Importer akan membatalkan import asli kalau masih ada:
     *
     * - Desa/kecamatan yang tidak cocok dengan master wilayah database.
     * - TPS yang punya pengguna hak pilih, tetapi suara paslon dan total suara
     *   kosong/0. Data seperti ini berbahaya kalau disimpan sebagai rekap final.
     *
     * Hasil import:
     *
     * - Membuat TPS jika belum ada pada desa yang cocok.
     * - Menyimpan atau memperbarui rekap_headers untuk jenis "ppwp".
     * - Menyimpan suara paslon PPWP ke rekap_ppwp_suaras.
     * - Status rekap diset "final" karena ini data hasil historis.
     */
    protected $signature = 'import:ppwp-folder
        {path=storage/import/PPWP : Folder berisi file PPWP per kecamatan atau satu file Excel PPWP}
        {--dry-run : Validasi dan tampilkan ringkasan tanpa menyimpan ke database}
        {--only=* : Batasi import ke nama kecamatan tertentu}
        {--desa=* : Batasi import ke nama desa/sheet tertentu}
        {--report= : Tulis detail masalah dan koreksi ke file txt; kosongkan nilainya untuk path otomatis}';

    protected $description = 'Import rekap PPWP dari folder Excel per kecamatan dan sheet per desa.';

    // Master pasangan calon PPWP yang akan disinkronkan sebelum import asli.
    private const PPWP_CALONS = [
        1 => 'H. ANIES RASYID BASWEDAN, Ph.D. - Dr. (H.C.) H. A. MUHAIMIN ISKANDAR',
        2 => 'H. PRABOWO SUBIANTO - GIBRAN RAKABUMING RAKA',
        3 => 'H. GANJAR PRANOWO, S.H., M.I.P. - Prof. Dr. H. MAHFUD MD',
    ];

    // Alias nama desa untuk beda ejaan antara Excel dan master wilayah database.
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
        // Path boleh relatif dari root project atau path absolut Windows.
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path) && ! is_dir($path)) {
            $this->error('Path tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        // Kalau path berupa folder, semua file .xlsx di folder itu akan dibaca.
        // Kalau path berupa file, hanya file itu yang dibaca.
        $files = $this->filesFromPath($path);

        if ($files === []) {
            $this->error('Tidak ada file Excel PPWP yang ditemukan di: '.$path);

            return self::FAILURE;
        }

        $rows = [];
        $corrections = [];
        $missing = [];
        $invalid = [];
        $warnings = [];

        foreach ($files as $file) {
            // Nama kecamatan diambil dari nama file, misalnya:
            // "PPWP - BANGOREJO.xlsx" menjadi "Bangorejo".
            $kecamatan = $this->kecamatanFromFile($file);

            if (! $this->shouldImportKecamatan($kecamatan)) {
                continue;
            }

            $spreadsheet = IOFactory::load($file);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $tpsColumns = $this->tpsColumns($sheet);

                // Sheet tanpa header TPS di baris 13 dianggap sheet pembuka
                // atau sheet kosong, jadi dilewati.
                if ($tpsColumns === []) {
                    continue;
                }

                // Nama sheet adalah sumber utama nama desa. Sel D9 hanya dipakai
                // sebagai pembanding karena ada file yang D9-nya salah isi.
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
                    // Semua angka TPS dibaca dari kolom yang sama:
                    // E untuk TPS 001, F untuk TPS 002, dan seterusnya sampai
                    // kolom TPS terakhir yang terdeteksi di baris 13.
                    $record = $this->recordFromSheet(
                        $sheet,
                        $column,
                        $kecamatan,
                        $desaNama,
                        strtoupper($tpsNama),
                        basename($file)
                    );

                    // Data seperti ini tidak diimport karena tampak belum lengkap:
                    // ada pengguna, tetapi suara paslon dan total suara kosong.
                    if ($this->recordLooksIncomplete($record)) {
                        $invalid[] = "{$record['source_file']} / {$record['desa']} / {$record['tps']}: pengguna ada, tetapi suara paslon/total suara kosong.";

                        continue;
                    }

                    $this->normalizeRecord($record, $corrections);
                    $rows[] = $record;
                }
            }
        }

        if ($rows === []) {
            $this->error('Tidak ada data TPS yang terbaca dari file.');
            $this->printProblems($missing, $invalid, $warnings);
            $reportPath = $this->writeImportReport('Import folder PPWP', [
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

        // Dry-run hanya menampilkan ringkasan, koreksi, dan masalah data.
        // Tidak ada insert/update ke database pada cabang ini.
        if ($this->option('dry-run')) {
            $this->line('DRY RUN: database tidak diubah.');
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings);

            return $missing === [] && $invalid === [] ? self::SUCCESS : self::FAILURE;
        }

        // Import asli sengaja dibuat ketat. Kalau masih ada data tidak cocok,
        // command berhenti agar database tidak berisi rekap final yang keliru.
        if ($missing !== [] || $invalid !== []) {
            $this->error('Import dibatalkan karena masih ada data yang tidak aman untuk diimpor.');
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings);

            return self::FAILURE;
        }

        // Mulai dari titik ini database akan ditulis. Transaction memastikan
        // semua row sukses bersama, atau rollback kalau ada error.
        DB::transaction(function () use ($rows) {
            $calonIds = $this->syncCalons();

            foreach ($rows as $row) {
                $desa = $this->findDesa($row['kecamatan'], $row['desa']);

                $tps = Tps::firstOrCreate([
                    'desa_id' => $desa->id,
                    'nama' => $row['tps'],
                ]);

                $rekap = RekapHeader::updateOrCreate(
                    ['tps_id' => $tps->id, 'jenis' => 'ppwp'],
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
                    RekapPpwpSuara::updateOrCreate(
                        ['rekap_id' => $rekap->id, 'calon_id' => $calonIds[$nomorUrut]],
                        ['suara' => $suara]
                    );
                }

                RekapPpwpSuara::where('rekap_id', $rekap->id)
                    ->whereNotIn('calon_id', array_values($calonIds))
                    ->delete();
            }
        });

        RekapAdminCache::flushAggregate();
        $this->printReport($rows, $corrections, $missing, $invalid, $warnings);

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
        $name = preg_replace('/^PPWP\s*-\s*/i', '', $name);

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

    private function tpsColumns(Worksheet $sheet): array
    {
        $columns = [];

        // Format Excel historis menaruh nama TPS di baris 13 mulai kolom E.
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

    private function recordFromSheet(Worksheet $sheet, string $column, string $kecamatan, string $desaNama, string $tpsNama, string $sourceFile): array
    {
        // Mapping baris mengikuti format rekap PPWP historis:
        // 14-15 DPT, 19-30 pengguna hak pilih, 33-36 surat suara,
        // 39-40 disabilitas, 45-47 suara paslon, 50-52 total suara.
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
            'suara' => [
                1 => $this->intCell($sheet, "{$column}45"),
                2 => $this->intCell($sheet, "{$column}46"),
                3 => $this->intCell($sheet, "{$column}47"),
            ],
            'suara_sah_excel' => $this->intCell($sheet, "{$column}50"),
            'suara_tidak_sah' => $this->intCell($sheet, "{$column}51"),
            'suara_total_excel' => $this->intCell($sheet, "{$column}52"),
        ];
    }

    private function normalizeRecord(array &$row, array &$corrections): void
    {
        // Normalisasi ini mengikuti pola importer historis lain di project:
        // kalau total Excel berbeda dari rincian, rincian yang konsisten dipakai
        // dan perubahan dicatat di daftar koreksi output command.
        $label = "{$row['kecamatan']} / {$row['desa']} / {$row['tps']}";
        $suaraSah = array_sum($row['suara']);
        $suaraTotal = $suaraSah + $row['suara_tidak_sah'];

        if ($row['suara_sah_excel'] !== $suaraSah) {
            $corrections[] = "{$label}: suara sah Excel {$row['suara_sah_excel']} disesuaikan ke jumlah paslon {$suaraSah}.";
        }

        if ($row['suara_total_excel'] !== $suaraTotal) {
            $newTidakSah = max(0, $row['suara_total_excel'] - $suaraSah);
            $corrections[] = "{$label}: total suara Excel {$row['suara_total_excel']} tidak sama dengan paslon+tidak sah {$suaraTotal}; suara tidak sah disesuaikan {$row['suara_tidak_sah']} -> {$newTidakSah}.";
            $row['suara_tidak_sah'] = $newTidakSah;
            $suaraTotal = $suaraSah + $row['suara_tidak_sah'];
        }

        $penggunaTotal = $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr']
            + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr']
            + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr'];

        if ($row['pengguna_total_excel'] !== $penggunaTotal) {
            $corrections[] = "{$label}: total pengguna Excel {$row['pengguna_total_excel']} tidak sama dengan rincian {$penggunaTotal}; rincian dipakai.";
        }

        if ($penggunaTotal !== $suaraTotal) {
            $diff = $suaraTotal - $penggunaTotal;
            $before = $row['pengguna_dpt_pr'];
            $row['pengguna_dpt_pr'] = max(0, $row['pengguna_dpt_pr'] + $diff);
            $corrections[] = "{$label}: pengguna hak pilih {$penggunaTotal} tidak sama dengan sah+tidak sah {$suaraTotal}; pengguna_dpt_pr disesuaikan {$before} -> {$row['pengguna_dpt_pr']}.";
        }

        if ($row['ss_digunakan'] !== $suaraTotal) {
            $corrections[] = "{$label}: surat suara digunakan {$row['ss_digunakan']} disesuaikan ke sah+tidak sah {$suaraTotal}.";
            $row['ss_digunakan'] = $suaraTotal;
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

    private function syncCalons(): array
    {
        $ids = [];

        foreach (self::PPWP_CALONS as $nomorUrut => $namaPaslon) {
            $calon = RekapPpwpCalon::updateOrCreate(
                ['nomor_urut' => $nomorUrut],
                ['nama_paslon' => $namaPaslon]
            );
            $ids[$nomorUrut] = $calon->id;
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

    private function printReport(array $rows, array $corrections, array $missing, array $invalid, array $warnings): void
    {
        $reportSummary = [
            'TPS terbaca' => count($rows),
            'File terbaca' => collect($rows)->pluck('source_file')->unique()->count(),
            'Kecamatan terbaca' => collect($rows)->pluck('kecamatan')->unique()->count(),
            'Desa terbaca' => collect($rows)->map(fn ($row) => $row['kecamatan'].' / '.$row['desa'])->unique()->count(),
            'TPS tidak aman diimpor' => count($invalid),
            'Koreksi data' => count($corrections),
        ];

        $this->newLine();
        $this->info('Import folder PPWP selesai.');
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

        $reportPath = $this->writeImportReport('Import folder PPWP', $reportSummary, [
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
