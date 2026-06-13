<?php

namespace App\Console\Commands;

use App\Models\Dokumen;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDokumen extends Command
{
    protected $signature = 'backup:dokumen
                                {--days=0 : Arsipkan dokumen yang lebih tua dari N hari}
                                {--dry-run : Simulasi tanpa benar-benar memindahkan file}';

    protected $description = 'Pindahkan file PDF dokumen lama ke folder backup dan tandai sebagai diarsipkan.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $backupDir = config('filesystems.backup_path', storage_path('app/backup'));
        $cutoff = Carbon::now()->subDays($days);

        $this->info('======================================');
        $this->info('  SIMAP - Backup Dokumen');
        $this->info('======================================');
        $this->info("Cutoff   : dokumen sebelum {$cutoff->format('d/m/Y')}");
        $this->info("Backup ke: {$backupDir}");
        if ($dryRun) {
            $this->warn('MODE: DRY RUN - tidak ada file yang dipindah');
        }
        $this->newLine();

        $dokumens = Dokumen::where('is_archived', false)
            ->where('status', 'terverifikasi')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($dokumens->isEmpty()) {
            $this->info('Tidak ada dokumen terverifikasi yang perlu dibackup.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$dokumens->count()} dokumen untuk dibackup.");
        $this->newLine();

        $berhasil = 0;
        $gagal = 0;

        foreach ($dokumens as $dok) {
            $sourcePath = Storage::path($dok->file_path);
            $destPath = $backupDir . DIRECTORY_SEPARATOR . $dok->file_path;
            $destDir = dirname($destPath);
            $label = "[{$dok->id}] {$dok->file_name}";

            if (!Storage::exists($dok->file_path)) {
                $this->warn("  SKIP   {$label} - file sumber tidak ditemukan");
                if (!$dryRun) {
                    $dok->update(['is_archived' => true, 'archived_at' => now()]);
                }
                $gagal++;
                continue;
            }

            if ($dryRun) {
                $this->line("  DRY    {$label}");
                $this->line("         -> {$destPath}");
                $berhasil++;
                continue;
            }

            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (copy($sourcePath, $destPath)) {
                Storage::delete($dok->file_path);
                $dok->update([
                    'is_archived' => true,
                    'archived_at' => now(),
                ]);
                $this->line("  OK     {$label}");
                $berhasil++;
            } else {
                $this->error("  GAGAL  {$label} - gagal copy");
                $gagal++;
            }
        }

        $this->newLine();
        $this->info("Backup dokumen selesai. Berhasil: {$berhasil}, gagal: {$gagal}.");

        return self::SUCCESS;
    }
}
