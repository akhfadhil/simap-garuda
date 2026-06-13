<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Desa;
use App\Models\Kecamatan;

class PpkController extends Controller
{
    // Menampilkan daftar PPS/desa dalam kecamatan PPK.
    public function dataPps()
    {
        $kecamatan = $this->activeKecamatan();

        $desas = Desa::where('kecamatan_id', $kecamatan->id)
            ->with(['tps.dokumens', 'users' => fn($q) => $q->where('role', 'pps')])
            ->get();

        return view('ppk.data-pps', compact('desas'));
    }

    // Mengaktifkan mode lihat dokumen PPS untuk desa tertentu.
    public function viewPps(Desa $desa)
    {
        $kecamatan = $this->activeKecamatan();

        abort_if($desa->kecamatan_id !== $kecamatan->id, 403);

        session([
            'admin_view_kecamatan_id' => $desa->kecamatan_id,
            'admin_view_desa_id' => $desa->id,
        ]);
        session()->forget('admin_view_tps_id');

        return redirect()->route('dashboard.pps');
    }

    private function activeKecamatan(): Kecamatan
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            abort_if(!session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');
            return Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        }

        abort_if(!$user->kecamatan_id, 403, 'Akun belum di-assign ke Kecamatan.');

        return Kecamatan::findOrFail($user->kecamatan_id);
    }
}
