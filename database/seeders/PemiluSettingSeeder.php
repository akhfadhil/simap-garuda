<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PemiluSettingSeeder extends Seeder
{
    public function run(): void
    {
        $jenis = \App\Models\RekapHeader::LEGISLATIVE_TYPES;

        foreach ($jenis as $j) {
            \App\Models\PemiluSetting::updateOrCreate(
                ['jenis' => $j],
                ['is_active' => true]
            );
        }
    }
}
