<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','ppk','pps','kpps','admin_partai','korcam','kordes','saksi_tps') NOT NULL");
        }

        DB::table('users')->where('role', 'admin')->update(['role' => 'admin_partai']);
        DB::table('users')->where('role', 'ppk')->update(['role' => 'korcam']);
        DB::table('users')->where('role', 'pps')->update(['role' => 'kordes']);
        DB::table('users')->where('role', 'kpps')->update(['role' => 'saksi_tps']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin_partai','korcam','kordes','saksi_tps') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','ppk','pps','kpps','admin_partai','korcam','kordes','saksi_tps') NOT NULL");
        }

        DB::table('users')->where('role', 'admin_partai')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'korcam')->update(['role' => 'ppk']);
        DB::table('users')->where('role', 'kordes')->update(['role' => 'pps']);
        DB::table('users')->where('role', 'saksi_tps')->update(['role' => 'kpps']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','ppk','pps','kpps') NOT NULL");
        }
    }
};
