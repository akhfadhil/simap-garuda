<?php

namespace App\Console\Commands;

use App\Models\RekapCaleg;
use App\Models\RekapPartai;
use Illuminate\Support\Facades\DB;

class ImportDprdKabFolder extends ImportDprRiFolder
{
    /*
     * CARA PAKAI IMPORTER DPRD KAB FOLDER
     * -------------------------------------------------------------------------
     * Aturan folder sama seperti importer DPR RI/DPRD Prov:
     *
     * - Satu folder berisi banyak file Excel.
     * - Satu file Excel mewakili satu kecamatan.
     * - Nama file mengikuti pola "DPRD KAB - NAMAKECAMATAN.xlsx".
     * - Di dalam satu file, tiap sheet mewakili satu desa.
     * - Nama sheet dipakai sebagai nama desa utama.
     * - Sheet pembuka seperti "DPRD KAB" dilewati otomatis kalau tidak punya TPS.
     *
     * Catatan khusus DPRD Kab:
     *
     * - Master partai/caleg dipisah per dapil kecamatan.
     * - Pastikan semua kecamatan yang diimport sudah punya dapil di menu setup.
     *
     * Wajib cek dulu tanpa mengubah database:
     *
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --dry-run
     *
     * Tulis detail masalah/koreksi ke file txt tanpa limit tampilan console:
     *
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --dry-run --report
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --dry-run --report=storage/app/import-reports/dprd-kab.txt
     *
     * Kalau --report tidak diberi path, file otomatis dibuat di:
     *
     *   storage/app/import-reports/import-folder-dprd-kab-YYYYMMDD-HHMMSS.txt
     *
     * Cek satu kecamatan saja:
     *
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --only=Bangorejo --dry-run
     *
     * Cek satu desa/sheet saja di dalam kecamatan:
     *
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --only=Bangorejo --desa=Sambirejo --dry-run
     *
     * Import asli setelah dry-run bersih:
     *
     *   php artisan import:dprd-kab-folder "storage/import/DPRD KAB"
     */
    protected $signature = 'import:dprd-kab-folder
        {path=storage/import/DPRD KAB : Folder berisi file DPRD KAB per kecamatan atau satu file Excel DPRD KAB}
        {--dry-run : Validasi dan tampilkan ringkasan tanpa menyimpan ke database}
        {--only=* : Batasi import ke nama kecamatan tertentu}
        {--desa=* : Batasi import ke nama desa/sheet tertentu}
        {--report= : Tulis detail masalah dan koreksi ke file txt; kosongkan nilainya untuk path otomatis}';

    protected $description = 'Import rekap DPRD Kabupaten dari folder Excel per kecamatan dan sheet per desa.';

    protected const JENIS = 'dprd_kab';

    protected const LABEL = 'DPRD KAB';

    protected const FILE_PREFIX_REGEX = '/^DPRD\s+KAB(?:UPATEN)?\s*-\s*/i';

    private array $dapilCache = [];

    protected function requiresDapil(): bool
    {
        return true;
    }

    protected function dapilIdForKecamatan(string $kecamatan): ?int
    {
        if (! array_key_exists($kecamatan, $this->dapilCache)) {
            $this->dapilCache[$kecamatan] = DB::table('kecamatans')
                ->whereRaw('LOWER(nama) = ?', [strtolower($kecamatan)])
                ->value('dapil_id');
        }

        return $this->dapilCache[$kecamatan] ? (int) $this->dapilCache[$kecamatan] : null;
    }

    protected function masterScopeKey(string $kecamatan, ?int $dapilId): string
    {
        return 'dapil:'.$dapilId;
    }

    protected function syncMaster(array $partaisByScope, array $rows = []): array
    {
        $masterIdsByScope = [];

        foreach ($partaisByScope as $scope => $partais) {
            $dapilId = (int) str_replace('dapil:', '', $scope);
            $partaiIds = [];
            $calegIds = [];

            foreach ($partais as $nomorPartai => $partaiData) {
                $partai = RekapPartai::updateOrCreate(
                    ['jenis' => static::JENIS, 'nomor_urut' => $nomorPartai, 'dapil_id' => $dapilId],
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
                ->where('dapil_id', $dapilId)
                ->whereNotIn('nomor_urut', array_keys($partais))
                ->delete();

            $masterIdsByScope[$scope] = ['partais' => $partaiIds, 'calegs' => $calegIds];
        }

        return $masterIdsByScope;
    }

    protected function masterIdsForRow(array $masterIds, array $row): array
    {
        return $masterIds[$row['master_scope']];
    }
}
