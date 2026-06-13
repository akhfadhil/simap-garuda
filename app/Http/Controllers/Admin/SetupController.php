<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RekapPpwpCalon;
use App\Models\RekapDpdCalon;
use App\Models\RekapPartai;
use App\Models\RekapCaleg;
use Illuminate\Http\Request;
use App\Models\Dapil;
use App\Models\Kecamatan;

class SetupController extends Controller
{
    // Menampilkan halaman setup master data pemilu.
    public function index()
    {
        $ppwpCalons  = RekapPpwpCalon::orderBy('nomor_urut')->get();
        $dpdCalons   = RekapDpdCalon::orderBy('nomor_urut')->get();
        $partaiDprRi = RekapPartai::with('calegs')->where('jenis','dpr_ri')->orderBy('nomor_urut')->get();
        $partaiProv  = RekapPartai::with('calegs')->where('jenis','dprd_prov')->orderBy('nomor_urut')->get();
        $dapils      = \App\Models\Dapil::with('kecamatans')->orderBy('nama')->get();
        $kecamatans  = \App\Models\Kecamatan::with('dapil')->orderBy('nama')->get();
        $partaiKab   = RekapPartai::with('calegs','dapil')
                        ->where('jenis','dprd_kab')
                        ->orderBy('dapil_id')
                        ->orderBy('nomor_urut')
                        ->get()
                        ->groupBy(fn($p) => (string) $p->dapil_id);
        $gubernurCalons = \App\Models\RekapGubernurCalon::orderBy('nomor_urut')->get();
        $bupatiCalons   = \App\Models\RekapBupatiCalon::orderBy('nomor_urut')->get();

        $pemiluSettings = \App\Models\PemiluSetting::orderByRaw("FIELD(jenis,'ppwp','gubernur','bupati','dpd','dpr_ri','dprd_prov','dprd_kab')")->get()->keyBy('jenis');

        return view('admin.setup.index', compact(
            'ppwpCalons','gubernurCalons','bupatiCalons','dpdCalons',
            'partaiDprRi','partaiProv','partaiKab','dapils','kecamatans','pemiluSettings'
        ));
    }

    // Menyimpan status aktif/nonaktif jenis pemilihan.
    public function updatePemiluSettings(Request $request)
    {
        $jenisList = ['ppwp','gubernur','bupati','dpd','dpr_ri','dprd_prov','dprd_kab'];
        
        foreach ($jenisList as $jenis) {
            \App\Models\PemiluSetting::where('jenis', $jenis)->update([
                'is_active' => $request->has("jenis_{$jenis}")
            ]);
        }

        return back()->with('success', 'Pengaturan jenis pemilu berhasil disimpan.');
    }

    // Menyimpan batch paslon PPWP.
    public function storePpwp(Request $request)
    {
        return $this->storePaslonBatch($request, RekapPpwpCalon::class, 'Paslon PPWP berhasil ditambahkan.');
    }

    // Menghapus paslon PPWP.
    public function destroyPpwp(RekapPpwpCalon $calon)
    {
        $calon->delete();
        return back()->with('success', 'Paslon dihapus.');
    }

    // Menyimpan batch calon DPD.
    public function storeDpd(Request $request)
    {
        return $this->storeCalonBatch($request, RekapDpdCalon::class, 'Calon DPD berhasil ditambahkan.');
    }

    // Menghapus calon DPD.
    public function destroyDpd(RekapDpdCalon $calon)
    {
        $calon->delete();
        return back()->with('success', 'Calon DPD dihapus.');
    }

    // Menyimpan partai untuk DPR/DPRD.
    public function storePartai(Request $request)
    {
        $request->validate([
            'jenis'       => 'required|in:dpr_ri,dprd_prov,dprd_kab',
            'partais'     => 'required|array',
            'partais.*.nomor_urut'  => 'nullable|integer|min:1|max:999',
            'partais.*.nama_partai' => 'nullable|string|max:200',
            'dapil_id'    => 'required_if:jenis,dprd_kab|nullable|exists:dapils,id',
        ]);

        $rows = collect($request->input('partais', []));
        $hasIncompleteRow = $rows->contains(function ($row) {
            $nomor = trim((string) ($row['nomor_urut'] ?? ''));
            $nama = trim((string) ($row['nama_partai'] ?? ''));

            return ($nomor === '') xor ($nama === '');
        });

        if ($hasIncompleteRow) {
            return back()
                ->withErrors(['partais' => 'Lengkapi nomor urut dan nama partai pada setiap baris yang diisi.'])
                ->withInput();
        }

        $validRows = $rows
            ->map(fn($row) => [
                'nomor_urut' => trim((string) ($row['nomor_urut'] ?? '')),
                'nama_partai' => trim((string) ($row['nama_partai'] ?? '')),
            ])
            ->filter(fn($row) => $row['nomor_urut'] !== '' && $row['nama_partai'] !== '')
            ->values();

        if ($validRows->isEmpty()) {
            return back()
                ->withErrors(['partais' => 'Isi minimal satu baris partai.'])
                ->withInput();
        }

        foreach ($validRows as $row) {
            RekapPartai::create([
                'jenis' => $request->jenis,
                'nomor_urut' => (int) $row['nomor_urut'],
                'nama_partai' => $row['nama_partai'],
                'dapil_id' => $request->jenis === 'dprd_kab' ? $request->dapil_id : null,
            ]);
        }

        return back()->with('success', 'Partai berhasil ditambahkan.');
    }

    // Menghapus partai beserta calegnya.
    public function destroyPartai(RekapPartai $partai)
    {
        $partai->delete();
        return back()->with('success', 'Partai dan caleg-calegnya dihapus.');
    }

