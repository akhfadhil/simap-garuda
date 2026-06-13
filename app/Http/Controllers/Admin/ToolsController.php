<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ToolsController extends Controller
{
    // Menjalankan backup dokumen terverifikasi lewat Artisan.
    public function backup()
    {
        $exit = Artisan::call('backup:dokumen');
        $output = trim(Artisan::output());
        $lines = collect(preg_split('/\r\n|\r|\n/', $output))
            ->map(fn($line) => trim($line))
            ->filter();
        $summary = $lines->first(fn($line) => str_contains($line, 'Backup dokumen selesai.'))
            ?? $lines->first(fn($line) => str_contains($line, 'Tidak ada dokumen terverifikasi'))
            ?? ($exit === 0 ? 'Backup dokumen selesai.' : 'Backup dokumen gagal dijalankan.');

        return back()->with('backup_result', $summary);
    }

    // Menjalankan ulang seeder partai dari halaman setup.
    public function seedPartai()
    {
        Artisan::call('db:seed', ['--class' => 'PartaiSeeder', '--force' => true]);
        $output = trim(Artisan::output());

        return back()->with('seed_result', 'OK ' . $output);
    }
}
