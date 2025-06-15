<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // Tambahkan impor ini
use App\Http\Controllers\Controller; // Tambahkan baris ini

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Paginate the users, e.g., 10 users per page to match DataTable or your preference
            $users = User::orderBy('username', 'asc')->paginate(15); 
            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return redirect()->back()->with('error_message_user', 'Gagal memuat data user. Silakan coba lagi.');
        }
    }


    public function create()
    {
        return view('users.create');
        // Catatan: Jika semua operasi pembuatan user dilakukan via modal di halaman index,
        // method ini dan view 'users.create' mungkin tidak lagi diperlukan.
        // Anda bisa menghapusnya dan 'create' dari pengecualian Route::resource jika tidak digunakan.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' akan mencari field 'password_confirmation', min 8
            'role' => 'required|string|in:admin,user',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.users.index') // Sesuaikan dengan nama rute dari web.php
                        ->withErrors($validator, 'createUser') // Kirim error ke error bag 'createUser'
                        ->withInput()
                        ->with('open_create_modal', true); // Flag untuk membuka modal di view
        }

        $validatedData = $validator->validated();

        try {
            User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
            ]);
            return redirect()->route('admin.users.index')->with('success_message_user', 'User baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            // Mengembalikan ke index dengan error umum dan flag untuk modal
            return redirect()->route('admin.users.index')
                            ->withErrors(['general_create' => 'Gagal menambahkan user. Silakan coba lagi.'], 'createUser')
                            ->withInput()
                            ->with('open_create_modal', true);
        }
    }

    /**
     * Display the specified resource.
     *
     * Catatan: Biasanya tidak digunakan untuk manajemen user di halaman index,
     * tapi bisa berguna jika Anda ingin halaman detail user.
     * Jika tidak perlu, Anda bisa menghapus method ini dan 'show' dari Route::resource.
     */
    public function show(User $user)
    {
        // return view('users.show', compact('user'));
        return abort(404); // Atau redirect ke index jika tidak ada halaman show
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Sama seperti create, Anda mungkin perlu mengirimkan data tambahan
        return view('users.edit', compact('user'));
        // Catatan: Jika semua operasi edit user dilakukan via modal di halaman index,
        // method ini dan view 'users.edit' mungkin tidak lagi diperlukan.
        // Anda bisa menghapusnya dan 'edit' dari pengecualian Route::resource jika tidak digunakan.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed', // Password opsional saat update
            'role' => 'required|string|in:admin,user', // Sesuaikan dengan role yang ada
            // 'user_id_for_edit' => 'required|exists:users,id', // Validasi user_id jika dikirim dari form
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.users.index') // Sesuaikan dengan nama rute
                        ->withErrors($validator, 'editUser') // Kirim error ke error bag 'editUser'
                        ->withInput()
                        ->with('open_edit_modal', $user->id) // Kirim ID user yang gagal diedit
                        ->with('failed_edit_username', $user->username); // Kirim username untuk judul modal jika JS memerlukannya
        }

        $validatedData = $validator->validated();

        try {
            $dataToUpdate = [
                'name' => $validatedData['name'],
                'username' => $validatedData['username'],
                'role' => $validatedData['role'],
            ];

            if (!empty($validatedData['password'])) {
                $dataToUpdate['password'] = Hash::make($validatedData['password']);
            }

            $user->update($dataToUpdate);

            return redirect()->route('admin.users.index')->with('success_message_user', 'Data user berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error updating user ' . $user->id . ': ' . $e->getMessage());
            return redirect()->route('admin.users.index')
                            ->withErrors(['general_edit' => 'Gagal memperbarui data user. Silakan coba lagi.'], 'editUser')
                            ->withInput()
                            ->with('open_edit_modal', $user->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            // Mencegah admin menghapus dirinya sendiri
            if (auth()->id() === $user->id && $user->role === 'admin') {
                 return redirect()->route('admin.users.index')
                    ->with('error_message_user', 'Anda tidak dapat menghapus akun admin Anda sendiri.');
            }

            $username = $user->username; // Simpan username untuk pesan
            $user->delete();

            return redirect()->route('admin.users.index')->with('success_message_user', "User '{$username}' berhasil dihapus.");
        } catch (\Exception $e) {
            Log::error('Error deleting user ' . $user->id . ': ' . $e->getMessage());
            return redirect()->route('admin.users.index')->with('error_message_user', 'Gagal menghapus user. Silakan coba lagi.');
        }
    }
}
