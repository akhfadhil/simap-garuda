<?php

namespace App\Http\Controllers\Rekap;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\RekapCellFlag;
use App\Models\RekapHeader;
use Illuminate\Support\Facades\Auth;

class PpsController extends Controller
{
    // Menampilkan daftar rekap TPS dalam desa Kordes.
    public function index()
    {
        $desa = $this->activeDesa();
        $tpsIds = $desa->tps->pluck('id');
        $rekaps = RekapHeader::whereIn('tps_id', $tpsIds)->get()
            ->groupBy('jenis');
        $flaggedJenis = RekapCellFlag::query()
            ->where(function ($query) use ($tpsIds, $desa) {
                $query->where(function ($query) use ($tpsIds) {
                    $query->where('level', 'tps')
                        ->whereIn('entity_id', $tpsIds);
                })->orWhere(function ($query) use ($desa) {
                    $query->where('level', 'desa')
                        ->where('entity_id', $desa->id);
                })->orWhere(function ($query) use ($desa) {
                    $query->where('level', 'kecamatan')
                        ->where('entity_id', $desa->kecamatan_id);
                });
            })
            ->pluck('jenis')
            ->unique()
            ->flip();

        return view('rekap.pps.index', compact('desa', 'rekaps', 'flaggedJenis'));
    }

    // Memastikan jenis pemilihan sedang aktif.
    private function cekAktif(string $jenis): void
    {
        abort_unless(in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true), 404);
        abort_if(! in_array($jenis, \App\Models\PemiluSetting::aktif()), 403, 'Jenis pemilu ini tidak aktif.');
    }

    // Menampilkan rekap per TPS untuk jenis pemilihan.
    public function show(string $jenis)
    {
        $this->cekAktif($jenis);
        $desa = $this->activeDesa();
        $tpsIds = $desa->tps->pluck('id');
        $relations = ['tps', 'partaiSuaras.partai', 'calegSuaras.caleg'];
        $rekaps = RekapHeader::with($relations)
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get()->keyBy('tps_id');
        $tpsList = $desa->tps;
        $flagRows = RekapCellFlag::query()
            ->where('jenis', $jenis)
            ->where(function ($query) use ($tpsIds, $desa) {
                $query->where(function ($query) use ($tpsIds) {
                    $query->where('level', 'tps')
                        ->whereIn('entity_id', $tpsIds);
                })->orWhere(function ($query) use ($desa) {
                    $query->where('level', 'desa')
                        ->where('entity_id', $desa->id);
                })->orWhere(function ($query) use ($desa) {
                    $query->where('level', 'kecamatan')
                        ->where('entity_id', $desa->kecamatan_id);
                });
            })
            ->get();
        $tpsCellFlags = collect();
        $cellFlags = collect();

        foreach ($flagRows as $flag) {
            if ($flag->level === 'tps') {
                $tpsCellFlags->put($flag->entity_id.':'.$flag->row_key, true);
                $cellFlags->put($flag->row_key, true);

                continue;
            }

            if ($flag->level === 'desa') {
                $cellFlags->put($flag->row_key, true);
            }

            if ($flag->level === 'kecamatan') {
                $cellFlags->put($flag->row_key, true);
            }
        }
        $master = $this->getMaster($jenis, $desa);

        return view('rekap.pps.show', compact('desa', 'jenis', 'rekaps', 'tpsList', 'master', 'tpsCellFlags', 'cellFlags'));
    }

    // Mengekspor rekap desa untuk jenis pemilihan.
    public function export(string $jenis)
    {
        $this->cekAktif($jenis);
        $desa = $this->activeDesa();
        $tpsIds = $desa->tps->pluck('id');

        $rekaps = RekapHeader::with(['partaiSuaras', 'calegSuaras'])
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get();

        $tpsList = $desa->tps;
        $master = $this->getAllMaster($desa);
        $label = RekapHeader::JENIS_LABELS[$jenis];
        $wilayah = $desa->nama.' — '.$desa->kecamatan->nama;
        $filename = 'Rekap_'.strtoupper($jenis).'_'.str_replace(' ', '_', $desa->nama).'.xlsx';

        $sheet = new \App\Exports\RekapSheetExport(
            $jenis,
            $label,
            $rekaps,
            $master,
            $tpsList,
            'kordes',
            $wilayah
        );

        return \Maatwebsite\Excel\Facades\Excel::download($sheet, $filename);
    }

    // Mengambil master data sesuai jenis pemilihan.
    private function getMaster(string $jenis, Desa $desa): array
    {
        $partais = \App\Models\RekapPartai::with('calegs')->where('jenis', $jenis)->configuredParty();

        if ($jenis === 'dprd_kab') {
            $partais->where('dapil_id', $desa->kecamatan?->dapil_id);
        }

        return ['partais' => $partais->orderBy('nomor_urut')->get()];
    }

    // Mengambil semua master data untuk kebutuhan export.
    private function getAllMaster(Desa $desa): array
    {
        return [
            'dpr_ri' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->configuredParty()->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->configuredParty()->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->configuredParty()->where('dapil_id', $desa->kecamatan?->dapil_id)->orderBy('nomor_urut')->get()],
        ];
    }

    private function activeDesa(): Desa
    {
        $user = Auth::user();

        if ($user->role === 'admin_partai') {
            abort_if(! session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');

            return Desa::with('kecamatan', 'tps')->findOrFail(session('admin_view_desa_id'));
        }

        if ($user->role === 'korcam') {
            abort_if(! session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $desa = Desa::with('kecamatan', 'tps')->findOrFail(session('admin_view_desa_id'));
            abort_if($desa->kecamatan_id !== $user->kecamatan_id, 403, 'Akses ditolak.');

            return $desa;
        }

        abort_if(! $user->desa_id, 403, 'Akun belum di-assign ke Desa.');

        return Desa::with('kecamatan', 'tps')->findOrFail($user->desa_id);
    }
}
