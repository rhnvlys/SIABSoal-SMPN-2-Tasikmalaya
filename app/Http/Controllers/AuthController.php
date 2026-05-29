<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LogAktivitas;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->dashboardRouteName() ?? 'dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Cek status user
            if ($user->status !== 'aktif') {
                Auth::logout();
                return back()->with('error', 'Akun Anda nonaktif. Silakan hubungi administrator.');
            }

            $dashboardRoute = $user->dashboardRouteName();
            if (!$dashboardRoute) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('error', 'Role pengguna tidak valid. Hubungi administrator.');
            }

            $request->session()->regenerate();

            LogAktivitas::catat('Login ke sistem', 'Auth');

            return redirect()->route($dashboardRoute)
                ->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()->with('error', 'Username atau password salah.')
                     ->withInput($request->only('username'));
    }

    /**
     * Proses logout
     */
    public function logout(Request $request)
    {
        LogAktivitas::catat('Logout dari sistem', 'Auth');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
