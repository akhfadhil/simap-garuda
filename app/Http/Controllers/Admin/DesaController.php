<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Http\Request;

class DesaController extends Controller
{
    // Menampilkan daftar desa sesuai filter kecamatan.
    public function index(Request $request)
    {
        $kecamatans = Kecamatan::all();
        $desas = collect();

        if ($request->filled('kecamatan_id')) {
            $desas = Desa::with('kecamatan')
                ->where('kecamatan_id', $request->kecamatan_id)
                ->withCount('tps')
                ->latest()
                ->get();
        }

        return view('admin.wilayah.desa', compact('desas', 'kecamatans'));
    }

    // Menyimpan desa baru.
    public function store(Request $request)
    {
        $request->validate([
            'nama'         => 'required|string|max:100',
            'kecamatan_id' => 'required|exists:kecamatans,id',
        ]);
        Desa::create($request->only('nama', 'kecamatan_id'));
        return back()->with('success', 'Desa berhasil ditambahkan.');
    }

    // Memperbarui data desa.
    public function update(Request $request, Desa $desa)
    {
        $request->validate([
            'nama'         => 'required|string|max:100',
            'kecamatan_id' => 'required|exists:kecamatans,id',
        ]);
        $desa->update($request->only('nama', 'kecamatan_id'));
        return back()->with('success', 'Desa berhasil diupdate.');
    }

    // Menghapus desa.
    public function destroy(Desa $desa)
    {
        $desa->delete();
        return back()->with('success', 'Desa berhasil dihapus.');
    }
}
