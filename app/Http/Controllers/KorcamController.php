<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Services\PartyScopeService;

class KorcamController extends Controller
{
    public function __construct(private PartyScopeService $partyScope)
    {
    }

    // Menampilkan daftar kordes/desa dalam kecamatan korcam.
    public function dataKordes()
    {
        $kecamatan = $this->activeKecamatan();

        $desas = Desa::where('kecamatan_id', $kecamatan->id)
            ->with(['tps.rekapHeaders', 'users' => fn($q) => $q->where('role', 'kordes')])
            ->get();

        return view('korcam.data-kordes', compact('desas'));
    }

    // Mengaktifkan mode lihat kordes untuk desa tertentu.
    public function viewKordes(Desa $desa)
    {
        $kecamatan = $this->activeKecamatan();

        abort_if($desa->kecamatan_id !== $kecamatan->id, 403);

        session([
            'admin_view_kecamatan_id' => $desa->kecamatan_id,
            'admin_view_desa_id' => $desa->id,
        ]);
        session()->forget('admin_view_tps_id');

        return redirect()->route('dashboard.kordes');
    }

    private function activeKecamatan(): Kecamatan
    {
        return $this->partyScope->activeKecamatanFor(Auth::user());
    }
}
