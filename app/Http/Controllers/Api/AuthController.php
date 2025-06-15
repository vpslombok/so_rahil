<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255', // Opsional, untuk menamai token
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Username belum terdaftar.'
            ], 401); // 401 Unauthorized atau 404 Not Found juga bisa dipertimbangkan
        }

        // Tambahkan pengecekan role di sini
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Login untuk admin tidak diizinkan melalui API ini.'
            ], 403); // 403 Forbidden
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password salah.'
            ], 401); // 401 Unauthorized
        }

        $deviceName = $request->post('device_name', $request->userAgent());
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'status' => 'success',
            'status_code' => 200, // Menambahkan status code 200
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username, // Sesuaikan dengan field user Anda
            ]
        ], 200); // Menambahkan status 200 secara eksplisit
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Menghapus token saat ini yang digunakan untuk otentikasi
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
