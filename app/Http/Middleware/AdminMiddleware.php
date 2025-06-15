<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Pastikan Auth di-import
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ambil nama peran admin dari konfigurasi, default ke 'admin' jika tidak ditemukan
        $adminRoleName = config('custom.admin_role', 'admin');

        // Periksa apakah user sudah login dan memiliki role 'admin'
        if (Auth::check() && Auth::user()->role === $adminRoleName) {
            return $next($request);
        }

        // Jika tidak, redirect atau berikan response error
        // Anda bisa redirect ke halaman login, halaman utama, atau halaman 'unauthorized'
        // return redirect('home')->with('error', 'Anda tidak memiliki akses admin.');
        // Atau, jika ini adalah API, Anda bisa mengembalikan response JSON
        // return response()->json(['message' => 'Unauthorized'], 403);
        // Atau, cara paling umum untuk web, abort dengan error 403 (Forbidden)
        // Gunakan helper terjemahan untuk pesan error
        abort(403, __('messages.admin_unauthorized_access'));
    }
}
