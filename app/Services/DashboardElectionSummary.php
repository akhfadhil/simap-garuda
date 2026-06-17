<?php

namespace App\Services;

use App\Models\Dapil;
use App\Models\PemiluSetting;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Models\User;
use App\Support\PartyConfig;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DashboardElectionSummary
{
    public function __construct(private PartyScopeService $partyScope)
    {
    }

    public function forUser(User $user): array
    {
        $scope = $this->partyScope->dashboardScopeFor($user);
        $activeJenis = PemiluSetting::aktif();

        return RekapAdminCache::rememberDashboardSummary($this->cacheParts($user, $scope, $activeJenis), function () use ($activeJenis, $scope) {
            $sections = [];
            $overview = $this->overview($activeJenis, $scope);

            foreach ($activeJenis as $jenis) {
                if (in_array($jenis, ['dpr_ri', 'dprd_prov'], true)) {
                    $sections[] = $this->calegSection($jenis, $scope);

                    continue;
                }

                if ($jenis === 'dprd_kab') {
                    foreach ($this->dprdKabSections($scope) as $section) {
                        $sections[] = $section;
                    }
                }
            }

            return [
                'scope_label' => $scope['label'],
                'overview' => $overview,
                'sections' => array_values(array_filter($sections)),
            ];
        });
    }

    private function overview(array $activeJenis, array $scope): array
    {
        $jenisList = collect($activeJenis)
            ->filter(fn ($jenis) => in_array($jenis, RekapHeader::LEGISLATIVE_TYPES, true))
            ->values()
            ->all();

        $tpsQuery = $this->applyScope(
            DB::table('tps as t')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id'),
            $scope
        );

        $totalTps = (clone $tpsQuery)->count('t.id');
        $missingTps = collect();
        $reviewTps = collect();
        $reviewTpsCount = 0;
        $inputTps = 0;
        $totalSuara = 0;
        $regions = [
            'label' => null,
            'strong' => [],
            'weak' => [],
        ];

        if ($jenisList) {
            $inputTps = (clone $tpsQuery)
                ->whereExists(function ($query) use ($jenisList) {
                    $query->selectRaw('1')
                        ->from('rekap_headers as h')
                        ->whereColumn('h.tps_id', 't.id')
                        ->whereIn('h.jenis', $jenisList);
                })
                ->count('t.id');

            $missingTps = (clone $tpsQuery)
                ->whereNotExists(function ($query) use ($jenisList) {
                    $query->selectRaw('1')
                        ->from('rekap_headers as h')
                        ->whereColumn('h.tps_id', 't.id')
                        ->whereIn('h.jenis', $jenisList);
                })
                ->orderBy('k.nama')
                ->orderBy('d.nama')
                ->orderBy('t.nama')
                ->limit(5)
                ->get([
                    't.id',
                    't.nama as tps',
                    'd.nama as desa',
                    'k.nama as kecamatan',
                ])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'label' => $row->tps.' - '.$row->desa,
                    'meta' => 'Kec. '.$row->kecamatan,
                ]);

            $reviewTpsQuery = $this->applyScope(
                DB::table('rekap_headers as h')
                    ->join('tps as t', 't.id', '=', 'h.tps_id')
                    ->join('desas as d', 'd.id', '=', 't.desa_id')
                    ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                    ->whereIn('h.jenis', $jenisList)
                    ->where('h.status', 'perlu_dicek'),
                $scope
            );
            $reviewTpsCount = (clone $reviewTpsQuery)->count('h.id');
            $reviewTps = $reviewTpsQuery
                ->orderByDesc('h.updated_at')
                ->orderBy('k.nama')
                ->orderBy('d.nama')
                ->orderBy('t.nama')
                ->limit(5)
                ->get([
                    't.id',
                    't.nama as tps',
                    'd.nama as desa',
                    'k.nama as kecamatan',
                    'h.jenis',
                    'h.catatan_internal',
                ])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'label' => $row->tps.' - '.$row->desa,
                    'meta' => (RekapHeader::JENIS_LABELS[$row->jenis] ?? strtoupper($row->jenis)).' | Kec. '.$row->kecamatan,
                    'note' => $row->catatan_internal,
                ]);

            $totalSuara = $this->totalConfiguredPartySuara($jenisList, $scope);
            $regions = $this->regionPerformance($jenisList, $scope);
        }

        return [
            'total_suara_partai' => $totalSuara,
            'total_tps' => $totalTps,
            'input_tps' => $inputTps,
            'missing_tps_count' => max(0, $totalTps - $inputTps),
            'missing_tps' => $missingTps->toArray(),
            'review_tps_count' => $reviewTpsCount,
            'review_tps' => $reviewTps->toArray(),
            'active_jenis_count' => count($jenisList),
            'regions' => $regions,
        ];
    }

    private function regionPerformance(array $jenisList, array $scope): array
    {
        $config = match ($scope['type']) {
            'kabupaten' => [
                'label' => 'Kecamatan',
                'id' => 'k.id',
                'name' => 'k.nama',
                'query' => DB::table('kecamatans as k'),
            ],
            'kecamatan' => [
                'label' => 'Desa',
                'id' => 'd.id',
                'name' => 'd.nama',
                'query' => DB::table('desas as d')
                    ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                    ->where('k.id', $scope['id']),
            ],
            'desa' => [
                'label' => 'TPS',
                'id' => 't.id',
                'name' => 't.nama',
                'query' => DB::table('tps as t')
                    ->join('desas as d', 'd.id', '=', 't.desa_id')
                    ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                    ->where('d.id', $scope['id']),
            ],
            default => null,
        };

        if (! $config) {
            return [
                'label' => null,
                'strong' => [],
                'weak' => [],
            ];
        }

        $regions = $config['query']
            ->orderByRaw($config['name'])
            ->get([
                DB::raw($config['id'].' as id'),
                DB::raw($config['name'].' as label'),
            ]);

        if ($regions->isEmpty()) {
            return [
                'label' => $config['label'],
                'strong' => [],
                'weak' => [],
            ];
        }

        $partaiTotals = $this->configuredPartySuaraQuery($jenisList, $scope)
            ->selectRaw($config['id'].' as region_id, SUM(s.suara) as total_suara')
            ->groupBy('region_id')
            ->pluck('total_suara', 'region_id');

        $calegTotals = $this->configuredPartyCalegSuaraQuery($jenisList, $scope)
            ->selectRaw($config['id'].' as region_id, SUM(s.suara) as total_suara')
            ->groupBy('region_id')
            ->pluck('total_suara', 'region_id');

        $rows = $regions
            ->map(function ($region) use ($partaiTotals, $calegTotals) {
                $id = (int) $region->id;

                return [
                    'id' => $id,
                    'label' => $region->label,
                    'suara' => (int) ($partaiTotals[$id] ?? 0) + (int) ($calegTotals[$id] ?? 0),
                ];
            })
            ->values();

        return [
            'label' => $config['label'],
            'strong' => $rows
                ->sortByDesc('suara')
                ->take(3)
                ->values()
                ->toArray(),
            'weak' => $rows
                ->sortBy('suara')
                ->take(3)
                ->values()
                ->toArray(),
        ];
    }

    private function totalConfiguredPartySuara(array $jenisList, array $scope): int
    {
        return (int) $this->configuredPartySuaraQuery($jenisList, $scope)->sum('s.suara')
            + (int) $this->configuredPartyCalegSuaraQuery($jenisList, $scope)->sum('s.suara');
    }

    private function configuredPartySuaraQuery(array $jenisList, array $scope): Builder
    {
        return $this->applyScope(
            PartyConfig::applyPartyQuery(DB::table('rekap_partai_suaras as s')
                ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                ->join('tps as t', 't.id', '=', 'h.tps_id')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->join('rekap_partais as p', 'p.id', '=', 's.partai_id')
                ->whereIn('h.jenis', $jenisList), 'p.nama_partai', 'p.nomor_urut'),
            $scope
        );
    }

    private function configuredPartyCalegSuaraQuery(array $jenisList, array $scope): Builder
    {
        return $this->applyScope(
            PartyConfig::applyPartyQuery(DB::table('rekap_caleg_suaras as s')
                ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                ->join('tps as t', 't.id', '=', 'h.tps_id')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->join('rekap_calegs as c', 'c.id', '=', 's.caleg_id')
                ->join('rekap_partais as p', 'p.id', '=', 'c.partai_id')
                ->whereIn('h.jenis', $jenisList), 'p.nama_partai', 'p.nomor_urut'),
            $scope
        );
    }

    private function partySection(string $jenis, array $scope, ?int $dapilId = null, ?string $dapilName = null): array
    {
        $partais = RekapPartai::query()
            ->where('jenis', $jenis)
            ->when($jenis === 'dprd_kab' && $dapilId, fn ($query) => $query->where('dapil_id', $dapilId))
            ->tap(fn ($query) => $this->onlyConfiguredParty($query))
            ->orderBy('nomor_urut')
            ->get(['id', 'nomor_urut', 'nama_partai']);

        if ($partais->isEmpty()) {
            return [
                'jenis' => $jenis,
                'title' => $this->partyTitle($jenis, $dapilName),
                'subtitle' => 'Data '.config('party.short_name').' belum tersedia',
                'scope' => $scope['label'],
                'rows' => [],
            ];
        }

        $partaiIds = $partais->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $totals = array_fill_keys($partaiIds, 0);

        $partaiRows = $this->applyScope(
            DB::table('rekap_partai_suaras as s')
                ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                ->join('tps as t', 't.id', '=', 'h.tps_id')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->where('h.jenis', $jenis),
            $scope
        )
            ->whereIn('s.partai_id', $partaiIds)
            ->select('s.partai_id', DB::raw('SUM(s.suara) as total_suara'))
            ->groupBy('s.partai_id')
            ->get();

        foreach ($partaiRows as $row) {
            $totals[(int) $row->partai_id] += (int) $row->total_suara;
        }

        $calegRows = $this->applyScope(
            DB::table('rekap_caleg_suaras as s')
                ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                ->join('tps as t', 't.id', '=', 'h.tps_id')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->join('rekap_calegs as c', 'c.id', '=', 's.caleg_id')
                ->where('h.jenis', $jenis),
            $scope
        )
            ->whereIn('c.partai_id', $partaiIds)
            ->select('c.partai_id', DB::raw('SUM(s.suara) as total_suara'))
            ->groupBy('c.partai_id')
            ->get();

        foreach ($calegRows as $row) {
            $totals[(int) $row->partai_id] += (int) $row->total_suara;
        }

        $allRows = $partais
            ->map(fn ($partai) => [
                'rank' => 0,
                'label' => $partai->nama_partai,
                'meta' => 'No. '.$partai->nomor_urut,
                'suara' => (int) ($totals[(int) $partai->id] ?? 0),
            ])
            ->sortByDesc('suara')
            ->values();
        $totalSuara = $allRows->sum('suara');
        $rows = $allRows
            ->take(5)
            ->values()
            ->map(function ($row, $index) use ($totalSuara) {
                $row['rank'] = $index + 1;
                $row['persentase'] = $totalSuara > 0 ? round(($row['suara'] / $totalSuara) * 100, 2) : 0;

                return $row;
            })
            ->toArray();

        return [
            'jenis' => $jenis,
            'title' => $this->partyTitle($jenis, $dapilName).' - '.config('party.short_name'),
            'subtitle' => 'Suara '.config('party.short_name'),
            'scope' => $scope['label'],
            'total_suara' => $totalSuara,
            'rows' => $rows,
        ];
    }

    private function dprdKabSections(array $scope): array
    {
        $dapils = Dapil::query()
            ->when($scope['dapil_id'], fn ($query) => $query->where('id', $scope['dapil_id']))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return $dapils
            ->map(fn ($dapil) => $this->calegSection('dprd_kab', $scope, (int) $dapil->id, $dapil->nama))
            ->toArray();
    }

    private function calegSection(string $jenis, array $scope, ?int $dapilId = null, ?string $dapilName = null): array
    {
        $partais = RekapPartai::with('calegs')
            ->where('jenis', $jenis)
            ->when($jenis === 'dprd_kab' && $dapilId, fn ($query) => $query->where('dapil_id', $dapilId))
            ->tap(fn ($query) => $this->onlyConfiguredParty($query))
            ->orderBy('nomor_urut')
            ->get();

        $calegs = $partais->flatMap(fn ($partai) => $partai->calegs->map(fn ($caleg) => [
            'id' => (int) $caleg->id,
            'label' => trim($caleg->nama_caleg.' No. '.$caleg->nomor_urut),
            'meta' => $partai->nama_partai,
        ]));

        if ($calegs->isEmpty()) {
            return [
                'jenis' => $jenis,
                'title' => $this->partyTitle($jenis, $dapilName).' - '.config('party.short_name'),
                'subtitle' => 'Caleg '.config('party.short_name').' belum tersedia',
                'scope' => $scope['label'],
                'rows' => [],
            ];
        }

        $calegIds = $calegs->pluck('id')->values()->all();
        $totals = $this->applyScope(
            DB::table('rekap_caleg_suaras as s')
                ->join('rekap_headers as h', 'h.id', '=', 's.rekap_id')
                ->join('tps as t', 't.id', '=', 'h.tps_id')
                ->join('desas as d', 'd.id', '=', 't.desa_id')
                ->join('kecamatans as k', 'k.id', '=', 'd.kecamatan_id')
                ->where('h.jenis', $jenis),
            $scope
        )
            ->whereIn('s.caleg_id', $calegIds)
            ->select('s.caleg_id', DB::raw('SUM(s.suara) as total_suara'))
            ->groupBy('s.caleg_id')
            ->pluck('total_suara', 'caleg_id');

        $allRows = $calegs
            ->map(fn ($caleg) => [
                'rank' => 0,
                'label' => $caleg['label'],
                'meta' => $caleg['meta'],
                'suara' => (int) ($totals[$caleg['id']] ?? 0),
            ])
            ->sortByDesc('suara')
            ->values();
        $totalSuara = $allRows->sum('suara');
        $rows = $allRows
            ->take(5)
            ->values()
            ->map(function ($row, $index) use ($totalSuara) {
                $row['rank'] = $index + 1;
                $row['persentase'] = $totalSuara > 0 ? round(($row['suara'] / $totalSuara) * 100, 2) : 0;

                return $row;
            })
            ->toArray();

        return [
            'jenis' => $jenis,
            'title' => $this->partyTitle($jenis, $dapilName).' - '.config('party.short_name'),
            'subtitle' => '5 caleg '.config('party.short_name').' teratas',
            'scope' => $scope['label'],
            'total_suara' => $totalSuara,
            'rows' => $rows,
        ];
    }

    private function onlyConfiguredParty(EloquentBuilder $query): EloquentBuilder
    {
        return PartyConfig::applyPartyQuery($query);
    }

    private function applyScope(Builder $query, array $scope): Builder
    {
        return match ($scope['type']) {
            'kecamatan' => $query->where('k.id', $scope['id']),
            'desa' => $query->where('d.id', $scope['id']),
            'tps' => $query->where('t.id', $scope['id']),
            default => $query,
        };
    }

    private function partyTitle(string $jenis, ?string $dapilName): string
    {
        if ($jenis === 'dprd_kab') {
            return 'DPRD Kab - '.($dapilName ?: 'Dapil');
        }

        return RekapHeader::JENIS_LABELS[$jenis] ?? strtoupper($jenis);
    }

    private function cacheParts(User $user, array $scope, array $activeJenis): array
    {
        return [
            'version' => 8,
            'user_role' => $user->role,
            'scope' => $scope,
            'active' => $activeJenis,
        ];
    }
}
