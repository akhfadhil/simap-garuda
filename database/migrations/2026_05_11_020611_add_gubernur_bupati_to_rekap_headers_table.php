<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Ubah enum di rekap_headers
        DB::statement("ALTER TABLE rekap_headers MODIFY COLUMN jenis 
            ENUM('ppwp','gubernur','bupati','dpd','dpr_ri','dprd_prov','dprd_kab') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE rekap_headers MODIFY COLUMN jenis 
            ENUM('ppwp','dpd','dpr_ri','dprd_prov','dprd_kab') NOT NULL");
    }
};
