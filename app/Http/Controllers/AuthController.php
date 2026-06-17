<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Menampilkan halaman login atau redirect jika sudah login.
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRoute(Auth::user()->role));
        }
        return view('auth.login');
    }

    // Memproses login dan mengarahkan user ke dashboard sesuai role.
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => trim($request->username),
            'password' => trim($request->password),
        ];

        if (Auth::attempt($credentials, false)) {
            $request->session()->regenerate();
            $request->session()->forget([
                'admin_view_kecamatan_id',
                'admin_view_desa_id',
                'admin_view_tps_id',
            ]);
            $role = Auth::user()->role;

            if (!in_array($role, ['admin_partai', 'korcam', 'kordes', 'saksi_tps'], true)) {
                Auth::logout();

                return back()
                    ->withErrors(['username' => 'Role akun ini sudah tidak aktif di '.config('party.app_name').'.'])
                    ->withInput();
            }

            return redirect()->route($this->dashboardRoute($role));
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->withInput();
    }

    // Menghapus session login user.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function dashboardRoute(string $role): string
    {
        return match ($role) {
            'admin_partai' => 'dashboard.admin_partai',
            'korcam' => 'dashboard.korcam',
            'kordes' => 'dashboard.kordes',
            'saksi_tps' => 'dashboard.saksi',
            default => 'login',
        };
    }
}
