<?php

namespace App\Http\Controllers\Rekap;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\RekapCellFlag;
use App\Models\RekapHeader;
use App\Models\Tps;
use Illuminate\Support\Facades\Auth;

class PpkController extends Controller
{
    // Menampilkan daftar rekap TPS dalam kecamatan PPK.
    public function index()
    {
        $kecamatan = $this->activeKecamatan();
        $tpsIds = Tps::whereHas('desa', fn ($q) => $q->where('kecamatan_id', $kecamatan->id))->pluck('id');
        $rekaps = RekapHeader::whereIn('tps_id', $tpsIds)->get()->groupBy('jenis');
        $desaIds = $kecamatan->desas()->pluck('id');
        $flaggedJenis = RekapCellFlag::query()
            ->where(function ($query) use ($tpsIds, $desaIds, $kecamatan) {
                $query->where(function ($query) use ($tpsIds) {
                    $query->where('level', 'tps')
                        ->whereIn('entity_id', $tpsIds);
                })->orWhere(function ($query) use ($desaIds) {
                    $query->where('level', 'desa')
                        ->whereIn('entity_id', $desaIds);
                })->orWhere(function ($query) use ($kecamatan) {
                    $query->where('level', 'kecamatan')
                        ->where('entity_id', $kecamatan->id);
                });
            })
            ->pluck('jenis')
            ->unique()
            ->flip();

        return view('rekap.ppk.index', compact('kecamatan', 'rekaps', 'flaggedJenis'));
    }

    // Memastikan jenis pemilihan sedang aktif.
    private function cekAktif(string $jenis): void
    {
        abort_if(! in_array($jenis, \App\Models\PemiluSetting::aktif()), 403, 'Jenis pemilu ini tidak aktif.');
    }

    // Menampilkan rekap per desa dan detail TPS.
    public function show(string $jenis)
    {
        $this->cekAktif($jenis);
        $kecamatan = $this->activeKecamatan();
        $showDetail = request()->boolean('detail');
        $detailDesaId = (int) request('detail_desa_id');
        $tpsIds = Tps::whereHas('desa', fn ($q) => $q->where('kecamatan_id', $kecamatan->id))->pluck('id');
        $relations = match ($jenis) {
            'ppwp' => ['tps.desa', 'ppwpSuaras.calon'],
            'gubernur' => ['tps.desa', 'gubernurSuaras.calon'],
            'bupati' => ['tps.desa', 'bupatiSuaras.calon'],
            'dpd' => ['tps.desa', 'dpdSuaras.calon'],
            default => ['tps.desa', 'partaiSuaras.partai', 'calegSuaras.caleg'],
        };
        $rekaps = RekapHeader::with($relations)
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get()->keyBy('tps_id');
        $desas = $kecamatan->desas()->with('tps')->get();
        $fieldNames = [
            'dpt_lk', 'dpt_pr',
            'pengguna_dpt_lk', 'pengguna_dpt_pr',
            'pengguna_dptb_lk', 'pengguna_dptb_pr',
            'pengguna_dpk_lk', 'pengguna_dpk_pr',
            'ss_diterima', 'ss_digunakan', 'ss_rusak', 'ss_sisa',
            'disabilitas_lk', 'disabilitas_pr',
            'suara_tidak_sah',
        ];
        $desaStats = [];
        $desaCalonTotals = [];
        $desaPartaiTotals = [];
        $desaCalegTotals = [];
        $desaPartaiGrandTotals = [];
        $tpsDesa = [];

        foreach ($desas as $desa) {
            $desaStats[$desa->id] = array_fill_keys($fieldNames, 0);
            $desaStats[$desa->id]['suara_sah'] = 0;
            $desaStats[$desa->id]['suara_total'] = 0;

            foreach ($desa->tps as $tps) {
                $tpsDesa[$tps->id] = $desa->id;
            }
        }

        foreach ($rekaps as $rekap) {
            $desaId = $tpsDesa[$rekap->tps_id] ?? null;
            if (! $desaId) {
                continue;
            }

            foreach ($fieldNames as $field) {
                $desaStats[$desaId][$field] += (int) ($rekap->{$field} ?? 0);
            }

            if (in_array($jenis, ['ppwp', 'gubernur', 'bupati', 'dpd'], true)) {
                $suaraRows = match ($jenis) {
                    'ppwp' => $rekap->ppwpSuaras,
                    'gubernur' => $rekap->gubernurSuaras,
                    'bupati' => $rekap->bupatiSuaras,
                    default => $rekap->dpdSuaras,
                };

                foreach ($suaraRows as $suara) {
                    $desaCalonTotals[$desaId][$suara->calon_id] =
                        ($desaCalonTotals[$desaId][$suara->calon_id] ?? 0) + (int) $suara->suara;
                    $desaStats[$desaId]['suara_sah'] += (int) $suara->suara;
                }
            } else {
                foreach ($rekap->partaiSuaras as $suara) {
                    $desaPartaiTotals[$desaId][$suara->partai_id] =
                        ($desaPartaiTotals[$desaId][$suara->partai_id] ?? 0) + (int) $suara->suara;
                    $desaPartaiGrandTotals[$desaId][$suara->partai_id] =
                        ($desaPartaiGrandTotals[$desaId][$suara->partai_id] ?? 0) + (int) $suara->suara;
                    $desaStats[$desaId]['suara_sah'] += (int) $suara->suara;
                }

                foreach ($rekap->calegSuaras as $suara) {
                    $partaiId = $suara->caleg?->partai_id;
                    $desaCalegTotals[$desaId][$suara->caleg_id] =
                        ($desaCalegTotals[$desaId][$suara->caleg_id] ?? 0) + (int) $suara->suara;

                    if ($partaiId) {
                        $desaPartaiGrandTotals[$desaId][$partaiId] =
                            ($desaPartaiGrandTotals[$desaId][$partaiId] ?? 0) + (int) $suara->suara;
                    }

                    $desaStats[$desaId]['suara_sah'] += (int) $suara->suara;
                }
            }
        }

        foreach ($desaStats as $desaId => $stats) {
            $desaStats[$desaId]['suara_total'] = $stats['suara_sah'] + $stats['suara_tidak_sah'];
        }

        $detailDesa = $desas->firstWhere('id', $detailDesaId);
        $detailDesas = $showDetail && $detailDesa ? collect([$detailDesa]) : collect();
        $detailRekaps = $showDetail
            ? $rekaps->whereIn('tps_id', $detailDesas->flatMap(fn ($desa) => $desa->tps->pluck('id'))->all())
            : collect();
        $desaIds = $desas->pluck('id');
        $allTpsIds = $desas->flatMap(fn ($desa) => $desa->tps->pluck('id'));
        $tpsDesa = [];
        foreach ($desas as $desa) {
            foreach ($desa->tps as $tps) {
                $tpsDesa[$tps->id] = $desa->id;
            }
        }
        $flagRows = RekapCellFlag::query()
            ->where('jenis', $jenis)
            ->where(function ($query) use ($allTpsIds, $desaIds, $kecamatan) {
                $query->where(function ($query) use ($allTpsIds) {
                    $query->where('level', 'tps')
                        ->whereIn('entity_id', $allTpsIds);
                })->orWhere(function ($query) use ($desaIds) {
                    $query->where('level', 'desa')
                        ->whereIn('entity_id', $desaIds);
                })->orWhere(function ($query) use ($kecamatan) {
                    $query->where('level', 'kecamatan')
                        ->where('entity_id', $kecamatan->id);
                });
            })
            ->get();
        $tpsCellFlags = collect();
        $cellFlags = collect();

        foreach ($flagRows as $flag) {
            if ($flag->level === 'tps') {
                $tpsCellFlags->put($flag->entity_id.':'.$flag->row_key, true);

                $desaId = $tpsDesa[$flag->entity_id] ?? null;
                if ($desaId) {
                    $cellFlags->put($desaId.':'.$flag->row_key, true);
                }

                continue;
            }

            if ($flag->level === 'desa') {
                $cellFlags->put($flag->entity_id.':'.$flag->row_key, true);
            }

            if ($flag->level === 'kecamatan') {
                foreach ($desaIds as $desaId) {
                    $cellFlags->put($desaId.':'.$flag->row_key, true);
                }
            }
        }
        $master = $this->getMaster($jenis, $kecamatan);

        return view('rekap.ppk.show', compact(
            'kecamatan',
            'jenis',
            'rekaps',
            'detailRekaps',
            'desas',
            'detailDesas',
            'detailDesaId',
            'master',
            'desaStats',
            'desaCalonTotals',
            'desaPartaiTotals',
            'desaCalegTotals',
            'desaPartaiGrandTotals',
            'tpsCellFlags',
            'cellFlags'
        ));
    }

