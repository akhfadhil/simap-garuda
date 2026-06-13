<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WritesImportReport;
use App\Models\Desa;
use App\Models\RekapCaleg;
use App\Models\RekapCalegSuara;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Models\RekapPartaiSuara;
use App\Models\Tps;
use App\Services\RekapAdminCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDprRiFolder extends Command
{
    use WritesImportReport;

    /*
     * CARA PAKAI IMPORTER DPR RI FOLDER
     * -------------------------------------------------------------------------
     * Importer ini dibuat khusus untuk data historis DPR RI dari file Excel lama.
     * Pola file yang didukung:
     *
     * - Satu folder berisi banyak file Excel.
     * - Satu file Excel mewakili satu kecamatan.
     * - Nama file mengikuti pola "DPR RI - NAMAKECAMATAN.xlsx".
     * - Di dalam satu file, tiap sheet mewakili satu desa.
     * - Nama sheet dipakai sebagai nama desa utama.
     * - Sheet pembuka seperti "DPR RI" dilewati otomatis kalau tidak punya TPS.
     *
     * Wajib cek dulu tanpa mengubah database:
     *
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI" --dry-run
     *
     * Tulis detail masalah/koreksi ke file txt tanpa limit tampilan console:
     *
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI" --dry-run --report
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI" --dry-run --report=storage/app/import-reports/dpr-ri.txt
     *
     * Kalau --report tidak diberi path, file otomatis dibuat di:
     *
     *   storage/app/import-reports/import-folder-dpr-ri-YYYYMMDD-HHMMSS.txt
     *
     * Cek satu kecamatan saja:
     *
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI" --only=Bangorejo --dry-run
     *
     * Cek satu desa/sheet saja di dalam kecamatan:
     *
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI" --only=Bangorejo --desa=Sambirejo --dry-run
     *
     * Import asli setelah dry-run bersih:
     *
     *   php artisan import:dpr-ri-folder "storage/import/DPR RI"
     *
     * Hasil import:
     *
     * - Membuat TPS jika belum ada pada desa yang cocok.
     * - Menyinkronkan master partai dan caleg DPR RI dari blok partai/caleg.
     * - Menyimpan rekap_headers untuk jenis "dpr_ri".
     * - Menyimpan suara partai dan suara caleg.
     * - Status rekap diset "final" karena ini data historis.
     */
    protected $signature = 'import:dpr-ri-folder
        {path=storage/import/DPR RI : Folder berisi file DPR RI per kecamatan atau satu file Excel DPR RI}
        {--dry-run : Validasi dan tampilkan ringkasan tanpa menyimpan ke database}
        {--only=* : Batasi import ke nama kecamatan tertentu}
        {--desa=* : Batasi import ke nama desa/sheet tertentu}
        {--report= : Tulis detail masalah dan koreksi ke file txt; kosongkan nilainya untuk path otomatis}';

    protected $description = 'Import rekap DPR RI dari folder Excel per kecamatan dan sheet per desa.';

    protected const JENIS = 'dpr_ri';

    protected const LABEL = 'DPR RI';

    protected const FILE_PREFIX_REGEX = '/^DPR\s+RI\s*-\s*/i';

    protected const DESA_ALIASES = [
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
            $this->error('Tidak ada file Excel '.static::LABEL.' yang ditemukan di: '.$path);

            return self::FAILURE;
        }

        $rows = [];
        $corrections = [];
        $missing = [];
        $invalid = [];
        $warnings = [];
        $partaisByScope = [];

        foreach ($files as $file) {
            $kecamatan = $this->kecamatanFromFile($file);

            if (! $this->shouldImportKecamatan($kecamatan)) {
                continue;
            }

            $dapilId = $this->dapilIdForKecamatan($kecamatan);
            if ($this->requiresDapil() && ! $dapilId) {
                $missing[] = "{$kecamatan}: kecamatan belum punya dapil.";

                continue;
            }

            $masterScope = $this->masterScopeKey($kecamatan, $dapilId);

            $spreadsheet = IOFactory::load($file);
            $masterSheet = $this->firstDetailSheet($spreadsheet);

            if (! $masterSheet) {
                $invalid[] = basename($file).': sheet detail '.static::LABEL.' tidak ditemukan.';
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                continue;
            }

            $filePartais = $this->partaisFromSheet($masterSheet);

            if ($filePartais === []) {
                $invalid[] = basename($file).': data partai/caleg tidak terbaca.';
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                continue;
            }

            if (! isset($partaisByScope[$masterScope])) {
                $partaisByScope[$masterScope] = $filePartais;
            } elseif ($this->partaiSignature($partaisByScope[$masterScope]) !== $this->partaiSignature($filePartais)) {
                $invalid[] = basename($file).': daftar partai/caleg berbeda dari file lain dalam scope master yang sama.';
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

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
                        $filePartais,
                        $masterScope,
                        $dapilId
                    );

                    if ($this->recordLooksIncomplete($record)) {
                        $invalid[] = "{$record['source_file']} / {$record['desa']} / {$record['tps']}: pengguna ada, tetapi suara partai/caleg dan total suara kosong.";

                        continue;
                    }

                    if ($this->recordHasImpossibleTotals($record)) {
                        $invalid[] = "{$record['source_file']} / {$record['desa']} / {$record['tps']}: suara sah lebih besar dari surat suara digunakan/total suara.";

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
            $reportPath = $this->writeImportReport('Import folder '.static::LABEL, [
                'Scope master terbaca' => count($partaisByScope),
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
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $partaisByScope);

            return $missing === [] && $invalid === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($missing !== [] || $invalid !== []) {
            $this->error('Import dibatalkan karena masih ada data yang tidak aman untuk diimpor.');
            $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $partaisByScope);

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows, $partaisByScope) {
            $masterIds = $this->syncMaster($partaisByScope, $rows);

            foreach ($rows as $row) {
                $rowMasterIds = $this->masterIdsForRow($masterIds, $row);
                $desa = $this->findDesa($row['kecamatan'], $row['desa']);

                $tps = Tps::firstOrCreate([
                    'desa_id' => $desa->id,
                    'nama' => $row['tps'],
                ]);

                $rekap = RekapHeader::updateOrCreate(
                    ['tps_id' => $tps->id, 'jenis' => static::JENIS],
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

                foreach ($row['partai_suara'] as $nomorPartai => $suara) {
                    RekapPartaiSuara::updateOrCreate(
                        ['rekap_id' => $rekap->id, 'partai_id' => $rowMasterIds['partais'][$nomorPartai]],
                        ['suara' => $suara]
                    );
                }

                foreach ($row['caleg_suara'] as $nomorPartai => $calegSuaras) {
                    foreach ($calegSuaras as $nomorCaleg => $suara) {
                        RekapCalegSuara::updateOrCreate(
                            ['rekap_id' => $rekap->id, 'caleg_id' => $rowMasterIds['calegs'][$nomorPartai][$nomorCaleg]],
                            ['suara' => $suara]
                        );
                    }
                }

                RekapPartaiSuara::where('rekap_id', $rekap->id)
                    ->whereNotIn('partai_id', array_values($rowMasterIds['partais']))
                    ->delete();

                $calegIds = collect($rowMasterIds['calegs'])->flatten()->values()->all();
                RekapCalegSuara::where('rekap_id', $rekap->id)
                    ->whereNotIn('caleg_id', $calegIds)
                    ->delete();
            }
        });

        RekapAdminCache::flushAggregate();
        $this->printReport($rows, $corrections, $missing, $invalid, $warnings, $partaisByScope);

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
        $name = preg_replace(static::FILE_PREFIX_REGEX, '', $name);

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

    private function firstDetailSheet($spreadsheet): ?Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if ($this->tpsColumns($sheet) !== []) {
                return $sheet;
            }
        }

        return null;
    }

    private function partaisFromSheet(Worksheet $sheet): array
    {
        $partais = [];
        $currentPartai = null;

        for ($row = 43; $row <= 260; $row++) {
            $section = trim((string) $this->cell($sheet, "A{$row}"));
            $nomor = $this->intCell($sheet, "B{$row}");
            $name = trim((string) $this->cell($sheet, "C{$row}"));

            if ($section === 'A.1' && $nomor > 0 && $name !== '') {
                $currentPartai = $nomor;
                $partais[$currentPartai] = [
                    'row' => $row,
                    'nama' => $name,
                    'total_row' => null,
                    'calegs' => [],
                ];

                continue;
            }

            if ($section === 'B' && $currentPartai !== null) {
                $partais[$currentPartai]['total_row'] = $row;
                $currentPartai = null;

                continue;
            }

            if ($currentPartai !== null && $name !== '') {
                $nomorCaleg = $nomor > 0 ? $nomor : $this->candidateNumberFromName($name);
                if ($nomorCaleg <= 0) {
                    continue;
                }

                $partais[$currentPartai]['calegs'][$nomorCaleg] = [
                    'row' => $row,
                    'nama' => $this->cleanCandidateName($name),
                ];
            }
        }

        return $partais;
    }

    private function partaiSignature(array $partais): string
    {
        return md5(json_encode(collect($partais)->map(fn ($partai) => [
            'nama' => $partai['nama'],
            'calegs' => collect($partai['calegs'])->pluck('nama')->all(),
        ])->all()));
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

    private function recordFromSheet(Worksheet $sheet, string $column, string $kecamatan, string $desaNama, string $tpsNama, string $sourceFile, array $partais, string $masterScope, ?int $dapilId): array
    {
        $partaiSuara = [];
        $calegSuara = [];
        $partaiTotalsExcel = [];
        $suaraRows = $this->suaraRows($sheet);

        foreach ($partais as $nomorPartai => $partai) {
            $partaiSuara[$nomorPartai] = $this->intCell($sheet, $column.$partai['row']);
            $partaiTotalsExcel[$nomorPartai] = $partai['total_row'] ? $this->intCell($sheet, $column.$partai['total_row']) : null;
            foreach ($partai['calegs'] as $nomorCaleg => $caleg) {
                $calegSuara[$nomorPartai][$nomorCaleg] = $this->intCell($sheet, $column.$caleg['row']);
            }
        }

        return [
            'source_file' => $sourceFile,
            'master_scope' => $masterScope,
            'dapil_id' => $dapilId,
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
            'partai_suara' => $partaiSuara,
            'caleg_suara' => $calegSuara,
            'partai_totals_excel' => $partaiTotalsExcel,
            'suara_sah_excel' => $this->intCell($sheet, $column.$suaraRows['sah']),
            'suara_tidak_sah' => $this->intCell($sheet, $column.$suaraRows['tidak_sah']),
            'suara_total_excel' => $this->intCell($sheet, $column.$suaraRows['total']),
        ];
    }

    private function normalizeRecord(array &$row, array &$corrections): void
    {
        $label = "{$row['kecamatan']} / {$row['desa']} / {$row['tps']}";
        $suaraSah = $this->sumSah($row);

        foreach ($row['partai_suara'] as $nomorPartai => $suaraPartai) {
            $total = $suaraPartai + array_sum($row['caleg_suara'][$nomorPartai] ?? []);
            $excelTotal = $row['partai_totals_excel'][$nomorPartai] ?? null;
            if ($excelTotal !== null && $excelTotal !== $total) {
                $corrections[] = "{$label}: total partai {$nomorPartai} Excel {$excelTotal} tidak sama dengan partai+caleg {$total}; rincian dipakai.";
            }
        }

        if ($row['suara_sah_excel'] !== $suaraSah) {
            $corrections[] = "{$label}: suara sah Excel {$row['suara_sah_excel']} disesuaikan ke jumlah partai+caleg {$suaraSah}.";
        }

        $penggunaTotal = $this->sumPengguna($row);
        if ($row['pengguna_total_excel'] !== $penggunaTotal) {
            $before = $row['pengguna_dpt_pr'];
            $row['pengguna_dpt_pr'] = max(0, $row['pengguna_dpt_pr'] + ($row['pengguna_total_excel'] - $penggunaTotal));
            $corrections[] = "{$label}: total pengguna Excel {$row['pengguna_total_excel']} tidak sama dengan rincian {$penggunaTotal}; pengguna_dpt_pr disesuaikan {$before} -> {$row['pengguna_dpt_pr']}.";
            $penggunaTotal = $this->sumPengguna($row);
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
            $corrections[] = "{$label}: suara tidak sah {$row['suara_tidak_sah']} disesuaikan ke surat suara digunakan - suara sah {$expectedTidakSah}.";
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
        return $this->sumPengguna($row) > 0
            && $this->sumSah($row) === 0
            && $row['suara_total_excel'] === 0;
    }

    private function recordHasImpossibleTotals(array $row): bool
    {
        $authoritativeTotal = $row['ss_digunakan'] > 0 ? $row['ss_digunakan'] : $row['suara_total_excel'];

        return $authoritativeTotal > 0 && $this->sumSah($row) > $authoritativeTotal;
    }

    protected function syncMaster(array $partaisByScope, array $rows = []): array
    {
        $partais = $partaisByScope[$this->defaultMasterScopeKey()] ?? reset($partaisByScope) ?: [];
        $partaiIds = [];
        $calegIds = [];

        foreach ($partais as $nomorPartai => $partaiData) {
            $partai = RekapPartai::updateOrCreate(
                ['jenis' => static::JENIS, 'nomor_urut' => $nomorPartai, 'dapil_id' => null],
                ['nama_partai' => $partaiData['nama']]
            );
            $partaiIds[$nomorPartai] = $partai->id;
            $calegIds[$nomorPartai] = [];

            foreach ($partaiData['calegs'] as $nomorCaleg => $calegData) {
                $caleg = RekapCaleg::updateOrCreate(
                    ['partai_id' => $partai->id, 'nomor_urut' => $nomorCaleg],
                    ['nama_caleg' => $calegData['nama']]
                );
                $calegIds[$nomorPartai][$nomorCaleg] = $caleg->id;
            }

            RekapCaleg::where('partai_id', $partai->id)
                ->whereNotIn('nomor_urut', array_keys($partaiData['calegs']))
                ->delete();
        }

        RekapPartai::where('jenis', static::JENIS)
            ->whereNull('dapil_id')
            ->whereNotIn('nomor_urut', array_keys($partais))
            ->delete();

        return ['partais' => $partaiIds, 'calegs' => $calegIds];
    }

    protected function masterIdsForRow(array $masterIds, array $row): array
    {
        return $masterIds;
    }

    protected function requiresDapil(): bool
    {
        return false;
    }

    protected function dapilIdForKecamatan(string $kecamatan): ?int
    {
        return null;
    }

    protected function defaultMasterScopeKey(): string
    {
        return 'global';
    }

    protected function masterScopeKey(string $kecamatan, ?int $dapilId): string
    {
        return $this->defaultMasterScopeKey();
    }

    private function resolveDesaName(string $kecamatan, string $desa): string
    {
        return static::DESA_ALIASES[$kecamatan][$desa] ?? $desa;
    }

    private function findDesa(string $kecamatan, string $desa): ?Desa
    {
        return Desa::query()
            ->whereRaw('LOWER(nama) = ?', [strtolower($desa)])
            ->whereHas('kecamatan', fn ($query) => $query->whereRaw('LOWER(nama) = ?', [strtolower($kecamatan)]))
            ->first();
    }

    private function printReport(array $rows, array $corrections, array $missing, array $invalid, array $warnings, array $partaisByScope): void
    {
        $partaiCount = collect($partaisByScope)->sum(fn ($partais) => count($partais));
        $calegCount = collect($partaisByScope)
            ->sum(fn ($partais) => collect($partais)->sum(fn ($partai) => count($partai['calegs'])));
        $reportSummary = [
            'Scope master terbaca' => count($partaisByScope),
            'Partai terbaca' => $partaiCount,
            'Caleg terbaca' => $calegCount,
            'TPS terbaca' => count($rows),
            'File terbaca' => collect($rows)->pluck('source_file')->unique()->count(),
            'Kecamatan terbaca' => collect($rows)->pluck('kecamatan')->unique()->count(),
            'Desa terbaca' => collect($rows)->map(fn ($row) => $row['kecamatan'].' / '.$row['desa'])->unique()->count(),
            'TPS tidak aman diimpor' => count($invalid),
            'Koreksi data' => count($corrections),
        ];

        $this->newLine();
        $this->info('Import folder '.static::LABEL.' selesai.');
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
                'Pengguna' => $items->sum(fn ($row) => $this->sumPengguna($row)),
                'Sah' => $items->sum(fn ($row) => $this->sumSah($row)),
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

        $reportPath = $this->writeImportReport('Import folder '.static::LABEL, $reportSummary, [
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

    private function sumSah(array $row): int
    {
        return array_sum($row['partai_suara'])
            + collect($row['caleg_suara'])->sum(fn ($calegs) => array_sum($calegs));
    }

    private function sumPengguna(array $row): int
    {
        return $row['pengguna_dpt_lk'] + $row['pengguna_dpt_pr']
            + $row['pengguna_dptb_lk'] + $row['pengguna_dptb_pr']
            + $row['pengguna_dpk_lk'] + $row['pengguna_dpk_pr'];
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

    private function cleanCandidateName(string $name): string
    {
        return preg_replace('/^\s*\d+\.\s*/', '', $name) ?? $name;
    }

    private function candidateNumberFromName(string $name): int
    {
        if (preg_match('/^\s*(\d+)\./', $name, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function suaraRows(Worksheet $sheet): array
    {
        for ($row = 43; $row <= 320; $row++) {
            $label = strtoupper(trim((string) $this->cell($sheet, "C{$row}")));

            if (str_contains($label, 'JUMLAH SELURUH SUARA SAH')) {
                return [
                    'sah' => $row,
                    'tidak_sah' => $row + 1,
                    'total' => $row + 2,
                ];
            }
        }

        throw new \RuntimeException('Baris suara sah/tidak sah tidak ditemukan.');
    }
}
