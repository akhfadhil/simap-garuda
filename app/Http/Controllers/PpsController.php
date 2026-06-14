<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Desa;
use App\Models\Tps;

class PpsController extends Controller
{
    // Menampilkan daftar TPS dalam desa kordes.
    public function dataTps()
    {
        $desa = $this->activeDesa();

        $tpsList = Tps::where('desa_id', $desa->id)
            ->with(['rekapHeaders', 'users' => fn($q) => $q->where('role', 'kpps')])
            ->get();

        return view('pps.data-tps', compact('tpsList'));
    }

    // Mengaktifkan mode lihat saksi TPS untuk TPS tertentu.
    public function viewTps(Tps $tps)
    {
        $desa = $this->activeDesa();

        abort_if($tps->desa_id !== $desa->id, 403);

        session([
            'admin_view_kecamatan_id' => $desa->kecamatan_id,
            'admin_view_desa_id' => $tps->desa_id,
            'admin_view_tps_id' => $tps->id,
        ]);

        return redirect()->route('dashboard.kpps');
    }

    private function activeDesa(): Desa
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            return Desa::findOrFail(session('admin_view_desa_id'));
        }

        if ($user->role === 'ppk') {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $desa = Desa::findOrFail(session('admin_view_desa_id'));
            abort_if($desa->kecamatan_id !== $user->kecamatan_id, 403, 'Akses ditolak.');

            return $desa;
        }

        abort_if(!$user->desa_id, 403, 'Akun belum di-assign ke Desa.');

        return Desa::findOrFail($user->desa_id);
    }
}
