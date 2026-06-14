<?php

namespace App\Http\Controllers\Rekap;

use App\Exports\RekapSheetExport;
use App\Http\Controllers\Controller;
use App\Models\RekapBupatiCalon;
use App\Models\RekapDpdCalon;
use App\Models\RekapGubernurCalon;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Models\RekapPpwpCalon;
use App\Models\Tps;
use App\Services\RekapAdminCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KppsController extends Controller
{
    const JENIS = RekapHeader::LEGISLATIVE_TYPES;

    // Menampilkan daftar rekap milik TPS user.
    public function index()
    {
        $tps = $this->activeTps();
        $rekaps = RekapHeader::where('tps_id', $tps->id)
            ->get()->keyBy('jenis');

        return view('rekap.kpps.index', compact('tps', 'rekaps'));
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

        return view('rekap.kpps.form', compact('tps', 'jenis', 'rekap', 'data'));
    }

    // Menyimpan draft rekap atau langsung finalisasi.
    public function store(Request $request, string $jenis)
    {
        $user = Auth::user();
        $isAdminEdit = $user->role === 'admin';

        abort_unless(in_array($user->role, ['kpps', 'admin'], true), 403, 'Akses ditolak.');

        $this->cekAktif($jenis);
        abort_unless(in_array($jenis, self::JENIS), 404);
        $tps = $this->activeTps();

        $existing = RekapHeader::where('tps_id', $tps->id)->where('jenis', $jenis)->first();
        if ($existing && $existing->status === 'final' && ! $isAdminEdit) {
            return back()->with('error', 'Rekap sudah difinalisasi, tidak bisa diubah.');
        }

        DB::transaction(function () use ($request, $jenis, $tps, $existing, $isAdminEdit) {
            $status = request('finalisasi') == '1'
                ? 'final'
                : (($isAdminEdit && $existing?->status === 'final') ? 'final' : 'draft');
            $suratSuaraDigunakan =
                $request->integer('pengguna_dpt_lk') +
                $request->integer('pengguna_dpt_pr') +
                $request->integer('pengguna_dptb_lk') +
                $request->integer('pengguna_dptb_pr') +
                $request->integer('pengguna_dpk_lk') +
                $request->integer('pengguna_dpk_pr');
            $suratSuaraSisa = max(
                0,
                $request->integer('ss_diterima') - $suratSuaraDigunakan - $request->integer('ss_rusak')
            );
            $headerData = $request->only([
                'dpt_lk', 'dpt_pr',
                'pengguna_dpt_lk', 'pengguna_dpt_pr',
                'pengguna_dptb_lk', 'pengguna_dptb_pr',
                'pengguna_dpk_lk', 'pengguna_dpk_pr',
                'ss_diterima', 'ss_rusak',
                'disabilitas_lk', 'disabilitas_pr',
                'suara_tidak_sah',
            ]);
            $headerData['ss_digunakan'] = $suratSuaraDigunakan;
            $headerData['ss_sisa'] = $suratSuaraSisa;

            $rekap = RekapHeader::updateOrCreate(
                ['tps_id' => $tps->id, 'jenis' => $jenis],
                array_merge($headerData, ['diinput_oleh' => Auth::id(), 'status' => $status])
            );

            if ($jenis === 'ppwp') {
                foreach ($request->input('suara', []) as $calon_id => $suara) {
                    $rekap->ppwpSuaras()->updateOrCreate(['calon_id' => $calon_id], ['suara' => (int) $suara]);
                }
            } elseif ($jenis === 'gubernur') {
                foreach ($request->input('suara', []) as $calon_id => $suara) {
                    $rekap->gubernurSuaras()->updateOrCreate(['calon_id' => $calon_id], ['suara' => (int) $suara]);
                }
            } elseif ($jenis === 'bupati') {
                foreach ($request->input('suara', []) as $calon_id => $suara) {
                    $rekap->bupatiSuaras()->updateOrCreate(['calon_id' => $calon_id], ['suara' => (int) $suara]);
                }
            } elseif ($jenis === 'dpd') {
                foreach ($request->input('suara', []) as $calon_id => $suara) {
                    $rekap->dpdSuaras()->updateOrCreate(['calon_id' => $calon_id], ['suara' => (int) $suara]);
                }
            } else {
                foreach ($request->input('suara_partai', []) as $partai_id => $suara) {
                    $rekap->partaiSuaras()->updateOrCreate(['partai_id' => $partai_id], ['suara' => (int) $suara]);
                }
                foreach ($request->input('suara_caleg', []) as $caleg_id => $suara) {
                    $rekap->calegSuaras()->updateOrCreate(['caleg_id' => $caleg_id], ['suara' => (int) $suara]);
                }
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
        abort_if(Auth::user()->role !== 'kpps', 403, 'Akses ditolak.');
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
        $relations = match ($jenis) {
            'ppwp' => ['ppwpSuaras'],
            'gubernur' => ['gubernurSuaras'],
            'bupati' => ['bupatiSuaras'],
            'dpd' => ['dpdSuaras'],
            default => ['partaiSuaras', 'calegSuaras'],
        };
        $rekap = RekapHeader::with($relations)
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
            'kpps',
            $wilayah
        );

        return Excel::download($sheet, $filename);
    }

    // Mengambil master data dan suara existing untuk form.
    private function getMasterData(string $jenis, ?RekapHeader $rekap, Tps $tps): array
    {
        $existingSuara = [];
        $existingPartai = [];
        $existingCaleg = [];

        if ($rekap) {
            $existingPartai = $rekap->partaiSuaras->pluck('suara', 'partai_id')->toArray();
            $existingCaleg = $rekap->calegSuaras->pluck('suara', 'caleg_id')->toArray();
        }

        if ($jenis === 'ppwp') {
            if ($rekap) {
                $existingSuara = $rekap->ppwpSuaras->pluck('suara', 'calon_id')->toArray();
            }

            return [
                'calons' => RekapPpwpCalon::orderBy('nomor_urut')->get(),
                'suara' => $existingSuara,
            ];
        }

        if ($jenis === 'gubernur') {
            if ($rekap) {
                $existingSuara = $rekap->gubernurSuaras->pluck('suara', 'calon_id')->toArray();
            }

            return [
                'calons' => RekapGubernurCalon::orderBy('nomor_urut')->get(),
                'suara' => $existingSuara,
            ];
        }

        if ($jenis === 'bupati') {
            if ($rekap) {
                $existingSuara = $rekap->bupatiSuaras->pluck('suara', 'calon_id')->toArray();
            }

            return [
                'calons' => RekapBupatiCalon::orderBy('nomor_urut')->get(),
                'suara' => $existingSuara,
            ];
        }

        if ($jenis === 'dpd') {
            if ($rekap) {
                $existingSuara = $rekap->dpdSuaras->pluck('suara', 'calon_id')->toArray();
            }

            return [
                'calons' => RekapDpdCalon::orderBy('nomor_urut')->get(),
                'suara' => $existingSuara,
            ];
        }

        if ($jenis === 'dpr_ri') {
            return [
                'partais' => RekapPartai::with('calegs')
                    ->where('jenis', 'dpr_ri')
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
            'ppwp' => ['calons' => RekapPpwpCalon::orderBy('nomor_urut')->get()],
            'gubernur' => ['calons' => RekapGubernurCalon::orderBy('nomor_urut')->get()],
            'bupati' => ['calons' => RekapBupatiCalon::orderBy('nomor_urut')->get()],
            'dpd' => ['calons' => RekapDpdCalon::orderBy('nomor_urut')->get()],
            'dpr_ri' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->orderBy('nomor_urut')->get()],
            'dprd_prov' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->orderBy('nomor_urut')->get()],
            'dprd_kab' => ['partais' => RekapPartai::with('calegs')->where('jenis', 'dprd_kab')->where('dapil_id', $kecamatan->dapil_id)->orderBy('nomor_urut')->get()],
        ];
    }

    private function activeTps(): Tps
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'ppk', 'pps'], true)) {
            abort_if(! session('admin_view_tps_id'), 403, 'Pilih TPS yang ingin dilihat.');
            $tps = Tps::with('desa.kecamatan.dapil')->findOrFail(session('admin_view_tps_id'));

            $allowed = match ($user->role) {
                'admin' => true,
                'ppk' => $tps->desa?->kecamatan_id === $user->kecamatan_id,
                'pps' => $tps->desa_id === $user->desa_id,
                default => false,
            };

            abort_if(! $allowed, 403, 'Akses ditolak.');

            return $tps;
        }

        abort_if(! $user->tps_id, 403, 'Akun belum di-assign ke TPS.');

        return Tps::with('desa.kecamatan.dapil')->findOrFail($user->tps_id);
    }
}
