<?php

namespace App\Console\Commands;

use App\Models\Dokumen;
use App\Models\Desa;
use App\Models\PemiluSetting;
use App\Models\Tps;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedDummyDokumen extends Command
{
    protected $signature = 'seed:dummy-dokumen
        {--status=menunggu_verifikasi : Status dokumen dummy}
        {--desa-id=* : Batasi dokumen dummy hanya untuk desa tertentu}';

    protected $description = 'Isi dokumen TPS untuk semua jenis pemilihan aktif memakai salinan File_contoh.pdf.';

    public function handle(): int
    {
        $source = base_path('File_contoh.pdf');
        if (!is_file($source)) {
            $this->error('File sumber tidak ditemukan: ' . $source);
            return self::FAILURE;
        }

        $status = $this->option('status');
        if (!in_array($status, array_keys(Dokumen::STATUS_LABELS), true)) {
            $this->error('Status tidak valid. Gunakan: ' . implode(', ', array_keys(Dokumen::STATUS_LABELS)));
            return self::FAILURE;
        }

        $jenisAktif = collect(PemiluSetting::aktif())
            ->map(fn($jenis) => strtoupper($jenis))
            ->map(fn($jenis) => $jenis === 'DPR_RI' ? 'DPR_RI' : $jenis)
            ->values();

        if ($jenisAktif->isEmpty()) {
            $this->warn('Tidak ada jenis pemilihan aktif.');
            return self::SUCCESS;
        }

        $desaIds = collect($this->option('desa-id'))
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($desaIds->isNotEmpty()) {
            $desaList = Desa::with('kecamatan')
                ->whereIn('id', $desaIds)
                ->get();

            if ($desaList->count() !== $desaIds->count()) {
                $foundIds = $desaList->pluck('id');
                $missingIds = $desaIds->diff($foundIds)->implode(', ');
                $this->error('Desa tidak ditemukan: ' . $missingIds);
                return self::FAILURE;
            }

            $this->info('Scope desa: ' . $desaList
                ->map(fn($desa) => "{$desa->nama} ({$desa->kecamatan?->nama})")
                ->implode(', '));
        }

        $fallbackUploaderId = User::where('role', 'admin')->value('id') ?? User::value('id');
        if (!$fallbackUploaderId) {
            $this->error('Tidak ada user untuk kolom uploaded_by.');
            return self::FAILURE;
        }

        $uploaderByTps = User::where('role', 'kpps')
            ->whereNotNull('tps_id')
            ->pluck('id', 'tps_id');

        $fileSize = filesize($source);
        $created = 0;
        $updated = 0;

        $tpsQuery = Tps::with('desa.kecamatan')->orderBy('id');
        if ($desaIds->isNotEmpty()) {
            $tpsQuery->whereIn('desa_id', $desaIds);
        }

        $totalTps = (clone $tpsQuery)->count();
        if ($totalTps === 0) {
            $this->warn('Tidak ada TPS untuk scope yang dipilih.');
            return self::SUCCESS;
        }

        $total = $totalTps * $jenisAktif->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $tpsQuery->chunk(100, function ($tpsList) use ($jenisAktif, $source, $fileSize, $fallbackUploaderId, $uploaderByTps, $status, &$created, &$updated, $bar) {
            foreach ($tpsList as $tps) {
                $kecFolder = $this->slug($tps->desa?->kecamatan?->nama ?? 'kecamatan');
                $desaFolder = $this->slug($tps->desa?->nama ?? 'desa');
                $tpsFolder = $this->slug($tps->nama);
                $uploaderId = $uploaderByTps[$tps->id] ?? $fallbackUploaderId;

                foreach ($jenisAktif as $jenis) {
                    $path = "documents/{$kecFolder}/desa/{$desaFolder}/{$tpsFolder}/" . strtolower($jenis) . '.pdf';
                    $storagePath = Storage::path($path);
                    $dir = dirname($storagePath);

                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    copy($source, $storagePath);

                    $dokumen = Dokumen::updateOrCreate(
                        [
                            'tps_id' => $tps->id,
                            'jenis' => $jenis,
                        ],
                        [
                            'kecamatan_id' => null,
                            'uploaded_by' => $uploaderId,
                            'level' => 'tps',
                            'status' => $status,
                            'verified_by' => null,
                            'verified_at' => null,
                            'komentar' => null,
                            'is_archived' => false,
                            'archived_at' => null,
                            'file_path' => $path,
                            'file_name' => 'File_contoh.pdf',
                            'file_size' => $fileSize,
                        ]
                    );

                    $dokumen->wasRecentlyCreated ? $created++ : $updated++;
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Dibuat: {$created}, diperbarui: {$updated}, jenis aktif: " . $jenisAktif->implode(', '));

        return self::SUCCESS;
    }

    private function slug(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $value) ?: 'item';
    }
}