    // Menyimpan caleg pada partai tertentu.
    public function storeCaleg(Request $request, RekapPartai $partai)
    {
        $request->validate(['nomor_urut' => 'required|integer', 'nama_caleg' => 'required|string|max:200']);
        $partai->calegs()->create($request->only('nomor_urut','nama_caleg'));
        return back()->with('success', 'Caleg berhasil ditambahkan.');
    }

    // Menghapus caleg.
    public function destroyCaleg(RekapCaleg $caleg)
    {
        $caleg->delete();
        return back()->with('success', 'Caleg dihapus.');
    }

    // Menyimpan dapil baru.
    public function storeDapil(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100']);
        Dapil::create($request->only('nama'));
        return back()->with('success', 'Dapil berhasil ditambahkan.');
    }

    // Menghapus dapil.
    public function destroyDapil(Dapil $dapil)
    {
        $dapil->delete();
        return back()->with('success', 'Dapil dihapus.');
    }

    // Menghubungkan kecamatan ke dapil.
    public function assignDapil(Request $request)
    {
        $request->validate([
            'kecamatan_dapil' => 'required|array',
            'kecamatan_dapil.*' => 'nullable|exists:dapils,id',
        ]);

        foreach ($request->input('kecamatan_dapil', []) as $kecamatanId => $dapilId) {
            Kecamatan::whereKey($kecamatanId)->update([
                'dapil_id' => $dapilId ?: null,
            ]);
        }

        return back()->with('success', 'Dapil kecamatan berhasil diupdate.');
    }

    // Menyimpan batch paslon gubernur.
    public function storeGubernur(Request $request)
    {
        return $this->storePaslonBatch($request, \App\Models\RekapGubernurCalon::class, 'Paslon Gubernur berhasil ditambahkan.');
    }

    // Menghapus paslon gubernur.
    public function destroyGubernur(\App\Models\RekapGubernurCalon $calon)
    {
        $calon->delete();
        return back()->with('success', 'Paslon Gubernur dihapus.');
    }

    // Menyimpan batch paslon bupati.
    public function storeBupati(Request $request)
    {
        return $this->storePaslonBatch($request, \App\Models\RekapBupatiCalon::class, 'Paslon Bupati berhasil ditambahkan.');
    }

    // Menghapus paslon bupati.
    public function destroyBupati(\App\Models\RekapBupatiCalon $calon)
    {
        $calon->delete();
        return back()->with('success', 'Paslon Bupati dihapus.');
    }

    // Menyimpan beberapa paslon dari form batch.
    private function storePaslonBatch(Request $request, string $modelClass, string $successMessage)
    {
        $request->validate([
            'calons' => 'required|array',
            'calons.*.nomor_urut' => 'nullable|integer|min:1|max:99',
            'calons.*.nama_paslon' => 'nullable|string|max:200',
        ]);

        $rows = collect($request->input('calons', []));
        $hasIncompleteRow = $rows->contains(function ($row) {
            $nomor = trim((string) ($row['nomor_urut'] ?? ''));
            $nama = trim((string) ($row['nama_paslon'] ?? ''));

            return ($nomor === '') xor ($nama === '');
        });

        if ($hasIncompleteRow) {
            return back()
                ->withErrors(['calons' => 'Lengkapi nomor urut dan nama paslon pada setiap baris yang diisi.'])
                ->withInput();
        }

        $validRows = $rows
            ->map(fn($row) => [
                'nomor_urut' => trim((string) ($row['nomor_urut'] ?? '')),
                'nama_paslon' => trim((string) ($row['nama_paslon'] ?? '')),
            ])
            ->filter(fn($row) => $row['nomor_urut'] !== '' && $row['nama_paslon'] !== '')
            ->values();

        if ($validRows->isEmpty()) {
            return back()
                ->withErrors(['calons' => 'Isi minimal satu baris paslon.'])
                ->withInput();
        }

        foreach ($validRows as $row) {
            $modelClass::create([
                'nomor_urut' => (int) $row['nomor_urut'],
                'nama_paslon' => $row['nama_paslon'],
            ]);
        }

        return back()->with('success', $successMessage);
    }

    // Menyimpan beberapa calon dari form batch.
    private function storeCalonBatch(Request $request, string $modelClass, string $successMessage)
    {
        $request->validate([
            'calons' => 'required|array',
            'calons.*.nomor_urut' => 'nullable|integer|min:1|max:999',
            'calons.*.nama_calon' => 'nullable|string|max:200',
        ]);

        $rows = collect($request->input('calons', []));
        $hasIncompleteRow = $rows->contains(function ($row) {
            $nomor = trim((string) ($row['nomor_urut'] ?? ''));
            $nama = trim((string) ($row['nama_calon'] ?? ''));

            return ($nomor === '') xor ($nama === '');
        });

        if ($hasIncompleteRow) {
            return back()
                ->withErrors(['calons' => 'Lengkapi nomor urut dan nama calon pada setiap baris yang diisi.'])
                ->withInput();
        }

        $validRows = $rows
            ->map(fn($row) => [
                'nomor_urut' => trim((string) ($row['nomor_urut'] ?? '')),
                'nama_calon' => trim((string) ($row['nama_calon'] ?? '')),
            ])
            ->filter(fn($row) => $row['nomor_urut'] !== '' && $row['nama_calon'] !== '')
            ->values();

        if ($validRows->isEmpty()) {
            return back()
                ->withErrors(['calons' => 'Isi minimal satu baris calon.'])
                ->withInput();
        }

        foreach ($validRows as $row) {
            $modelClass::create([
                'nomor_urut' => (int) $row['nomor_urut'],
                'nama_calon' => $row['nama_calon'],
            ]);
        }

        return back()->with('success', $successMessage);
    }
}
