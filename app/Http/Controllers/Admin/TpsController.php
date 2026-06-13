<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tps;
use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Http\Request;

class TpsController extends Controller
{
    // Menampilkan daftar TPS sesuai filter kecamatan/desa.
    public function index(Request $request)
    {
        $kecamatans = Kecamatan::with(['desas' => fn ($query) => $query->orderBy('nama')])
            ->orderBy('nama')
            ->get();

        $selectedKecamatanId = $request->integer('kecamatan_id') ?: null;
        $selectedDesaId = $request->integer('desa_id') ?: null;
        $filteredTps = collect();

        if ($selectedKecamatanId || $selectedDesaId) {
            $filteredTps = Tps::with('desa.kecamatan')
                ->when($selectedDesaId, fn ($query) => $query->where('desa_id', $selectedDesaId))
                ->when(! $selectedDesaId && $selectedKecamatanId, function ($query) use ($selectedKecamatanId) {
                    $query->whereHas('desa', fn ($desaQuery) => $desaQuery->where('kecamatan_id', $selectedKecamatanId));
                })
                ->orderBy('nama')
                ->get();
        }

        return view('admin.tps.index', compact('kecamatans', 'filteredTps', 'selectedKecamatanId', 'selectedDesaId'));
    }

    // Membuat TPS massal per desa dan melewati TPS yang sudah ada.
    public function store(Request $request)
    {
        $request->validate([
            'jumlah_tps' => 'required|array',
            'jumlah_tps.*' => 'nullable|integer|min:0|max:999',
        ]);

        $rows = collect($request->input('jumlah_tps'))
            ->map(fn ($jumlah) => (int) $jumlah)
            ->filter(fn ($jumlah) => $jumlah > 0);

        if ($rows->isEmpty()) {
            return back()
                ->withErrors(['jumlah_tps' => 'Isi minimal satu jumlah TPS.'])
                ->withInput();
        }

        $desas = Desa::whereIn('id', $rows->keys())->get()->keyBy('id');
        $created = 0;
        $processed = 0;

        foreach ($rows as $desaId => $jumlah) {
            if (! $desas->has($desaId)) {
                continue;
            }

            $processed++;

            for ($i = 1; $i <= $jumlah; $i++) {
                $nama = 'TPS ' . str_pad($i, 3, '0', STR_PAD_LEFT);

                $exists = Tps::where('desa_id', $desaId)->where('nama', $nama)->exists();
                if (! $exists) {
                    Tps::create(['nama' => $nama, 'desa_id' => $desaId]);
                    $created++;
                }
            }
        }

        return back()->with('success', "Berhasil membuat {$created} TPS baru dari {$processed} desa. (TPS yang sudah ada dilewati)");
    }

    // Memperbarui nama TPS.
    public function update(Request $request, Tps $tps)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
        ]);

        $tps->update(['nama' => $request->nama]);

        return back()->with('success', "Nama TPS berhasil diubah menjadi \"{$tps->nama}\".");
    }

    // Menghapus TPS.
    public function destroy(Tps $tps)
    {
        $nama = $tps->nama;
        $tps->delete();
        return back()->with('success', "{$nama} berhasil dihapus.");
    }
}
