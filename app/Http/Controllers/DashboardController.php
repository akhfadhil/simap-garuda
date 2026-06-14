<?php
namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Tps;
use App\Services\DashboardElectionSummary;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Menampilkan dashboard admin Partai Garuda.
    public function admin(DashboardElectionSummary $summary)
    {
        $this->checkRole('admin');
        session()->forget(['admin_view_kecamatan_id', 'admin_view_desa_id', 'admin_view_tps_id']);

        return view('dashboard.admin', ['electionSummary' => $summary->forUser(Auth::user())]);
    }

    // Menampilkan dashboard Korcam sesuai kecamatan user.
    public function ppk(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewKecamatan = null;

        if ($user->role === 'admin') {
            abort_if(!session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');
            $viewKecamatan = Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        } else {
            $this->checkRole('ppk');
        }

        return view('dashboard.ppk', [
            'electionSummary' => $summary->forUser($user),
            'viewKecamatan' => $viewKecamatan,
            'isAdminView' => (bool) $viewKecamatan,
        ]);
    }

    // Menampilkan dashboard Kordes sesuai desa user.
    public function pps(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewDesa = null;

        if ($user->role === 'pps') {
            // Kordes membuka dashboard wilayahnya sendiri.
        } else {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $viewDesa = Desa::with('kecamatan')->findOrFail(session('admin_view_desa_id'));
            $this->authorizeDesaScope($viewDesa);
        }

        return view('dashboard.pps', [
            'electionSummary' => $summary->forUser($user),
            'viewDesa' => $viewDesa,
            'isAdminView' => (bool) $viewDesa,
        ]);
    }

    // Menampilkan dashboard Saksi TPS sesuai TPS user.
    public function kpps(DashboardElectionSummary $summary)
    {
        $user = Auth::user();
        $viewTps = null;

        if ($user->role === 'kpps') {
            // Saksi TPS membuka dashboard TPS miliknya sendiri.
        } else {
            abort_if(!session('admin_view_tps_id'), 403, 'Pilih TPS yang ingin dilihat.');
            $viewTps = Tps::with('desa.kecamatan')->findOrFail(session('admin_view_tps_id'));
            $this->authorizeTpsScope($viewTps);
        }

        return view('dashboard.kpps', [
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

    private function authorizeDesaScope(Desa $desa): void
    {
        $user = Auth::user();

        $allowed = match ($user->role) {
            'admin' => true,
            'ppk' => $desa->kecamatan_id === $user->kecamatan_id,
            default => false,
        };

        abort_if(!$allowed, 403, 'Akses ditolak.');
    }

    private function authorizeTpsScope(Tps $tps): void
    {
        $user = Auth::user();

        $allowed = match ($user->role) {
            'admin' => true,
            'ppk' => $tps->desa?->kecamatan_id === $user->kecamatan_id,
            'pps' => $tps->desa_id === $user->desa_id,
            default => false,
        };

        abort_if(!$allowed, 403, 'Akses ditolak.');
    }

    // Menyimpan mode lihat sebagai Korcam untuk admin.
    public function viewAsPpk(Kecamatan $kecamatan)
    {
        session([
            'admin_view_kecamatan_id' => $kecamatan->id,
        ]);
        session()->forget(['admin_view_desa_id', 'admin_view_tps_id']);

        return redirect()->route('dashboard.ppk');
    }

    // Menyimpan mode lihat sebagai Kordes untuk admin.
    public function viewAsPps(Desa $desa)
    {
        session([
            'admin_view_kecamatan_id' => $desa->kecamatan_id,
            'admin_view_desa_id' => $desa->id,
        ]);
        session()->forget('admin_view_tps_id');

        return redirect()->route('dashboard.pps');
    }

    // Menyimpan mode lihat sebagai Saksi TPS untuk admin.
    public function viewAsKpps(Tps $tps)
    {
        $tps->load('desa');
        session([
            'admin_view_kecamatan_id' => $tps->desa->kecamatan_id,
            'admin_view_desa_id' => $tps->desa_id,
            'admin_view_tps_id' => $tps->id,
        ]);

        return redirect()->route('dashboard.kpps');
    }
}
