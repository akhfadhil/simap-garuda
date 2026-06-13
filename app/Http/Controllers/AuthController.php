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
            return redirect()->route('dashboard.' . Auth::user()->role);
        }
        return view('auth.login');
    }

    // Menampilkan halaman login khusus partai.
    public function showPartaiLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.' . Auth::user()->role);
        }

        return view('auth.partai-login');
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
            return redirect()->route('dashboard.' . $role);
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->withInput();
    }

    // Memproses login khusus akun partai.
    public function loginPartai(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => trim($request->username),
            'password' => trim($request->password),
        ];

        if (Auth::attempt($credentials, false) && Auth::user()->role === 'partai') {
            $request->session()->regenerate();
            $request->session()->forget([
                'admin_view_kecamatan_id',
                'admin_view_desa_id',
                'admin_view_tps_id',
            ]);

            return redirect()->route('dashboard.partai');
        }

        Auth::logout();

        return back()
            ->withErrors(['username' => 'Akun partai atau password salah.'])
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
}
