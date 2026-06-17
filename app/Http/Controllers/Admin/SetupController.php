<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dapil;
use App\Models\Kecamatan;
use App\Models\PemiluSetting;
use App\Models\RekapCaleg;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Support\PartyConfig;
use Illuminate\Http\Request;

class SetupController extends Controller
{
    private const LEGACY_NON_PARTY_TYPES = ['ppwp', 'gubernur', 'bupati', 'dpd'];

    // Menampilkan halaman setup master data pemilu.
    public function index()
    {
        $partaiDprRi = RekapPartai::with('calegs')->where('jenis', 'dpr_ri')->configuredParty()->orderBy('nomor_urut')->get();
        $partaiProv = RekapPartai::with('calegs')->where('jenis', 'dprd_prov')->configuredParty()->orderBy('nomor_urut')->get();
        $dapils = \App\Models\Dapil::with('kecamatans')->orderBy('nama')->get();
        $kecamatans = \App\Models\Kecamatan::with('dapil')->orderBy('nama')->get();
        $partaiKab = RekapPartai::with('calegs', 'dapil')
            ->where('jenis', 'dprd_kab')
            ->configuredParty()
            ->orderBy('dapil_id')
            ->orderBy('nomor_urut')
            ->get()
            ->groupBy(fn ($p) => (string) $p->dapil_id);
        $jenisOrder = array_flip(RekapHeader::LEGISLATIVE_TYPES);
        $pemiluSettings = PemiluSetting::whereIn('jenis', RekapHeader::LEGISLATIVE_TYPES)
            ->get()
            ->sortBy(fn (PemiluSetting $setting) => $jenisOrder[$setting->jenis] ?? PHP_INT_MAX)
            ->keyBy('jenis');

        return view('admin.setup.index', compact(
            'partaiDprRi', 'partaiProv', 'partaiKab', 'dapils', 'kecamatans', 'pemiluSettings'
        ));
    }

    // Menyimpan status aktif/nonaktif jenis pemilihan.
    public function updatePemiluSettings(Request $request)
    {
        $jenisList = RekapHeader::LEGISLATIVE_TYPES;

        foreach ($jenisList as $jenis) {
            PemiluSetting::updateOrCreate(['jenis' => $jenis], [
                'is_active' => $request->has("jenis_{$jenis}"),
            ]);
        }

        PemiluSetting::whereIn('jenis', self::LEGACY_NON_PARTY_TYPES)->update(['is_active' => false]);

        return back()->with('success', 'Pengaturan jenis pemilu berhasil disimpan.');
    }

    // Menyimpan partai untuk DPR/DPRD.
    public function storePartai(Request $request)
    {
        $request->validate([
            'jenis' => 'required|in:dpr_ri,dprd_prov,dprd_kab',
            'partais' => 'required|array',
            'partais.*.nomor_urut' => 'nullable|integer|min:1|max:999',
            'partais.*.nama_partai' => 'nullable|string|max:200',
            'dapil_id' => 'required_if:jenis,dprd_kab|nullable|exists:dapils,id',
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
            ->map(fn ($row) => [
                'nomor_urut' => trim((string) ($row['nomor_urut'] ?? '')),
                'nama_partai' => trim((string) ($row['nama_partai'] ?? '')),
            ])
            ->filter(fn ($row) => $row['nomor_urut'] !== '' && $row['nama_partai'] !== '')
            ->values();

        if ($validRows->isEmpty()) {
            return back()
                ->withErrors(['partais' => 'Isi minimal satu baris partai.'])
                ->withInput();
        }

        $invalidRows = $validRows->reject(fn ($row) => $this->isConfiguredPartyRow($row));

        if ($invalidRows->isNotEmpty()) {
            return back()
                ->withErrors(['partais' => PartyConfig::appName().' hanya menerima '.PartyConfig::name().' nomor urut '.(config('party.historical_numbers.2024') ?? '-').'.'])
                ->withInput();
        }

        foreach ($validRows as $row) {
            $dapilId = $request->jenis === 'dprd_kab' ? $request->dapil_id : null;

            RekapPartai::updateOrCreate(
                [
                    'jenis' => $request->jenis,
                    'nomor_urut' => (int) $row['nomor_urut'],
                    'dapil_id' => $dapilId,
                ],
                ['nama_partai' => config('party.name')]
            );
        }

        return back()->with('success', config('party.name').' berhasil disimpan.');
    }

    // Menghapus partai beserta calegnya.
    public function destroyPartai(RekapPartai $partai)
    {
        if ($partai->isConfiguredParty()) {
            return back()->with('error', PartyConfig::name().' adalah partai utama dan tidak bisa dihapus dari '.PartyConfig::appName().'.');
        }

        $partai->delete();

        return back()->with('success', 'Partai dan caleg-calegnya dihapus.');
    }

    // Menyimpan caleg pada partai tertentu.
    public function storeCaleg(Request $request, RekapPartai $partai)
    {
        abort_unless($partai->isConfiguredParty(), 403, 'Caleg hanya bisa ditambahkan untuk '.PartyConfig::name().'.');

        $request->validate(['nomor_urut' => 'required|integer', 'nama_caleg' => 'required|string|max:200']);
        $partai->calegs()->create($request->only('nomor_urut', 'nama_caleg'));

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

    private function isConfiguredPartyRow(array $row): bool
    {
        return PartyConfig::matchesSubmittedParty($row['nomor_urut'], $row['nama_partai']);
    }
}
