<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        
        // Ubah enum di dokumens
        DB::statement("ALTER TABLE dokumens MODIFY COLUMN jenis 
            ENUM('PPWP','GUBERNUR','BUPATI','DPD','DPR_RI','DPRD_PROV','DPRD_KAB') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE rekap_headers MODIFY COLUMN jenis 
            ENUM('ppwp','dpd','dpr_ri','dprd_prov','dprd_kab') NOT NULL");
        
        DB::statement("ALTER TABLE dokumens MODIFY COLUMN jenis 
            ENUM('PPWP','DPD','DPR_RI','DPRD_PROV','DPRD_KAB') NOT NULL");
    }
};