    // Mengekspor rekap kecamatan untuk jenis pemilihan.
    public function export(string $jenis)
    {
        $this->cekAktif($jenis);
        $kecamatan = $this->activeKecamatan();
        $desas = $kecamatan->desas()->with('tps')->get();
        $tpsIds = $desas->flatMap(fn ($d) => $d->tps->pluck('id'));

        $rekaps = RekapHeader::with(['ppwpSuaras', 'gubernurSuaras', 'bupatiSuaras', 'dpdSuaras', 'partaiSuaras', 'calegSuaras'])
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get();

        $tpsList = $desas->flatMap(fn ($d) => $d->tps)->values();
        $master = $this->getAllMaster($kecamatan);
        $label = \App\Models\RekapHeader::JENIS_LABELS[$jenis];
        $wilayah = 'Kec. '.$kecamatan->nama;
        $filename = 'Rekap_'.strtoupper($jenis).'_PPK_'.str_replace(' ', '_', $kecamatan->nama).'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RekapExport($rekaps, $master, $tpsList, 'ppk', $wilayah, $desas, $jenis),
            $filename
        );
    }

    // Mengambil master data sesuai jenis pemilihan.
    private function getMaster(string $jenis, Kecamatan $kecamatan): array
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
        $partais = \App\Models\RekapPartai::with('calegs')->where('jenis', $jenis);

        if ($jenis === 'dprd_kab') {
            $partais->where('dapil_id', $kecamatan->dapil_id);
        }

        return ['partais' => $partais->orderBy('nomor_urut')->get()];
    }

    // Mengambil semua master data untuk kebutuhan export.
    private function getAllMaster(Kecamatan $kecamatan): array
    {
        return [
            'ppwp' => ['calons' => \App\Models\RekapPpwpCalon::orderBy('nomor_urut')->get()],
            'gubernur' => ['calons' => \App\Models\RekapGubernurCalon::orderBy('nomor_urut')->get()],
            'bupati' => ['calons' => \App\Models\RekapBupatiCalon::orderBy('nomor_urut')->get()],
            'dpd' => ['calons' => \App\Models\RekapDpdCalon::orderBy('nomor_urut')->get()],
            'dpr_ri' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->where('dapil_id', $kecamatan->dapil_id)->orderBy('nomor_urut')->get()],
        ];
    }

    private function activeKecamatan(): Kecamatan
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            abort_if(! session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');

            return Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        }

        abort_if(! $user->kecamatan_id, 403, 'Akun belum di-assign ke Kecamatan.');

        return Kecamatan::findOrFail($user->kecamatan_id);
    }
}
