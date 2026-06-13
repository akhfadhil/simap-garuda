<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WilayahSeeder::class,
            PartaiSeeder::class,
            PemiluSettingSeeder::class,
        ]);
    }
}

// php artisan migrate:fresh --seed

// php artisan migrate:fresh
// php artisan db:seed

// * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1  # cron job di server

// php artisan db:seed --class=PartaiSeeder  # seeder partai untuk dapil
// php artisan backup:dokumen  # command untuk backup
// BACKUP_DOKUMEN_PATH=E:\Backup\SIMAP  # ubah ini di .env jika ingin ubah path backup
