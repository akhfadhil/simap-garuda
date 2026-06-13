<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'partai_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('partai_id')->nullable()->after('tps_id');
            });
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','komisioner','partai','ppk','pps','kpps') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','komisioner','ppk','pps','kpps') NOT NULL");
        }

        if (Schema::hasColumn('users', 'partai_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('partai_id');
            });
        }
    }
};
