<?php

namespace App\Services;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\RekapHeader;
use App\Models\Tps;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RekapExportService
{
    // Dipanggil setiap kali satu jenis rekap TPS difinalisasi
    public function handleFinalisasi(Tps $tps, string $jenis): void
    {
        // 1. Export rekap TPS jenis ini
        $this->exportTps($tps, $jenis);

        // 2. Cek apakah semua jenis aktif di desa ini sudah final
        $desa = $tps->desa;
        if ($this->isDesaFinal($desa)) {
            $this->exportDesa($desa);
        }

        // 3. Cek apakah semua jenis aktif di kecamatan ini sudah final
        $kecamatan = $desa->kecamatan;
        if ($this->isKecamatanFinal($kecamatan)) {
            $this->exportKecamatan($kecamatan);
        }
    }

    // Cek semua TPS di desa sudah final untuk semua jenis aktif.
    private function isDesaFinal(Desa $desa): bool
    {
        $tpsIds = $desa->tps->pluck('id');
        $jenisAktif = \App\Models\PemiluSetting::aktif();
        $required = $tpsIds->count() * count($jenisAktif);
        if ($required === 0) {
            return false;
        }

        $finalCount = RekapHeader::whereIn('tps_id', $tpsIds)
            ->whereIn('jenis', $jenisAktif)
            ->where('status', 'final')
            ->count();

        return $finalCount >= $required;
    }

    // Cek semua TPS di kecamatan sudah final untuk semua jenis aktif.
    private function isKecamatanFinal(Kecamatan $kecamatan): bool
    {
        $tpsIds = $kecamatan->desas->flatMap(fn ($d) => $d->tps->pluck('id'));
        $jenisAktif = \App\Models\PemiluSetting::aktif();
        $required = $tpsIds->count() * count($jenisAktif);
        if ($required === 0) {
            return false;
        }

        $finalCount = RekapHeader::whereIn('tps_id', $tpsIds)
            ->whereIn('jenis', $jenisAktif)
            ->where('status', 'final')
            ->count();

        return $finalCount >= $required;
    }

    // ── Export level TPS ──
    private function exportTps(Tps $tps, string $jenis): void
    {
        $tps->load('desa.kecamatan');

        $kecFolder = $this->slug($tps->desa->kecamatan->nama);
        $desaFolder = $this->slug($tps->desa->nama);
        $tpsSlug = $this->slug($tps->nama);
        $dir = "rekap_exports/{$kecFolder}/{$desaFolder}";

        $version = $this->nextVersion($dir, "{$tpsSlug}_{$jenis}");
        $filename = "{$tpsSlug}_{$jenis}_{$version}.xlsx";
        $path = "{$dir}/{$filename}";

        $rekaps = RekapHeader::with(['ppwpSuaras', 'gubernurSuaras', 'bupatiSuaras', 'dpdSuaras', 'partaiSuaras', 'calegSuaras'])
            ->where('tps_id', $tps->id)
            ->where('jenis', $jenis)
            ->get();
        $master = $this->getMaster($jenis);
        $tpsList = collect([$tps]);
        $label = RekapHeader::JENIS_LABELS[$jenis];
        $wilayah = $tps->nama.' — '.$tps->desa->nama;

        $sheet = new \App\Exports\RekapSheetExport(
            $jenis, $label, $rekaps, $master, $tpsList, 'kpps', $wilayah
        );

        Excel::store($sheet, $path);
    }

    // ── Export level Desa ──
    private function exportDesa(Desa $desa): void
    {
        $desa->load('tps', 'kecamatan');

        $kecFolder = $this->slug($desa->kecamatan->nama);
        $desaFolder = $this->slug($desa->nama);
        $dir = "rekap_exports/{$kecFolder}";

        $version = $this->nextVersion($dir, "Rekap_Desa_{$desaFolder}");
        $filename = "Rekap_Desa_{$desaFolder}_{$version}.xlsx";
        $path = "{$dir}/{$filename}";

        $tpsIds = $desa->tps->pluck('id');
        $rekaps = RekapHeader::with(['ppwpSuaras', 'gubernurSuaras', 'bupatiSuaras', 'dpdSuaras', 'partaiSuaras', 'calegSuaras'])
            ->whereIn('tps_id', $tpsIds)
            ->get();
        $master = $this->getAllMaster();
        $wilayah = $desa->nama.' — Kec. '.$desa->kecamatan->nama;

        Excel::store(
            new \App\Exports\RekapExport($rekaps, $master, $desa->tps, 'pps', $wilayah, collect([$desa]), null),
            $path
        );
    }

    // ── Export level Kecamatan ──
    private function exportKecamatan(Kecamatan $kecamatan): void
    {
        $kecamatan->load('desas.tps');

        $kecFolder = $this->slug($kecamatan->nama);
        $dir = "rekap_exports/{$kecFolder}";

        $version = $this->nextVersion($dir, "Rekap_Kec_{$kecFolder}");
        $filename = "Rekap_Kec_{$kecFolder}_{$version}.xlsx";
        $path = "{$dir}/{$filename}";

        $desas = $kecamatan->desas;
        $tpsIds = $desas->flatMap(fn ($d) => $d->tps->pluck('id'));
        $tpsList = $desas->flatMap(fn ($d) => $d->tps)->values();
        $rekaps = RekapHeader::with(['ppwpSuaras', 'gubernurSuaras', 'bupatiSuaras', 'dpdSuaras', 'partaiSuaras', 'calegSuaras'])
            ->whereIn('tps_id', $tpsIds)
            ->get();
        $master = $this->getAllMaster();
        $wilayah = 'Kec. '.$kecamatan->nama;

        Excel::store(
            new \App\Exports\RekapExport($rekaps, $master, $tpsList, 'ppk', $wilayah, $desas, null),
            $path
        );
    }

    // ── Helper: cari versi berikutnya ──
    private function nextVersion(string $dir, string $prefix): string
    {
        $files = Storage::files($dir);
        $pattern = strtolower($prefix);
        $max = 0;

        foreach ($files as $file) {
            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            if (str_starts_with($base, strtolower($pattern))) {
                if (preg_match('/_v(\d+)$/', $base, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        }

        return 'v'.str_pad($max + 1, 2, '0', STR_PAD_LEFT);
    }

    // ── Helper: nama folder aman ──
    private function slug(string $nama): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama);
    }

    private function getMaster(string $jenis): array
    {
        if ($jenis === 'ppwp') {
            return ['calons' => \App\Models\RekapPpwpCalon::orderBy('nomor_urut')->get()];
        }
        if ($jenis === 'gubernur') {
            return ['calons' => \App\Models\RekapGubernurCalon::orderBy('nomor_urut')->get()];
        }
        if ($jenis === 'bupati') {
            return ['calons' => \App\Models\RekapBupatiCalon::orderBy('nomor_urut')->get()];
        }
        if ($jenis === 'dpd') {
            return ['calons' => \App\Models\RekapDpdCalon::orderBy('nomor_urut')->get()];
        }

        return ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', $jenis)->garuda()->orderBy('nomor_urut')->get()];
    }

    private function getAllMaster(): array
    {
        return [
            'ppwp' => ['calons' => \App\Models\RekapPpwpCalon::orderBy('nomor_urut')->get()],
            'gubernur' => ['calons' => \App\Models\RekapGubernurCalon::orderBy('nomor_urut')->get()],
            'bupati' => ['calons' => \App\Models\RekapBupatiCalon::orderBy('nomor_urut')->get()],
            'dpd' => ['calons' => \App\Models\RekapDpdCalon::orderBy('nomor_urut')->get()],
            'dpr_ri' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->garuda()->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->garuda()->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->garuda()->orderBy('nomor_urut')->get()],
        ];
    }
}
