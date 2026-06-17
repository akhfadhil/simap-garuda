<?php

namespace App\Http\Controllers\Rekap;

use App\Exports\RekapSheetExport;
use App\Http\Controllers\Controller;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Models\Tps;
use App\Services\PartyScopeService;
use App\Services\RekapAdminCache;
use App\Support\PartyConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SaksiController extends Controller
{
    const JENIS = RekapHeader::LEGISLATIVE_TYPES;

    public function __construct(private PartyScopeService $partyScope)
    {
    }

    // Menampilkan daftar rekap milik TPS user.
    public function index()
    {
        $tps = $this->activeTps();
        $rekaps = RekapHeader::where('tps_id', $tps->id)
            ->get()->keyBy('jenis');

        return view('rekap.saksi.index', compact('tps', 'rekaps'));
    }

    // Memastikan jenis pemilihan sedang aktif.
    private function cekAktif(string $jenis): void
    {
        abort_unless(in_array($jenis, self::JENIS, true), 404);
        $aktif = \App\Models\PemiluSetting::aktif();
        abort_if(! in_array($jenis, $aktif), 403, 'Jenis pemilu ini tidak aktif.');
    }

    // Menampilkan form input rekap untuk jenis pemilihan.
    public function form(string $jenis)
    {
        $this->cekAktif($jenis);
        abort_unless(in_array($jenis, self::JENIS), 404);
        $tps = $this->activeTps();
        $rekap = RekapHeader::where('tps_id', $tps->id)->where('jenis', $jenis)->first();
        $data = $this->getMasterData($jenis, $rekap, $tps);

        return view('rekap.saksi.form', compact('tps', 'jenis', 'rekap', 'data'));
    }

    // Menyimpan draft rekap atau langsung finalisasi.
    public function store(Request $request, string $jenis)
    {
        $user = Auth::user();
        $isAdminEdit = $user->role === 'admin_partai';

        abort_unless(in_array($user->role, ['saksi_tps', 'kordes', 'korcam', 'admin_partai'], true), 403, 'Akses ditolak.');

        $this->cekAktif($jenis);
        abort_unless(in_array($jenis, self::JENIS), 404);
        $tps = $this->activeTps();
        $request->validate([
            'suara_partai' => ['nullable', 'array'],
            'suara_partai.*' => ['nullable', 'integer', 'min:0'],
            'suara_caleg' => ['nullable', 'array'],
            'suara_caleg.*' => ['nullable', 'integer', 'min:0'],
            'finalisasi' => ['nullable', 'in:1'],
            'status_internal' => [$isAdminEdit ? 'required' : 'prohibited', 'string', 'in:draft,perlu_dicek,final'],
            'catatan_internal' => [$isAdminEdit ? 'nullable' : 'prohibited', 'string', 'max:2000'],
        ]);
        $this->guardConfiguredPartySuaraPayload($request, $jenis, $tps);

        $existing = RekapHeader::where('tps_id', $tps->id)->where('jenis', $jenis)->first();
        if ($existing && $existing->status === 'final' && ! $isAdminEdit) {
            return back()->with('error', 'Rekap sudah difinalisasi, tidak bisa diubah.');
        }

        DB::transaction(function () use ($request, $jenis, $tps, $existing, $isAdminEdit) {
            $status = $this->resolveStatus($request, $existing, $isAdminEdit);
            $headerData = [
                'dpt_lk' => 0,
                'dpt_pr' => 0,
                'pengguna_dpt_lk' => 0,
                'pengguna_dpt_pr' => 0,
                'pengguna_dptb_lk' => 0,
                'pengguna_dptb_pr' => 0,
                'pengguna_dpk_lk' => 0,
                'pengguna_dpk_pr' => 0,
                'ss_diterima' => 0,
                'ss_digunakan' => 0,
                'ss_rusak' => 0,
                'ss_sisa' => 0,
                'disabilitas_lk' => 0,
                'disabilitas_pr' => 0,
                'suara_tidak_sah' => 0,
            ];

            $rekap = RekapHeader::updateOrCreate(
                ['tps_id' => $tps->id, 'jenis' => $jenis],
                array_merge($headerData, [
                    'diinput_oleh' => Auth::id(),
                    'status' => $status,
                    'catatan_internal' => $isAdminEdit ? $request->input('catatan_internal') : ($existing?->catatan_internal),
                    'difinalisasi_at' => $status === 'final' ? ($existing?->difinalisasi_at ?? now()) : null,
                ])
            );

            foreach ($request->input('suara_partai', []) as $partai_id => $suara) {
                $rekap->partaiSuaras()->updateOrCreate(['partai_id' => $partai_id], ['suara' => (int) $suara]);
            }
            foreach ($request->input('suara_caleg', []) as $caleg_id => $suara) {
                $rekap->calegSuaras()->updateOrCreate(['caleg_id' => $caleg_id], ['suara' => (int) $suara]);
            }

            if (request('finalisasi') == '1') {
                $rekap->update(['status' => 'final', 'difinalisasi_at' => now()]);
                try {
                    $tps->load('desa.kecamatan');
                    app(\App\Services\RekapExportService::class)->handleFinalisasi($tps, $jenis);
                } catch (\Exception $e) {
                    \Log::error('Auto export gagal: '.$e->getMessage());
                }
            }
        });
        RekapAdminCache::flushAggregate();

        $label = RekapHeader::JENIS_LABELS[$jenis];
        if ($isAdminEdit) {
            return redirect(session()->pull('admin_rekap_return_url', route('admin.rekap.show', $jenis)))
                ->with('success', "Rekap {$label} berhasil diperbarui oleh admin.");
        }

        if (request('finalisasi') == '1') {
            return redirect()->route('rekap.index')->with('success', "Rekap {$label} berhasil difinalisasi.");
        }

        return redirect()->route('rekap.index')->with('success', "Rekap {$label} berhasil disimpan.");
    }

    public function finalisasi(string $jenis)
    {
        abort_unless(in_array(Auth::user()->role, ['saksi_tps', 'kordes', 'korcam'], true), 403, 'Akses ditolak.');
        $this->cekAktif($jenis);
        abort_unless(in_array($jenis, self::JENIS), 404);

        $tps = $this->activeTps();
        $rekap = RekapHeader::where('tps_id', $tps->id)->where('jenis', $jenis)->firstOrFail();

        if ($rekap->status === 'final') {
            return redirect()->route('rekap.index')->with('success', 'Rekap sudah difinalisasi.');
        }

        $rekap->update(['status' => 'final', 'difinalisasi_at' => now()]);
        RekapAdminCache::flushAggregate();

        return redirect()->route('rekap.index')->with('success', 'Rekap berhasil difinalisasi.');
    }

    // Mengekspor rekap TPS untuk jenis pemilihan.
    public function export(string $jenis)
    {
        $this->cekAktif($jenis);
        abort_unless(in_array($jenis, self::JENIS), 404);

        $tps = $this->activeTps();
        $rekap = RekapHeader::with(['partaiSuaras', 'calegSuaras'])
            ->where('tps_id', $tps->id)
            ->where('jenis', $jenis)
            ->get();

        $tpsList = collect([$tps]);
        $master = $this->getAllMaster($tps);
        $wilayah = $tps->nama.' — '.$tps->desa->nama;
        $label = RekapHeader::JENIS_LABELS[$jenis];
        $filename = 'Rekap_'.strtoupper($jenis).'_'.str_replace(' ', '_', $tps->nama).'.xlsx';

        $sheet = new RekapSheetExport(
            $jenis,
            $label,
            $rekap,
            $master,
            $tpsList,
            'saksi_tps',
            $wilayah
        );

        return Excel::download($sheet, $filename);
    }

    // Mengambil master data dan suara existing untuk form.
    private function getMasterData(string $jenis, ?RekapHeader $rekap, Tps $tps): array
    {
        $existingPartai = [];
        $existingCaleg = [];

        if ($rekap) {
            $existingPartai = $rekap->partaiSuaras->pluck('suara', 'partai_id')->toArray();
            $existingCaleg = $rekap->calegSuaras->pluck('suara', 'caleg_id')->toArray();
        }

        if ($jenis === 'dpr_ri') {
            return [
                'partais' => RekapPartai::with('calegs')
                    ->where('jenis', 'dpr_ri')
                    ->configuredParty()
                    ->orderBy('nomor_urut')
                    ->get(),
                'suara_partai' => $existingPartai,
                'suara_caleg' => $existingCaleg,
            ];
        }

        if ($jenis === 'dprd_prov') {
            return [
                'partais' => RekapPartai::with('calegs')
                    ->where('jenis', 'dprd_prov')
                    ->configuredParty()
                    ->orderBy('nomor_urut')
                    ->get(),
                'suara_partai' => $existingPartai,
                'suara_caleg' => $existingCaleg,
            ];
        }

        if ($jenis === 'dprd_kab') {
            $kecamatan = $tps->desa->kecamatan;
            $dapilId = $kecamatan->dapil_id;

            return [
                'partais' => RekapPartai::with('calegs')
                    ->where('jenis', 'dprd_kab')
                    ->configuredParty()
                    ->where('dapil_id', $dapilId)
                    ->orderBy('nomor_urut')
                    ->get(),
                'suara_partai' => $existingPartai,
                'suara_caleg' => $existingCaleg,
                'dapil' => $kecamatan->dapil,
            ];
        }

        return [];
    }

    // Mengambil semua master data untuk kebutuhan export.
    private function getAllMaster(Tps $tps): array
    {
        $kecamatan = $tps->desa->kecamatan;

        return [
            'dpr_ri' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->configuredParty()->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->configuredParty()->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->configuredParty()->where('dapil_id', $kecamatan->dapil_id)->orderBy('nomor_urut')->get()],
        ];
    }

    private function guardConfiguredPartySuaraPayload(Request $request, string $jenis, Tps $tps): void
    {
        $partais = RekapPartai::with('calegs')
            ->where('jenis', $jenis)
            ->configuredParty()
            ->when($jenis === 'dprd_kab', fn ($query) => $query->where('dapil_id', $tps->desa?->kecamatan?->dapil_id))
            ->get();
        $allowedPartaiIds = $partais->pluck('id')->map(fn ($id) => (string) $id)->all();
        $allowedCalegIds = $partais
            ->flatMap(fn ($partai) => $partai->calegs->pluck('id'))
            ->map(fn ($id) => (string) $id)
            ->all();

        $requestedPartaiIds = array_keys($request->input('suara_partai', []));
        $requestedCalegIds = array_keys($request->input('suara_caleg', []));

        abort_if(
            array_diff($requestedPartaiIds, $allowedPartaiIds) || array_diff($requestedCalegIds, $allowedCalegIds),
            403,
            'Input rekap hanya boleh untuk '.PartyConfig::name().' dan calegnya.'
        );
    }

    private function resolveStatus(Request $request, ?RekapHeader $existing, bool $isAdminEdit): string
    {
        if ($request->input('finalisasi') === '1') {
            return 'final';
        }

        if ($isAdminEdit) {
            return $request->input('status_internal', $existing?->status ?? 'draft');
        }

        return 'draft';
    }

    private function activeTps(): Tps
    {
        return $this->partyScope->activeTpsFor(Auth::user());
    }
}
