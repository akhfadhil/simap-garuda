<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Str;

trait WritesImportReport
{
    private function writeImportReport(string $title, array $summary, array $sections): ?string
    {
        if (! $this->input->hasParameterOption('--report')) {
            return null;
        }

        $option = $this->option('report');

        if ($option === false) {
            return null;
        }

        $path = is_string($option) && trim($option) !== ''
            ? $this->resolveImportReportPath($option)
            : $this->defaultImportReportPath($title);

        $directory = dirname($path);
        try {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        } catch (\Throwable $exception) {
            $this->warn('Gagal membuat folder laporan import: '.$directory);
            $this->warn($exception->getMessage());

            return null;
        }

        $lines = [
            $title,
            'Dibuat: '.now()->format('Y-m-d H:i:s'),
            '',
            'Ringkasan:',
        ];

        foreach ($summary as $label => $value) {
            $lines[] = "- {$label}: {$value}";
        }

        foreach ($sections as $sectionTitle => $messages) {
            $lines[] = '';
            $lines[] = $sectionTitle.' ('.count($messages).'):';

            if ($messages === []) {
                $lines[] = '- Tidak ada.';

                continue;
            }

            foreach ($messages as $message) {
                $lines[] = '- '.$message;
            }
        }

        try {
            file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
        } catch (\Throwable $exception) {
            $this->warn('Gagal menulis laporan import: '.$path);
            $this->warn($exception->getMessage());

            return null;
        }

        return $path;
    }

    private function defaultImportReportPath(string $title): string
    {
        $name = Str::slug($title).'-'.now()->format('Ymd-His').'.txt';

        return storage_path('app/import-reports/'.$name);
    }

    private function resolveImportReportPath(string $path): string
    {
        $path = trim($path);

        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }
}
