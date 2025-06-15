<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Request sudah ada, tidak perlu diubah
use Illuminate\Support\Facades\Auth;
use App\Models\FlutterAppVersion; // Menggunakan model yang benar

class LoginController extends Controller
{
    /**
     * Menampilkan form login.
     */
    public function showLoginForm()
    {
        // Jika sudah login, redirect ke dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $active_flutter_app = null;

        // Ambil versi aplikasi Flutter yang aktif
        if (class_exists(FlutterAppVersion::class)) {
            $active_flutter_app = FlutterAppVersion::where('is_active', true)->first();
        }
        return view('auth.login', compact('active_flutter_app'));
    }

    /**
     * Menangani upaya login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard'); // Redirect ke dashboard atau halaman tujuan
        }

        return back()->withErrors([
            'username' => 'Kombinasi username dan password tidak cocok.',
        ])->onlyInput('username')->with('error_message', 'Login gagal. Periksa kembali username dan password Anda.');
    }

    /**
     * Menangani proses logout.
     * (Rute logout Anda saat ini menggunakan closure, ini contoh jika ingin dipindah ke controller)
     */
    // public function logout(Request $request)
    // {
    //     Auth::logout();
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();
    //     return redirect('/');
    // }
}
