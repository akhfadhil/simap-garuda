<?php

namespace App\Http\Controllers\Rekap;

use App\Exports\TpsStatusReportExport;
use App\Http\Controllers\Controller;
use App\Models\Dapil;
use App\Models\Kecamatan;
use App\Models\PemiluSetting;
use App\Models\RekapCellFlag;
use App\Models\RekapHeader;
use App\Models\Tps;
use App\Services\RekapAdminCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    // Menampilkan daftar rekap kabupaten dengan filter kecamatan.
    public function index()
    {
        $kecamatans = Kecamatan::all();
        $kecId = request('kecamatan_id');
        $tpsIds = Tps::when($kecId, fn ($q) => $q->whereHas('desa', fn ($q2) => $q2->where('kecamatan_id', $kecId)))->pluck('id');
        $rekaps = RekapHeader::whereIn('tps_id', $tpsIds)->get()->groupBy('jenis');
        $desaIds = \App\Models\Desa::when($kecId, fn ($q) => $q->where('kecamatan_id', $kecId))->pluck('id');
        $kecamatanIds = $kecId ? collect([(int) $kecId]) : $kecamatans->pluck('id');
        $flaggedJenis = RekapCellFlag::query()
            ->when($kecId, function ($query) use ($tpsIds, $desaIds, $kecamatanIds) {
                $query->where(function ($query) use ($tpsIds, $desaIds, $kecamatanIds) {
                    $query->where(function ($query) use ($tpsIds) {
                        $query->where('level', 'tps')
                            ->whereIn('entity_id', $tpsIds);
                    })->orWhere(function ($query) use ($desaIds) {
                        $query->where('level', 'desa')
                            ->whereIn('entity_id', $desaIds);
                    })->orWhere(function ($query) use ($kecamatanIds) {
                        $query->where('level', 'kecamatan')
                            ->whereIn('entity_id', $kecamatanIds);
                    });
                });
            })
            ->pluck('jenis')
            ->unique()
            ->flip();

        return view('rekap.admin.index', compact('kecamatans', 'rekaps', 'flaggedJenis'));
    }

    // Menampilkan rekap agregat kabupaten untuk jenis pemilihan.
    public function show(string $jenis)
    {
        $dapils = collect();
        $selectedDapilId = null;
        $showDetail = request()->boolean('detail');
        $detailKecamatanId = (int) request('detail_kecamatan_id');
        $detailDesaId = (int) request('detail_desa_id');

        if ($jenis === 'dprd_kab') {
            $dapils = Dapil::orderBy('nama')->get();
            $requestedDapilId = (int) request('dapil_id');
            $selectedDapilId = $dapils->contains('id', $requestedDapilId)
                ? $requestedDapilId
                : (int) $dapils->first()?->id;
            $kecamatans = Kecamatan::with(['desas.tps'])
                ->where('dapil_id', $selectedDapilId)
                ->orderBy('nama')
                ->get();
        } else {
            $kecamatans = Kecamatan::with(['desas.tps'])->orderBy('nama')->get();
        }

        $detailKecamatan = $kecamatans->firstWhere('id', $detailKecamatanId);
        $detailDesaOptions = $detailKecamatan ? $detailKecamatan->desas->values() : collect();
        $detailKecamatans = collect();
        if ($showDetail && $detailKecamatan) {
            $detailKecamatanForDetail = clone $detailKecamatan;

            if ($detailDesaId) {
                $detailKecamatanForDetail->setRelation(
                    'desas',
                    $detailKecamatan->desas->where('id', $detailDesaId)->values()
                );
            }

            $detailKecamatans = collect([$detailKecamatanForDetail]);
        }

        $tpsIds = $kecamatans->flatMap(fn ($kecamatan) => $kecamatan->desas->flatMap(fn ($desa) => $desa->tps->pluck('id')));
        $detailTpsIds = $detailKecamatans->flatMap(fn ($kecamatan) => $kecamatan->desas->flatMap(fn ($desa) => $desa->tps->pluck('id')));
        abort_unless(in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true), 404);
        $relations = ['partaiSuaras.partai', 'calegSuaras.caleg'];
        $rekaps = RekapHeader::query()
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get()->keyBy('tps_id');
        $detailRekaps = $showDetail && $detailTpsIds->isNotEmpty()
            ? RekapHeader::with($relations)
                ->whereIn('tps_id', $detailTpsIds)
                ->where('jenis', $jenis)
                ->get()
                ->keyBy('tps_id')
            : collect();
        $fieldNames = [
            'dpt_lk', 'dpt_pr',
            'pengguna_dpt_lk', 'pengguna_dpt_pr',
            'pengguna_dptb_lk', 'pengguna_dptb_pr',
            'pengguna_dpk_lk', 'pengguna_dpk_pr',
            'ss_diterima', 'ss_digunakan', 'ss_rusak', 'ss_sisa',
            'disabilitas_lk', 'disabilitas_pr',
            'suara_tidak_sah',
        ];

        $kecStats = [];
        $kecCalonTotals = [];
        $kecPartaiTotals = [];
        $kecCalegTotals = [];
        $kecPartaiGrandTotals = [];
        $tpsKecamatan = [];
        $tpsDesa = [];
        $desaKecamatan = [];

        foreach ($kecamatans as $kecamatan) {
            $kecStats[$kecamatan->id] = array_fill_keys($fieldNames, 0);
            $kecStats[$kecamatan->id]['suara_sah'] = 0;
            $kecStats[$kecamatan->id]['suara_total'] = 0;
            $kecStats[$kecamatan->id]['final'] = 0;
            $kecStats[$kecamatan->id]['tps_count'] = 0;

            foreach ($kecamatan->desas as $desa) {
                $desaKecamatan[$desa->id] = $kecamatan->id;

                foreach ($desa->tps as $tps) {
                    $tpsKecamatan[$tps->id] = $kecamatan->id;
                    $tpsDesa[$tps->id] = $desa->id;
                    $kecStats[$kecamatan->id]['tps_count']++;
                }
            }
        }

        foreach ($rekaps as $rekap) {
            $kecamatanId = $tpsKecamatan[$rekap->tps_id] ?? null;
            if (! $kecamatanId) {
                continue;
            }

            foreach ($fieldNames as $field) {
                $kecStats[$kecamatanId][$field] += (int) ($rekap->{$field} ?? 0);
            }

            $kecStats[$kecamatanId]['final'] += $rekap->status === 'final' ? 1 : 0;
        }

        $suaraTotals = $this->aggregateSuaraByKecamatan($jenis, $selectedDapilId);
        $kecCalonTotals = $suaraTotals['calons'];
        $kecPartaiTotals = $suaraTotals['partais'];
        $kecCalegTotals = $suaraTotals['calegs'];
        $kecPartaiGrandTotals = $suaraTotals['partaiGrandTotals'];

        foreach ($suaraTotals['suaraSah'] as $kecamatanId => $suaraSah) {
            if (isset($kecStats[$kecamatanId])) {
                $kecStats[$kecamatanId]['suara_sah'] = $suaraSah;
            }
        }

        foreach ($kecStats as $kecamatanId => $stats) {
            $kecStats[$kecamatanId]['suara_total'] = $stats['suara_sah'] + $stats['suara_tidak_sah'];
        }

        $master = $this->getMaster($jenis, $selectedDapilId);
        $kecamatanIds = $kecamatans->pluck('id');
        $desaIds = collect(array_keys($desaKecamatan));
        $flagRows = RekapCellFlag::query()
            ->where('jenis', $jenis)
            ->where(function ($query) use ($tpsIds, $desaIds, $kecamatanIds) {
                $query->where(function ($query) use ($tpsIds) {
                    $query->where('level', 'tps')
                        ->whereIn('entity_id', $tpsIds);
                })->orWhere(function ($query) use ($desaIds) {
                    $query->where('level', 'desa')
                        ->whereIn('entity_id', $desaIds);
                })->orWhere(function ($query) use ($kecamatanIds) {
                    $query->where('level', 'kecamatan')
                        ->whereIn('entity_id', $kecamatanIds);
                });
            })
            ->get();
        $tpsCellFlags = collect();
        $desaCellFlags = collect();
        $kecCellFlags = collect();
        $kecDirectCellFlags = collect();

        foreach ($flagRows as $flag) {
            if ($flag->level === 'tps') {
                $tpsCellFlags->put($flag->entity_id.':'.$flag->row_key, true);

                $desaId = $tpsDesa[$flag->entity_id] ?? null;
                $kecamatanId = $tpsKecamatan[$flag->entity_id] ?? null;

                if ($desaId) {
                    $desaCellFlags->put($desaId.':'.$flag->row_key, true);
                }
                if ($kecamatanId) {
                    $kecCellFlags->put($kecamatanId.':'.$flag->row_key, true);
                }

                continue;
            }

            if ($flag->level === 'desa') {
                $desaCellFlags->put($flag->entity_id.':'.$flag->row_key, true);

                $kecamatanId = $desaKecamatan[$flag->entity_id] ?? null;
                if ($kecamatanId) {
                    $kecCellFlags->put($kecamatanId.':'.$flag->row_key, true);
                }
            }

            if ($flag->level === 'kecamatan') {
                $kecCellFlags->put($flag->entity_id.':'.$flag->row_key, true);
                $kecDirectCellFlags->put($flag->entity_id.':'.$flag->row_key, true);

                foreach ($desaKecamatan as $desaId => $kecamatanId) {
                    if ((int) $kecamatanId === (int) $flag->entity_id) {
                        $desaCellFlags->put($desaId.':'.$flag->row_key, true);
                    }
                }
            }
        }

        return view('rekap.admin.show', compact(
            'kecamatans',
            'jenis',
            'rekaps',
            'detailRekaps',
            'detailKecamatans',
            'detailKecamatanId',
            'detailDesaId',
            'detailDesaOptions',
            'master',
            'dapils',
            'selectedDapilId',
            'kecStats',
            'kecCalonTotals',
            'kecPartaiTotals',
            'kecCalegTotals',
            'kecPartaiGrandTotals',
            'tpsCellFlags',
            'desaCellFlags',
            'kecCellFlags',
            'kecDirectCellFlags'
        ));
    }

    // Menandai/menghapus tanda merah manual pada cell rekap dari halaman admin.
    public function toggleCellFlag(Request $request, string $jenis)
    {
        abort_if($request->user()?->role !== 'admin', 403);
        abort_unless(array_key_exists($jenis, RekapHeader::JENIS_LABELS), 404);

        $data = $request->validate([
            'level' => ['nullable', 'string', 'in:tps,kecamatan'],
            'entity_id' => ['required', 'integer'],
            'row_key' => ['required', 'string', 'max:191'],
        ]);
        $level = $data['level'] ?? 'tps';

        if ($level === 'tps') {
            $entity = Tps::findOrFail($data['entity_id']);
        } else {
            $entity = Kecamatan::findOrFail($data['entity_id']);
        }

        $flag = RekapCellFlag::where([
            'jenis' => $jenis,
            'level' => $level,
            'entity_id' => $entity->id,
            'row_key' => $data['row_key'],
        ])->first();

        $flagged = false;

        if ($flag) {
            $flag->delete();
        } else {
            RekapCellFlag::create([
                'jenis' => $jenis,
                'level' => $level,
                'entity_id' => $entity->id,
                'row_key' => $data['row_key'],
                'flagged_by' => $request->user()->id,
            ]);
            $flagged = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'flagged' => $flagged,
                'level' => $level,
                'entity_id' => $entity->id,
                'tps_id' => $level === 'tps' ? $entity->id : null,
                'desa_id' => $level === 'tps' ? $entity->desa_id : null,
                'kecamatan_id' => $level === 'kecamatan' ? $entity->id : null,
                'row_key' => $data['row_key'],
            ]);
        }

        return back();
    }

    // Mengekspor rekap admin untuk jenis pemilihan.
    public function export(string $jenis)
    {
        $kecId = request('kecamatan_id');
        $desas = \App\Models\Desa::with('tps')
            ->when($kecId, fn ($q) => $q->where('kecamatan_id', $kecId))
            ->get();

        $tpsIds = $desas->flatMap(fn ($d) => $d->tps->pluck('id'));

        abort_unless(in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true), 404);

        $rekaps = \App\Models\RekapHeader::with(['partaiSuaras', 'calegSuaras'])
            ->whereIn('tps_id', $tpsIds)
            ->where('jenis', $jenis)
            ->get();

        $tpsList = $desas->flatMap(fn ($d) => $d->tps)->values();
        $master = $this->getAllMaster();
        $wilayah = $kecId
            ? 'Kec. '.\App\Models\Kecamatan::find($kecId)?->nama
            : 'Semua Kecamatan';

        $suffix = $kecId ? '_Kec_'.$kecId : '_Semua';
        $filename = 'Rekap_'.strtoupper($jenis).'_Admin'.$suffix.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RekapExport($rekaps, $master, $tpsList, 'admin', $wilayah, $desas, $jenis),
            $filename
        );
    }

    public function exportMissingTps()
    {
        $jenisList = $this->activeLegislativeJenis();
        $rows = [];

        if ($jenisList) {
            $rows = Tps::query()
                ->join('desas as d', 'd.id', '=', 'tps.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->whereNotExists(function ($query) use ($jenisList) {
                    $query->selectRaw('1')
                        ->from('rekap_headers as h')
                        ->whereColumn('h.tps_id', 'tps.id')
                        ->whereIn('h.jenis', $jenisList);
                })
                ->orderBy('k.nama')
                ->orderBy('d.nama')
                ->orderBy('tps.nama')
                ->get([
                    'tps.nama as tps',
                    'd.nama as desa',
                    'k.nama as kecamatan',
                ])
                ->map(fn ($row) => [
                    $row->kecamatan,
                    $row->desa,
                    $row->tps,
                    collect($jenisList)->map(fn ($jenis) => RekapHeader::JENIS_LABELS[$jenis] ?? strtoupper($jenis))->implode(', '),
                ])
                ->toArray();
        }

        return Excel::download(
            new TpsStatusReportExport(
                'TPS Belum Masuk',
                ['Kecamatan', 'Desa', 'TPS', 'Pemilihan Aktif'],
                $rows
            ),
            'TPS_Belum_Masuk.xlsx'
        );
    }

    public function exportReviewTps()
    {
        $jenisList = $this->activeLegislativeJenis();
        $rows = RekapHeader::query()
            ->join('tps as t', 't.id', '=', 'rekap_headers.tps_id')
            ->join('desas as d', 'd.id', '=', 't.desa_id')
            ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
            ->whereIn('rekap_headers.jenis', $jenisList)
            ->where('rekap_headers.status', 'perlu_dicek')
            ->orderByDesc('rekap_headers.updated_at')
            ->orderBy('k.nama')
            ->orderBy('d.nama')
            ->orderBy('t.nama')
            ->get([
                'rekap_headers.jenis',
                'rekap_headers.status',
                'rekap_headers.catatan_internal',
                't.nama as tps',
                'd.nama as desa',
                'k.nama as kecamatan',
            ])
            ->map(fn ($row) => [
                RekapHeader::JENIS_LABELS[$row->jenis] ?? strtoupper($row->jenis),
                $row->kecamatan,
                $row->desa,
                $row->tps,
                'Perlu Dicek',
                $row->catatan_internal,
            ])
            ->toArray();

        return Excel::download(
            new TpsStatusReportExport(
                'TPS Perlu Dicek',
                ['Jenis', 'Kecamatan', 'Desa', 'TPS', 'Status', 'Catatan Internal'],
                $rows
            ),
            'TPS_Perlu_Dicek.xlsx'
        );
    }

    // Mengambil master data sesuai jenis pemilihan dan dapil.
    private function getMaster(string $jenis, ?int $dapilId = null): array
    {
        $partais = \App\Models\RekapPartai::with('calegs')->where('jenis', $jenis)->garuda();
        $partaiNomor = $this->partaiScopeNomor($jenis);

        if ($jenis === 'dprd_kab' && $dapilId) {
            $partais->where('dapil_id', $dapilId);
        }

        if ($partaiNomor) {
            $partais->where('nomor_urut', $partaiNomor);
        }

        return ['partais' => $partais->orderBy('nomor_urut')->get()];
    }

    private function activeLegislativeJenis(): array
    {
        return collect(PemiluSetting::aktif())
            ->filter(fn ($jenis) => in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true))
            ->values()
            ->all();
    }

    // Mengambil nomor partai untuk akun partai pada jenis legislatif.
    private function partaiScopeNomor(string $jenis): ?int
    {
        if (! Auth::check() || Auth::user()->role !== 'partai') {
            return null;
        }

        if (! in_array($jenis, ['dpr_ri', 'dprd_prov', 'dprd_kab'], true)) {
            return null;
        }

        $partai = Auth::user()->partai;
        abort_if(! $partai, 403, 'Akun partai belum dihubungkan ke master partai.');

        return (int) $partai->nomor_urut;
    }

    private function applyGarudaPartaiQuery($query, string $alias = 'p')
    {
        $party = config('party');
        $numbers = collect($party['historical_numbers'] ?? [])
            ->map(fn ($number) => (int) $number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $query->where(function ($query) use ($alias, $party, $numbers) {
            $query->where("{$alias}.nama_partai", 'like', '%'.($party['short_name'] ?? 'Garuda').'%')
                ->orWhere("{$alias}.nama_partai", 'like', '%'.($party['name'] ?? 'Partai Garuda').'%');

            if ($numbers) {
                $query->orWhereIn("{$alias}.nomor_urut", $numbers);
            }
        });
    }

    // Menghitung agregat suara per kecamatan.
    private function aggregateSuaraByKecamatan(string $jenis, ?int $dapilId = null): array
    {
        $partaiNomor = $this->partaiScopeNomor($jenis);

        return RekapAdminCache::rememberAggregate($jenis, $dapilId, function () use ($jenis, $dapilId, $partaiNomor) {
            $result = [
                'calons' => [],
                'partais' => [],
                'calegs' => [],
                'partaiGrandTotals' => [],
                'suaraSah' => [],
            ];

            $partaiRows = $this->baseSuaraAggregateQuery('rekap_partai_suaras', $jenis, $dapilId)
                ->join('rekap_partais as p', 'p.id', '=', 's.partai_id')
                ->where('p.jenis', $jenis)
                ->tap(fn ($query) => $this->applyGarudaPartaiQuery($query, 'p'))
                ->when($partaiNomor, fn ($query) => $query->where('p.nomor_urut', $partaiNomor))
                ->when($jenis === 'dprd_kab' && $dapilId, fn ($query) => $query->where('p.dapil_id', $dapilId))
                ->select('k.id as kecamatan_id', 's.partai_id', DB::raw('SUM(s.suara) as total_suara'))
                ->groupBy('k.id', 's.partai_id')
                ->get();

            foreach ($partaiRows as $row) {
                $kecamatanId = (int) $row->kecamatan_id;
                $partaiId = (int) $row->partai_id;
                $total = (int) $row->total_suara;

                $result['partais'][$kecamatanId][$partaiId] = $total;
                $result['partaiGrandTotals'][$kecamatanId][$partaiId] =
                    ($result['partaiGrandTotals'][$kecamatanId][$partaiId] ?? 0) + $total;
                $result['suaraSah'][$kecamatanId] = ($result['suaraSah'][$kecamatanId] ?? 0) + $total;
            }

            $calegRows = $this->baseSuaraAggregateQuery('rekap_caleg_suaras', $jenis, $dapilId)
                ->join('rekap_calegs as c', 'c.id', '=', 's.caleg_id')
                ->join('rekap_partais as p', 'p.id', '=', 'c.partai_id')
                ->where('p.jenis', $jenis)
                ->tap(fn ($query) => $this->applyGarudaPartaiQuery($query, 'p'))
                ->when($partaiNomor, fn ($query) => $query->where('p.nomor_urut', $partaiNomor))
                ->when($jenis === 'dprd_kab' && $dapilId, fn ($query) => $query->where('p.dapil_id', $dapilId))
                ->select('k.id as kecamatan_id', 's.caleg_id', 'p.id as partai_id', DB::raw('SUM(s.suara) as total_suara'))
                ->groupBy('k.id', 's.caleg_id', 'p.id')
                ->get();

            foreach ($calegRows as $row) {
                $kecamatanId = (int) $row->kecamatan_id;
                $calegId = (int) $row->caleg_id;
                $partaiId = (int) $row->partai_id;
                $total = (int) $row->total_suara;

                $result['calegs'][$kecamatanId][$calegId] = $total;
                $result['partaiGrandTotals'][$kecamatanId][$partaiId] =
                    ($result['partaiGrandTotals'][$kecamatanId][$partaiId] ?? 0) + $total;
                $result['suaraSah'][$kecamatanId] = ($result['suaraSah'][$kecamatanId] ?? 0) + $total;
            }

            return $result;
        }, ['partai_nomor' => $partaiNomor]);
    }

    // Membentuk query dasar agregasi suara.
    private function baseSuaraAggregateQuery(string $table, string $jenis, ?int $dapilId = null)
    {
        return DB::table($table.' as s')
            ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
            ->join('tps as t', 't.id', '=', 'h.tps_id')
            ->join('desas as d', 'd.id', '=', 't.desa_id')
            ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
            ->where('h.jenis', $jenis)
            ->when($jenis === 'dprd_kab' && $dapilId, fn ($query) => $query->where('k.dapil_id', $dapilId));
    }

    // Mengambil semua master data untuk kebutuhan export.
    private function getAllMaster(): array
    {
        $partaiNomorDprRi = $this->partaiScopeNomor('dpr_ri');
        $partaiNomorDprdProv = $this->partaiScopeNomor('dprd_prov');
        $partaiNomorDprdKab = $this->partaiScopeNomor('dprd_kab');

        return [
            'dpr_ri' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->garuda()->when($partaiNomorDprRi, fn ($q) => $q->where('nomor_urut', $partaiNomorDprRi))->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->garuda()->when($partaiNomorDprdProv, fn ($q) => $q->where('nomor_urut', $partaiNomorDprdProv))->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => \App\Models\RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->garuda()->when($partaiNomorDprdKab, fn ($q) => $q->where('nomor_urut', $partaiNomorDprdKab))->orderBy('nomor_urut')->get()],
        ];
    }

    // Menampilkan halaman export rekap.
    public function exportPage()
    {
        $kecamatans = \App\Models\Kecamatan::orderBy('nama')->get();

        return view('rekap.admin.export', compact('kecamatans'));
    }

    // Mengunduh export rekap sesuai level wilayah.
    public function exportDownload(Request $request)
    {
        $request->validate([
            'jenis' => 'required|in:'.implode(',', \App\Models\RekapHeader::LEGISLATIVE_TYPES),
            'level' => 'required|in:tps,desa,kecamatan,kabupaten',
        ]);

        $jenis = $request->jenis;
        $level = $request->level;
        $label = \App\Models\RekapHeader::JENIS_LABELS[$jenis];

        switch ($level) {
            case 'tps':
                $tps = \App\Models\Tps::with('desa.kecamatan')->findOrFail($request->tps_id);
                $rekaps = \App\Models\RekapHeader::with(['partaiSuaras', 'calegSuaras'])
                    ->where('tps_id', $tps->id)->where('jenis', $jenis)->get();
                $tpsList = collect([$tps]);
                $master = $this->getAllMaster();
                $wilayah = $tps->nama.' — '.$tps->desa->nama;
                $filename = 'Rekap_'.strtoupper($jenis).'_'.str_replace(' ', '_', $tps->nama).'.xlsx';
                $sheet = new \App\Exports\RekapSheetExport($jenis, $label, $rekaps, $master, $tpsList, 'kpps', $wilayah);

                return \Maatwebsite\Excel\Facades\Excel::download($sheet, $filename);

            case 'desa':
                $desa = \App\Models\Desa::with('tps', 'kecamatan')->findOrFail($request->desa_id);
                $tpsIds = $desa->tps->pluck('id');
                $rekaps = \App\Models\RekapHeader::with(['partaiSuaras', 'calegSuaras'])
                    ->whereIn('tps_id', $tpsIds)->where('jenis', $jenis)->get();
                $tpsList = $desa->tps;
                $master = $this->getAllMaster();
                $wilayah = $desa->nama.' — Kec. '.$desa->kecamatan->nama;
                $filename = 'Rekap_'.strtoupper($jenis).'_'.str_replace(' ', '_', $desa->nama).'.xlsx';
                $sheet = new \App\Exports\RekapSheetExport($jenis, $label, $rekaps, $master, $tpsList, 'pps', $wilayah);

                return \Maatwebsite\Excel\Facades\Excel::download($sheet, $filename);

            case 'kecamatan':
                $kecamatan = \App\Models\Kecamatan::findOrFail($request->kecamatan_id);
                $desas = \App\Models\Desa::with('tps')->where('kecamatan_id', $kecamatan->id)->get();
                $tpsIds = $desas->flatMap(fn ($d) => $d->tps->pluck('id'));
                $rekaps = \App\Models\RekapHeader::with(['partaiSuaras', 'calegSuaras'])
                    ->whereIn('tps_id', $tpsIds)->where('jenis', $jenis)->get();
                $tpsList = $desas->flatMap(fn ($d) => $d->tps)->values();
                $master = $this->getAllMaster();
                $wilayah = 'Kec. '.$kecamatan->nama;
                $filename = 'Rekap_'.strtoupper($jenis).'_Kec_'.str_replace(' ', '_', $kecamatan->nama).'.xlsx';

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\RekapExport($rekaps, $master, $tpsList, 'ppk', $wilayah, $desas, $jenis),
                    $filename
                );

            case 'kabupaten':
                $desas = \App\Models\Desa::with('tps')->get();
                $tpsIds = $desas->flatMap(fn ($d) => $d->tps->pluck('id'));
                $rekaps = \App\Models\RekapHeader::with(['partaiSuaras', 'calegSuaras'])
                    ->whereIn('tps_id', $tpsIds)->where('jenis', $jenis)->get();
                $tpsList = $desas->flatMap(fn ($d) => $d->tps)->values();
                $master = $this->getAllMaster();
                $wilayah = 'Kabupaten';
                $filename = 'Rekap_'.strtoupper($jenis).'_Kabupaten.xlsx';

                $kecamatans = \App\Models\Kecamatan::with('desas.tps')->get();
                $pseudoDesas = $kecamatans->map(function ($kec) {
                    $kec->tps = $kec->desas->flatMap(fn ($d) => $d->tps);

                    return $kec;
                });

                return \Maatwebsite\Excel\Facades\Excel::download(
                    new \App\Exports\RekapExport($rekaps, $master, $tpsList, 'admin', $wilayah, $pseudoDesas, $jenis),
                    $filename
                );
        }
    }

    // Menampilkan halaman grafik dan statistik.
    public function chartPage()
    {
        $kecamatans = Kecamatan::with(['desas.tps'])->orderBy('nama')->get();
        $dapils = \App\Models\Dapil::with('kecamatans')->orderBy('nama')->get();

        return view('rekap.admin.chart', compact('kecamatans', 'dapils'));
    }

    // Mengambil data JSON untuk grafik dan peta.
    public function chartData(\Illuminate\Http\Request $request)
    {
        $jenis = $request->jenis;
        $level = $request->level ?? 'kabupaten';
        $kecId = $request->kecamatan_id;
        $desaId = $request->desa_id;
        $tpsId = $request->tps_id;
        $dapilId = $request->dapil_id;
        $activeDapilId = $jenis === 'dprd_kab' && $dapilId ? (int) $dapilId : null;

        abort_unless(in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true), 404);

        return $this->chartLegislatifData($jenis, $level, $kecId, $desaId, $tpsId, $dapilId, $activeDapilId);
    }

    // Mengambil data chart untuk pemilihan legislatif.
    private function chartLegislatifData(string $jenis, string $level, $kecId, $desaId, $tpsId, $dapilId, ?int $activeDapilId)
    {
        $partaiNomor = $this->partaiScopeNomor($jenis);

        return response()->json(RekapAdminCache::rememberChart([
            'version' => 3,
            'jenis' => $jenis,
            'level' => $level,
            'kecamatan_id' => $kecId,
            'desa_id' => $desaId,
            'tps_id' => $tpsId,
            'dapil_id' => $dapilId,
            'active_dapil_id' => $activeDapilId,
            'partai_nomor' => $partaiNomor,
        ], function () use ($jenis, $level, $kecId, $desaId, $tpsId, $dapilId, $activeDapilId) {
            $master = $this->getMaster($jenis, $activeDapilId);
            $partais = $master['partais'];
            $labels = ['Total Suara '.config('party.short_name')];
            $searchMeta = [
                $partais
                    ->map(fn ($partai) => trim($partai->nama_partai.' '.$partai->calegs->pluck('nama_caleg')->implode(' ')))
                    ->implode(' '),
            ];

            $groups = $this->chartGroupRows($level, $kecId, $desaId, $tpsId, $dapilId);
            $partaiIds = $partais->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $groupExpr = $this->chartGroupExpression($level);
            $calegs = $partais
                ->flatMap(fn ($partai) => $partai->calegs->map(fn ($caleg) => [
                    'id' => (int) $caleg->id,
                    'nama_caleg' => $caleg->nama_caleg,
                    'nama_partai' => $partai->nama_partai,
                ]));

            $suaraByGroup = [];
            foreach ($groups as $group) {
                $suaraByGroup[(int) $group['id']] = [0];
            }
            $groupIndex = array_flip($groups->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
            $suaraByCaleg = array_fill_keys($calegs->pluck('id')->values()->all(), 0);
            $candidateSeriesByCaleg = [];
            foreach ($suaraByCaleg as $calegId => $_) {
                $candidateSeriesByCaleg[(int) $calegId] = array_fill(0, $groups->count(), 0);
            }

            if ($groups->isNotEmpty() && count($partaiIds) > 0) {
                $partaiRows = $this->applyChartScope(
                    DB::table('rekap_partai_suaras as s')
                        ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                        ->join('tps as t', 't.id', '=', 'h.tps_id')
                        ->join('desas as d', 'd.id', '=', 't.desa_id')
                        ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                        ->join('rekap_partais as p', 'p.id', '=', 's.partai_id'),
                    $jenis,
                    $kecId,
                    $desaId,
                    $tpsId,
                    $dapilId
                )
                    ->where('p.jenis', $jenis)
                    ->when($jenis === 'dprd_kab' && $activeDapilId, fn ($query) => $query->where('p.dapil_id', $activeDapilId))
                    ->whereIn('s.partai_id', $partaiIds)
                    ->selectRaw($groupExpr.' as group_id, SUM(s.suara) as total_suara')
                    ->groupBy(DB::raw($groupExpr))
                    ->get();

                foreach ($partaiRows as $row) {
                    $groupId = (int) $row->group_id;
                    if (isset($suaraByGroup[$groupId])) {
                        $suaraByGroup[$groupId][0] += (int) $row->total_suara;
                    }
                }

                $calegRows = $this->applyChartScope(
                    DB::table('rekap_caleg_suaras as s')
                        ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                        ->join('tps as t', 't.id', '=', 'h.tps_id')
                        ->join('desas as d', 'd.id', '=', 't.desa_id')
                        ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                        ->join('rekap_calegs as c', 'c.id', '=', 's.caleg_id')
                        ->join('rekap_partais as p', 'p.id', '=', 'c.partai_id'),
                    $jenis,
                    $kecId,
                    $desaId,
                    $tpsId,
                    $dapilId
                )
                    ->where('p.jenis', $jenis)
                    ->when($jenis === 'dprd_kab' && $activeDapilId, fn ($query) => $query->where('p.dapil_id', $activeDapilId))
                    ->whereIn('p.id', $partaiIds)
                    ->selectRaw($groupExpr.' as group_id, s.caleg_id, SUM(s.suara) as total_suara')
                    ->groupBy(DB::raw($groupExpr), 's.caleg_id')
                    ->get();

                foreach ($calegRows as $row) {
                    $groupId = (int) $row->group_id;
                    $calegId = (int) $row->caleg_id;
                    $total = (int) $row->total_suara;
                    if (isset($suaraByGroup[$groupId])) {
                        $suaraByGroup[$groupId][0] += $total;
                    }
                    if (array_key_exists($calegId, $suaraByCaleg)) {
                        $suaraByCaleg[$calegId] += $total;
                    }
                    if (isset($groupIndex[$groupId], $candidateSeriesByCaleg[$calegId])) {
                        $candidateSeriesByCaleg[$calegId][$groupIndex[$groupId]] += $total;
                    }
                }
            }

            $partisipasiRows = $this->applyChartScope(
                DB::table('rekap_headers as h')
                    ->join('tps as t', 't.id', '=', 'h.tps_id')
                    ->join('desas as d', 'd.id', '=', 't.desa_id')
                    ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id'),
                $jenis,
                $kecId,
                $desaId,
                $tpsId,
                $dapilId
            )
                ->selectRaw($groupExpr.' as group_id')
                ->selectRaw('SUM(COALESCE(h.dpt_lk, 0)) as dpt_lk')
                ->selectRaw('SUM(COALESCE(h.dpt_pr, 0)) as dpt_pr')
                ->selectRaw('SUM(COALESCE(h.pengguna_dpt_lk, 0) + COALESCE(h.pengguna_dpt_pr, 0) + COALESCE(h.pengguna_dptb_lk, 0) + COALESCE(h.pengguna_dptb_pr, 0) + COALESCE(h.pengguna_dpk_lk, 0) + COALESCE(h.pengguna_dpk_pr, 0)) as hadir')
                ->selectRaw('COUNT(DISTINCT h.tps_id) as tps_masuk')
                ->groupBy(DB::raw($groupExpr))
                ->get()
                ->keyBy(fn ($row) => (int) $row->group_id);

            $data = $groups->map(function ($group) use ($suaraByGroup, $partisipasiRows) {
                $groupId = (int) $group['id'];
                $partisipasi = $partisipasiRows->get($groupId);
                $dptLk = (int) ($partisipasi->dpt_lk ?? 0);
                $dptPr = (int) ($partisipasi->dpt_pr ?? 0);

                return [
                    'label' => $group['label'],
                    'suara' => $suaraByGroup[$groupId] ?? [],
                    'partisipasi' => [
                        'dpt' => $dptLk + $dptPr,
                        'dpt_lk' => $dptLk,
                        'dpt_pr' => $dptPr,
                        'hadir' => (int) ($partisipasi->hadir ?? 0),
                        'tps_masuk' => (int) ($partisipasi->tps_masuk ?? 0),
                        'tps_total' => (int) $group['tps_total'],
                    ],
                ];
            })->values()->toArray();

            return [
                'type' => 'bar',
                'jenis' => $jenis,
                'labels' => $labels,
                'search_meta' => $searchMeta,
                'candidate_rank' => $calegs
                    ->map(fn ($caleg) => [
                        'id' => $caleg['id'],
                        'label' => $caleg['nama_caleg'],
                        'meta' => $caleg['nama_partai'],
                        'suara' => (int) ($suaraByCaleg[$caleg['id']] ?? 0),
                    ])
                    ->sortByDesc('suara')
                    ->values()
                    ->toArray(),
                'candidate_series' => $calegs
                    ->map(fn ($caleg) => [
                        'id' => $caleg['id'],
                        'label' => $caleg['nama_caleg'],
                        'meta' => $caleg['nama_partai'],
                        'suara' => $candidateSeriesByCaleg[$caleg['id']] ?? array_fill(0, $groups->count(), 0),
                    ])
                    ->values()
                    ->toArray(),
                'data' => $data,
            ];
        }));
    }

    // Mengambil baris wilayah untuk grouping chart.
    private function chartGroupRows(string $level, $kecId, $desaId, $tpsId, $dapilId)
    {
        if ($level === 'kabupaten') {
            return Kecamatan::with(['desas.tps:id,desa_id'])
                ->orderBy('nama')
                ->get()
                ->map(fn ($kec) => [
                    'id' => (int) $kec->id,
                    'label' => $kec->nama,
                    'tps_total' => $kec->desas->sum(fn ($desa) => $desa->tps->count()),
                ]);
        }

        if ($level === 'dapil' && $dapilId) {
            return Kecamatan::with(['desas.tps:id,desa_id'])
                ->where('dapil_id', $dapilId)
                ->orderBy('nama')
                ->get()
                ->map(fn ($kec) => [
                    'id' => (int) $kec->id,
                    'label' => $kec->nama,
                    'tps_total' => $kec->desas->sum(fn ($desa) => $desa->tps->count()),
                ]);
        }

        if ($level === 'kecamatan' && $kecId) {
            return \App\Models\Desa::withCount('tps')
                ->where('kecamatan_id', $kecId)
                ->orderBy('nama')
                ->get()
                ->map(fn ($desa) => [
                    'id' => (int) $desa->id,
                    'label' => $desa->nama,
                    'tps_total' => (int) $desa->tps_count,
                ]);
        }

        if ($level === 'desa' && $desaId) {
            return Tps::where('desa_id', $desaId)
                ->orderBy('nama')
                ->get()
                ->map(fn ($tps) => [
                    'id' => (int) $tps->id,
                    'label' => $tps->nama,
                    'tps_total' => 1,
                ]);
        }

        if ($level === 'tps' && $tpsId) {
            return Tps::where('id', $tpsId)
                ->get()
                ->map(fn ($tps) => [
                    'id' => (int) $tps->id,
                    'label' => $tps->nama,
                    'tps_total' => 1,
                ]);
        }

        return collect();
    }

    // Menentukan ekspresi SQL grouping chart.
    private function chartGroupExpression(string $level): string
    {
        return match ($level) {
            'kecamatan' => 'd.id',
            'desa', 'tps' => 't.id',
            default => 'k.id',
        };
    }

    // Menerapkan filter scope wilayah pada query chart.
    private function applyChartScope($query, string $jenis, $kecId, $desaId, $tpsId, $dapilId)
    {
        $query->where('h.jenis', $jenis);

        if ($tpsId) {
            return $query->where('t.id', $tpsId);
        }

        if ($desaId) {
            return $query->where('d.id', $desaId);
        }

        if ($kecId) {
            return $query->where('k.id', $kecId);
        }

        if ($dapilId) {
            return $query->where('k.dapil_id', $dapilId);
        }

        return $query;
    }

    // Membentuk ranking kandidat/caleg untuk sidebar chart.
    private function buildCandidateRanking($rekaps, string $jenis, ?int $dapilId = null): array
    {
        if (! in_array($jenis, ['dpr_ri', 'dprd_prov', 'dprd_kab'])) {
            return [];
        }

        $partais = $this->getMaster($jenis, $dapilId)['partais'];
        $calegs = $partais
            ->flatMap(fn ($partai) => $partai->calegs->map(fn ($caleg) => [
                'id' => $caleg->id,
                'nama_caleg' => $caleg->nama_caleg,
                'nama_partai' => $partai->nama_partai,
            ]));

        return $calegs
            ->map(function ($caleg) use ($rekaps) {
                $suara = $rekaps->sum(fn ($rekap) => $rekap->calegSuaras->firstWhere('caleg_id', $caleg['id'])?->suara ?? 0);

                return [
                    'label' => $caleg['nama_caleg'],
                    'meta' => $caleg['nama_partai'],
                    'suara' => $suara,
                ];
            })
            ->sortByDesc('suara')
            ->values()
            ->toArray();
    }

    // Membentuk data suara untuk chart fallback.
    private function buildSuaraData($rekaps, string $jenis, ?int $dapilId = null): array
    {
        $master = $this->getMaster($jenis, $dapilId);

        return $master['partais']->map(function ($partai) use ($rekaps) {
            return $rekaps->sum(fn ($r) => ($r->partaiSuaras->firstWhere('partai_id', $partai->id)?->suara ?? 0) +
                $r->calegSuaras->whereIn('caleg_id', $partai->calegs->pluck('id'))->sum('suara')
            );
        })->toArray();
    }

    // Menghitung data partisipasi dari rekap.
    private function buildPartisipasiData($rekaps, ?int $tpsTotal = null): array
    {
        return [
            'dpt' => $rekaps->sum(fn ($r) => ($r->dpt_lk ?? 0) + ($r->dpt_pr ?? 0)),
            'dpt_lk' => $rekaps->sum(fn ($r) => $r->dpt_lk ?? 0),
            'dpt_pr' => $rekaps->sum(fn ($r) => $r->dpt_pr ?? 0),
            'hadir' => $rekaps->sum(fn ($r) => ($r->pengguna_dpt_lk ?? 0) + ($r->pengguna_dpt_pr ?? 0) +
                                            ($r->pengguna_dptb_lk ?? 0) + ($r->pengguna_dptb_pr ?? 0) +
                                            ($r->pengguna_dpk_lk ?? 0) + ($r->pengguna_dpk_pr ?? 0)),
            'tps_masuk' => $rekaps->pluck('tps_id')->unique()->count(),
            'tps_total' => $tpsTotal ?? $rekaps->pluck('tps_id')->unique()->count(),
        ];
    }
}
