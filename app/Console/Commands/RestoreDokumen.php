<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Dokumen;

class RestoreDokumen extends Command
{
    protected $signature   = 'restore:dokumen {id : ID dokumen yang ingin di-restore}';
    protected $description = 'Pindahkan kembali file PDF dari backup ke storage Laravel.';

    public function handle(): int
    {
        $id  = $this->argument('id');
        $dok = Dokumen::find($id);

        if (!$dok) {
            $this->error("Dokumen ID {$id} tidak ditemukan.");
            return 1;
        }

        if (!$dok->is_archived) {
            $this->warn("Dokumen ini tidak dalam status diarsipkan.");
            return 0;
        }

        $backupDir  = config('filesystems.backup_path', storage_path('app/backup'));
        // $archivedAt = $dok->archived_at ?? $dok->created_at;
        // $subDir     = $archivedAt->format('Y') . DIRECTORY_SEPARATOR . $archivedAt->format('m');
        // $backupPath = $backupDir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . basename($dok->file_path);
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $dokumen->file_path;

        if (!file_exists($backupPath)) {
            $this->error("File backup tidak ditemukan di: {$backupPath}");
            return 1;
        }

        // Pastikan folder storage tujuan ada
        $storageDir = dirname(Storage::path($dok->file_path));
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        if (copy($backupPath, Storage::path($dok->file_path))) {
            unlink($backupPath);
            $dok->update(['is_archived' => false, 'archived_at' => null]);
            $this->info("✓ Dokumen [{$dok->id}] {$dok->file_name} berhasil di-restore.");
            return 0;
        }

        $this->error("Gagal memindahkan file dari backup.");
        return 1;
    }
}
