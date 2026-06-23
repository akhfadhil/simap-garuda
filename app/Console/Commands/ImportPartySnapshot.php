<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportPartySnapshot extends Command
{
    protected $signature = 'import:party-snapshot {file : Jalur ke file JSON snapshot}';
    protected $description = 'Mengimpor data wilayah, master partai, caleg, dan perolehan suara dari file JSON snapshot.';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("File snapshot tidak ditemukan di path: {$filePath}");
            return 1;
        }

        $this->info("Membaca dan memproses file snapshot...");
        $json = File::get($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Format JSON tidak valid: " . json_last_error_msg());
            return 1;
        }

        // Validasi struktur snapshot dasar
        $requiredKeys = ['party_profile', 'dapils', 'kecamatans', 'desas', 'tps', 'rekap_partais', 'rekap_calegs'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                $this->error("Kunci data '{$key}' tidak ditemukan dalam berkas snapshot.");
                return 1;
            }
        }

        $profile = $data['party_profile'];
        $this->info("Mengimpor data untuk partai: {$profile['nama']} ({$profile['nama_singkat']})...");

        try {
            Schema::disableForeignKeyConstraints();

            // Truncate tables
            DB::table('rekap_caleg_suaras')->truncate();
            DB::table('rekap_partai_suaras')->truncate();
            DB::table('rekap_headers')->truncate();
            DB::table('rekap_calegs')->truncate();
            DB::table('rekap_partais')->truncate();
            DB::table('tps')->truncate();
            DB::table('desas')->truncate();
            DB::table('kecamatans')->truncate();
            DB::table('dapils')->truncate();

            DB::beginTransaction();

            // 1. Dapils
            $dapils = collect($data['dapils'])->map(fn($item) => [
                'id' => $item['id'],
                'nama' => $item['nama'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $dapils->chunk(1000)->each(fn($chunk) => DB::table('dapils')->insert($chunk->toArray()));

            // 2. Kecamatans
            $seenKecamatans = [];
            $kecamatans = collect($data['kecamatans'])
                ->filter(function ($item) use (&$seenKecamatans) {
                    if (in_array($item['nama'], $seenKecamatans, true)) {
                        return false;
                    }
                    $seenKecamatans[] = $item['nama'];
                    return true;
                })
                ->map(fn($item) => [
                    'id' => $item['id'],
                    'nama' => $item['nama'],
                    'dapil_id' => $item['dapil_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $kecamatans->chunk(1000)->each(fn($chunk) => DB::table('kecamatans')->insert($chunk->toArray()));

            // 3. Desas
            $seenDesas = [];
            $desas = collect($data['desas'])
                ->filter(function ($item) use (&$seenDesas) {
                    $key = $item['kecamatan_id'] . '_' . $item['nama'];
                    if (in_array($key, $seenDesas, true)) {
                        return false;
                    }
                    $seenDesas[] = $key;
                    return true;
                })
                ->map(fn($item) => [
                    'id' => $item['id'],
                    'kecamatan_id' => $item['kecamatan_id'],
                    'nama' => $item['nama'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $desas->chunk(1000)->each(fn($chunk) => DB::table('desas')->insert($chunk->toArray()));

            // 4. Tps
            $seenTps = [];
            $tps = collect($data['tps'])
                ->filter(function ($item) use (&$seenTps) {
                    $key = $item['desa_id'] . '_' . $item['nama'];
                    if (in_array($key, $seenTps, true)) {
                        return false;
                    }
                    $seenTps[] = $key;
                    return true;
                })
                ->map(fn($item) => [
                    'id' => $item['id'],
                    'desa_id' => $item['desa_id'],
                    'nama' => $item['nama'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $tps->chunk(1000)->each(fn($chunk) => DB::table('tps')->insert($chunk->toArray()));

            // 5. Rekap Partais
            $rekapPartais = collect($data['rekap_partais'])->map(fn($item) => [
                'id' => $item['id'],
                'jenis' => $item['jenis'],
                'nomor_urut' => $item['nomor_urut'],
                'nama_partai' => $item['nama_partai'],
                'dapil_id' => $item['dapil_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $rekapPartais->chunk(1000)->each(fn($chunk) => DB::table('rekap_partais')->insert($chunk->toArray()));

            // 6. Rekap Calegs
            $rekapCalegs = collect($data['rekap_calegs'])->map(fn($item) => [
                'id' => $item['id'],
                'partai_id' => $item['partai_id'],
                'nomor_urut' => $item['nomor_urut'],
                'nama_caleg' => $item['nama_caleg'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $rekapCalegs->chunk(1000)->each(fn($chunk) => DB::table('rekap_calegs')->insert($chunk->toArray()));

            // 7. Rekap Headers
            if (isset($data['rekap_headers'])) {
                $nowStr = now()->toDateTimeString();
                $chunks = array_chunk($data['rekap_headers'], 1000);
                foreach ($chunks as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $item) {
                        $insertData[] = [
                            'id' => $item['id'],
                            'tps_id' => $item['tps_id'],
                            'jenis' => $item['jenis'],
                            'status' => $item['status'] ?? 'draft',
                            'catatan_internal' => $item['catatan_internal'] ?? null,
                            'dpt_lk' => $item['dpt_lk'] ?? 0,
                            'dpt_pr' => $item['dpt_pr'] ?? 0,
                            'pengguna_dpt_lk' => $item['pengguna_dpt_lk'] ?? 0,
                            'pengguna_dpt_pr' => $item['pengguna_dpt_pr'] ?? 0,
                            'pengguna_dptb_lk' => $item['pengguna_dptb_lk'] ?? 0,
                            'pengguna_dptb_pr' => $item['pengguna_dptb_pr'] ?? 0,
                            'pengguna_dpk_lk' => $item['pengguna_dpk_lk'] ?? 0,
                            'pengguna_dpk_pr' => $item['pengguna_dpk_pr'] ?? 0,
                            'ss_diterima' => $item['ss_diterima'] ?? 0,
                            'ss_digunakan' => $item['ss_digunakan'] ?? 0,
                            'ss_rusak' => $item['ss_rusak'] ?? 0,
                            'ss_sisa' => $item['ss_sisa'] ?? 0,
                            'disabilitas_lk' => $item['disabilitas_lk'] ?? 0,
                            'disabilitas_pr' => $item['disabilitas_pr'] ?? 0,
                            'suara_sah' => $item['suara_sah'] ?? 0,
                            'suara_tidak_sah' => $item['suara_tidak_sah'] ?? 0,
                            'diinput_oleh' => $item['diinput_oleh'] ?? null,
                            'difinalisasi_at' => isset($item['difinalisasi_at']) ? \Illuminate\Support\Carbon::parse($item['difinalisasi_at'])->toDateTimeString() : null,
                            'created_at' => $nowStr,
                            'updated_at' => $nowStr,
                        ];
                    }
                    DB::table('rekap_headers')->insert($insertData);
                }
            }

            // 8. Rekap Partai Suaras
            if (isset($data['rekap_partai_suaras'])) {
                $nowStr = now()->toDateTimeString();
                $chunks = array_chunk($data['rekap_partai_suaras'], 1000);
                foreach ($chunks as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $item) {
                        $insertData[] = [
                            'id' => $item['id'],
                            'rekap_id' => $item['rekap_id'],
                            'partai_id' => $item['partai_id'],
                            'suara' => $item['suara'] ?? 0,
                            'created_at' => $nowStr,
                            'updated_at' => $nowStr,
                        ];
                    }
                    DB::table('rekap_partai_suaras')->insert($insertData);
                }
            }

            // 9. Rekap Caleg Suaras
            if (isset($data['rekap_caleg_suaras'])) {
                $nowStr = now()->toDateTimeString();
                $chunks = array_chunk($data['rekap_caleg_suaras'], 1000);
                foreach ($chunks as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $item) {
                        $insertData[] = [
                            'id' => $item['id'],
                            'rekap_id' => $item['rekap_id'],
                            'caleg_id' => $item['caleg_id'],
                            'suara' => $item['suara'] ?? 0,
                            'created_at' => $nowStr,
                            'updated_at' => $nowStr,
                        ];
                    }
                    DB::table('rekap_caleg_suaras')->insert($insertData);
                }
            }

            Schema::enableForeignKeyConstraints();
            DB::commit();
        } catch (\Exception $e) {
            $this->error("Original Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            try {
                DB::rollBack();
            } catch (\Exception $rollbackEx) {
                // Ignore rollback exception
            }
            Schema::enableForeignKeyConstraints();
            $this->error("Terjadi kesalahan saat mengimpor data: " . $e->getMessage());
            return 1;
        }

        // Tulis konfigurasi baru ke .env
        $this->updateEnvFile($profile);

        $this->info("✓ Impor snapshot selesai untuk {$profile['nama_singkat']}!");
        $this->line("Identitas partai telah diperbarui di file .env.");
        return 0;
    }

    private function updateEnvFile(array $profile): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        $updates = [
            'PARTY_SLUG' => $profile['slug'],
            'PARTY_NAME' => '"' . $profile['nama'] . '"',
            'PARTY_SHORT_NAME' => $profile['nama_singkat'],
            'PARTY_COLOR_PRIMARY' => '"' . ($profile['warna_utama'] ?? '#3B82F6') . '"',
            'PARTY_COLOR_PRIMARY_DARK' => '"' . ($profile['warna_aksen'] ?? '#1D4ED8') . '"',
            'PARTY_LOGO' => $profile['logo_path'] ?? 'images/party-logo.png',
        ];

        foreach ($updates as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }
}