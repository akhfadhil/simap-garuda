<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Dapil;
use Carbon\Carbon;

class PartaiSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $partai = [
            [1, 'PKB'],
            [2, 'GERINDRA'],
            [3, 'PDI P'],
            [4, 'GOLKAR'],
            [5, 'NASDEM'],
            [6, 'BURUH'],
            [7, 'GELORA'],
            [8, 'PKS'],
            [9, 'PKN'],
            [10, 'HANURA'],
            [11, 'GARUDA'],
            [12, 'PAN'],
            [13, 'PBB'],
            [14, 'DEMOKRAT'],
            [15, 'PSI'],
            [16, 'PERINDO'],
            [17, 'PPP'],
            [18, 'UMMAT'],
        ];

        // ── DPR RI & DPRD PROV: satu set, tanpa dapil ──
        foreach (['dpr_ri', 'dprd_prov'] as $jenis) {
            foreach ($partai as [$nomor, $nama]) {
                $exists = DB::table('rekap_partais')
                    ->where('jenis', $jenis)
                    ->where('nomor_urut', $nomor)
                    ->exists();
                if (!$exists) {
                    DB::table('rekap_partais')->insert([
                        'jenis'       => $jenis,
                        'nomor_urut'  => $nomor,
                        'nama_partai' => $nama,
                        'dapil_id'    => null,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        }

        // ── DPRD KAB: satu set per dapil ──
        $dapils = Dapil::all();

        if ($dapils->isEmpty()) {
            $this->command->warn('Tidak ada dapil ditemukan. Jalankan WilayahSeeder + tambah dapil dulu.');
            return;
        }

        foreach ($dapils as $dapil) {
            foreach ($partai as [$nomor, $nama]) {
                $exists = DB::table('rekap_partais')
                    ->where('jenis', 'dprd_kab')
                    ->where('dapil_id', $dapil->id)
                    ->where('nomor_urut', $nomor)
                    ->exists();
                if (!$exists) {
                    DB::table('rekap_partais')->insert([
                        'jenis'       => 'dprd_kab',
                        'nomor_urut'  => $nomor,
                        'nama_partai' => $nama,
                        'dapil_id'    => $dapil->id,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        }

        $this->command->info('Partai selesai di-seed: ' . count($partai) . ' partai × (dpr_ri + dprd_prov + ' . $dapils->count() . ' dapil).');
    }
}
