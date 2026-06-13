<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Backup dokumen otomatis setiap hari jam 01:00 dini hari
        // Arsipkan dokumen yang sudah lebih dari 30 hari
        $schedule->command('backup:dokumen')
                 ->dailyAt('01:00')
                 ->appendOutputTo(storage_path('logs/backup-dokumen.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
