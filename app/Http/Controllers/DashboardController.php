<?php
namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Tps;
use App\Services\DashboardElectionSummary;
use App\Services\PartyScopeService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private PartyScopeService $partyScope)
    {
    }

    // Menampilkan dashboard admin partai.
    public function admin(DashboardElectionSummary $summary)
    {
        $this->checkRole('admin_partai');
        session()->forget(['admin_view_kecamatan_id', 'admin_view_desa_id', 'admin_view_tps_id']);

        return view('dashboard.admin', ['electionSummary' => $summary->forUser(Auth::user())]);
    }

    // Menampilkan dashboard Korcam sesuai kecamatan user.
    public function korcam(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewKecamatan = null;

        if ($user->role === 'admin_partai') {
            abort_if(!session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');
            $viewKecamatan = Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        } else {
            $this->checkRole('korcam');
        }

        return view('dashboard.korcam', [
            'electionSummary' => $summary->forUser($user),
            'viewKecamatan' => $viewKecamatan,
            'isAdminView' => (bool) $viewKecamatan,
        ]);
    }

    // Menampilkan dashboard Kordes sesuai desa user.
    public function kordes(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewDesa = null;

        if ($user->role === 'kordes') {
            // Kordes membuka dashboard wilayahnya sendiri.
        } else {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $viewDesa = Desa::with('kecamatan')->findOrFail(session('admin_view_desa_id'));
            abort_if(! $this->partyScope->canAccessDesa($user, $viewDesa), 403, 'Akses ditolak.');
        }

        return view('dashboard.kordes', [
            'electionSummary' => $summary->forUser($user),
            'viewDesa' => $viewDesa,
            'isAdminView' => (bool) $viewDesa,
        ]);
    }

    // Menampilkan dashboard Saksi TPS sesuai TPS user.
    public function saksi(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewTps = null;

        if ($user->role === 'saksi_tps') {
            // Saksi TPS membuka dashboard TPS miliknya sendiri.
        } else {
            abort_if(!session('admin_view_tps_id'), 403, 'Pilih TPS yang ingin dilihat.');
            $viewTps = Tps::with('desa.kecamatan')->findOrFail(session('admin_view_tps_id'));
            abort_if(! $this->partyScope->canAccessTps($user, $viewTps), 403, 'Akses ditolak.');
        }

        return view('dashboard.saksi', [
            'electionSummary' => $summary->forUser($user),
            'viewTps' => $viewTps,
            'isAdminView' => (bool) $viewTps,
        ]);
    }

    // Memastikan user hanya membuka dashboard role miliknya.
    private function checkRole(string $role)
    {
        if (Auth::user()->role !== $role) abort(403, 'Akses ditolak.');
    }

    // Menyimpan mode lihat sebagai Korcam untuk admin.
    public function viewAsKorcam(Kecamatan $kecamatan)
    {
        session([
            'admin_view_kecamatan_id' => $kecamatan->id,
        ]);
        session()->forget(['admin_view_desa_id', 'admin_view_tps_id']);

        return redirect()->route('dashboard.korcam');
    }

    // Menyimpan mode lihat sebagai Kordes untuk admin.
    public function viewAsKordes(Desa $desa)
    {
        session([
            'admin_view_kecamatan_id' => $desa->kecamatan_id,
            'admin_view_desa_id' => $desa->id,
        ]);
        session()->forget('admin_view_tps_id');

        return redirect()->route('dashboard.kordes');
    }

    // Menyimpan mode lihat sebagai Saksi TPS untuk admin.
    public function viewAsSaksi(Tps $tps)
    {
        $tps->load('desa');
        session([
            'admin_view_kecamatan_id' => $tps->desa->kecamatan_id,
            'admin_view_desa_id' => $tps->desa_id,
            'admin_view_tps_id' => $tps->id,
        ]);

        return redirect()->route('dashboard.saksi');
    }
}
