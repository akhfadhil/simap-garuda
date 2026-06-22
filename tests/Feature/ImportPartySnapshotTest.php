<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ImportPartySnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_if_file_does_not_exist(): void
    {
        $this->artisan('import:party-snapshot non-existent.json')
            ->assertFailed()
            ->expectsOutput("File snapshot tidak ditemukan di path: non-existent.json");
    }

    public function test_command_imports_snapshot_successfully(): void
    {
        // 1. Create a fake snapshot JSON
        $snapshot = [
            'exported_at' => now()->toIso8601String(),
            'source_app' => 'SIMAP Utama',
            'party_profile' => [
                'id' => 3,
                'slug' => 'pdi-p',
                'nama' => 'Partai Demokrasi Indonesia Perjuangan',
                'nama_singkat' => 'PDIP',
                'logo_path' => 'images/logo-pdip.png',
                'warna_utama' => '#DC2626',
                'warna_aksen' => '#991B1B',
                'nomor_urut_aktif' => 3,
                'nomor_urut_historis_json' => [2024 => 3],
            ],
            'dapils' => [
                ['id' => 1, 'nama' => 'Dapil 1']
            ],
            'kecamatans' => [
                ['id' => 1, 'nama' => 'Kecamatan A', 'dapil_id' => 1]
            ],
            'desas' => [
                ['id' => 1, 'kecamatan_id' => 1, 'nama' => 'Desa B']
            ],
            'tps' => [
                ['id' => 1, 'desa_id' => 1, 'nama' => 'TPS 01']
            ],
            'rekap_partais' => [
                ['id' => 1, 'jenis' => 'dpr_ri', 'nomor_urut' => 3, 'nama_partai' => 'PDIP', 'dapil_id' => null]
            ],
            'rekap_calegs' => [
                ['id' => 1, 'partai_id' => 1, 'nomor_urut' => 1, 'nama_caleg' => 'Megawati']
            ],
            'rekap_headers' => [
                [
                    'id' => 1,
                    'tps_id' => 1,
                    'jenis' => 'dpr_ri',
                    'status' => 'final',
                    'suara_tidak_sah' => 5,
                    'difinalisasi_at' => now()->toIso8601String()
                ]
            ],
            'rekap_partai_suaras' => [
                ['id' => 1, 'rekap_id' => 1, 'partai_id' => 1, 'suara' => 150]
            ],
            'rekap_caleg_suaras' => [
                ['id' => 1, 'rekap_id' => 1, 'caleg_id' => 1, 'suara' => 80]
            ]
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'snapshot');
        file_put_contents($tempFile, json_encode($snapshot));

        // Backup original .env content
        $originalEnvPath = base_path('.env');
        $originalEnvContent = null;
        $envMocked = false;
        if (file_exists($originalEnvPath)) {
            $originalEnvContent = file_get_contents($originalEnvPath);
        } else {
            file_put_contents($originalEnvPath, "PARTY_SLUG=\nPARTY_NAME=\n");
            $envMocked = true;
        }

        try {
            // Run command
            $this->artisan('import:party-snapshot', ['file' => $tempFile])
                ->assertSuccessful()
                ->expectsOutput("Membaca dan memproses file snapshot...")
                ->expectsOutput("Mengimpor data untuk partai: Partai Demokrasi Indonesia Perjuangan (PDIP)...")
                ->expectsOutput("✓ Impor snapshot selesai untuk PDIP!");

            // Assert database records
            $this->assertDatabaseHas('dapils', ['id' => 1, 'nama' => 'Dapil 1']);
            $this->assertDatabaseHas('kecamatans', ['id' => 1, 'nama' => 'Kecamatan A']);
            $this->assertDatabaseHas('desas', ['id' => 1, 'nama' => 'Desa B']);
            $this->assertDatabaseHas('tps', ['id' => 1, 'nama' => 'TPS 01']);
            $this->assertDatabaseHas('rekap_partais', ['id' => 1, 'nama_partai' => 'PDIP']);
            $this->assertDatabaseHas('rekap_calegs', ['id' => 1, 'nama_caleg' => 'Megawati']);
            $this->assertDatabaseHas('rekap_headers', ['id' => 1, 'jenis' => 'dpr_ri', 'status' => 'final']);
            $this->assertDatabaseHas('rekap_partai_suaras', ['rekap_id' => 1, 'partai_id' => 1, 'suara' => 150]);
            $this->assertDatabaseHas('rekap_caleg_suaras', ['rekap_id' => 1, 'caleg_id' => 1, 'suara' => 80]);
        } finally {
            // Clean up
            @unlink($tempFile);
            if ($originalEnvContent !== null) {
                file_put_contents($originalEnvPath, $originalEnvContent);
            } else {
                @unlink($originalEnvPath);
            }
        }
    }
}
