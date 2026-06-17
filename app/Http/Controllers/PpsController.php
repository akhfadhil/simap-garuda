<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Desa;
use App\Models\Tps;
use App\Services\PartyScopeService;

class PpsController extends Controller
{
    public function __construct(private PartyScopeService $partyScope)
    {
    }

    // Menampilkan daftar TPS dalam desa kordes.
    public function dataTps()
    {
        $desa = $this->activeDesa();

        $tpsList = Tps::where('desa_id', $desa->id)
            ->with(['rekapHeaders', 'users' => fn($q) => $q->where('role', 'saksi_tps')])
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

        return redirect()->route('dashboard.saksi');
    }

    private function activeDesa(): Desa
    {
        return $this->partyScope->activeDesaFor(Auth::user());
    }
}
