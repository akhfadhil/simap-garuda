<?php
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Modul dokumen/verifikasi internal SIMAP utama tidak dipakai di SIMAP Garuda.
    }

    public function down(): void
    {
        //
    }
};
