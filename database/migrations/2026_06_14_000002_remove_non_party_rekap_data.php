<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyTypes = ['ppwp', 'gubernur', 'bupati', 'dpd'];

        if (Schema::hasTable('rekap_headers')) {
            DB::table('rekap_headers')
                ->whereIn('jenis', $legacyTypes)
                ->delete();
        }

        if (Schema::hasTable('rekap_cell_flags')) {
            DB::table('rekap_cell_flags')
                ->whereIn('jenis', $legacyTypes)
                ->delete();
        }

        if (Schema::hasTable('pemilu_settings')) {
            DB::table('pemilu_settings')
                ->whereIn('jenis', $legacyTypes)
                ->update(['is_active' => false]);
        }

        foreach ([
            'rekap_ppwp_calons',
            'rekap_gubernur_calons',
            'rekap_bupati_calons',
            'rekap_dpd_calons',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function down(): void
    {
        // Data rekap non-legislatif tidak dipulihkan karena SIMAP Garuda hanya memantau DPR/DPRD.
    }
};
