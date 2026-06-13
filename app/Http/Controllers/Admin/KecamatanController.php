<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use Illuminate\Http\Request;

class KecamatanController extends Controller
{
    // Menampilkan daftar kecamatan.
    public function index()
    {
        $kecamatans = Kecamatan::withCount('desas')->latest()->get();
        return view('admin.wilayah.kecamatan', compact('kecamatans'));
    }

    // Menyimpan kecamatan baru.
    public function store(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:100|unique:kecamatans']);
        Kecamatan::create(['nama' => $request->nama]);
        return back()->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    // Memperbarui nama kecamatan.
    public function update(Request $request, Kecamatan $kecamatan)
    {
        $request->validate(['nama' => 'required|string|max:100|unique:kecamatans,nama,' . $kecamatan->id]);
        $kecamatan->update(['nama' => $request->nama]);
        return back()->with('success', 'Kecamatan berhasil diupdate.');
    }

    // Menghapus kecamatan.
    public function destroy(Kecamatan $kecamatan)
    {
        $kecamatan->delete();
        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }
}
