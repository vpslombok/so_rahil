<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\FlutterAppVersion;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FlutterAppController extends Controller
{
    protected $apkStoragePath = 'flutter_apks';

    /**
     * Display the Flutter app management page.
     *
     * @return \Illuminate\View\View
     */
    public function manager()
    {
        $versions = FlutterAppVersion::orderBy('created_at', 'desc')->get();
        return view('admin.flutter_app.manager', compact('versions'));
    }

    /**
     * Handle the upload of a new Flutter APK.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'apk_file' => 'required|file|mimetypes:application/vnd.android.package-archive,application/octet-stream,application/zip|max:102400',
            'version_name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('flutter_app_versions', 'version_name') // Jika tidak menggunakan SoftDeletes, hapus withoutTrashed()
            ],
            'release_notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.flutter_app.manager')
                ->withErrors($validator, 'uploadForm') // Menggunakan error bag 'uploadForm'
                ->withInput()
                ->with('open_upload_modal', true); // Flag untuk membuka modal
        }

        $validatedData = $validator->validated();

        try {
            $file = $validatedData['apk_file']; // Ambil dari data yang sudah divalidasi
            $originalFileName = $file->getClientOriginalName();
            $fileName = Str::slug($validatedData['version_name']) . '_' . time() . '.' . $file->getClientOriginalExtension();

            $filePath = $file->storeAs($this->apkStoragePath, $fileName, 'local');

            if (!$filePath) {
                throw new \Exception("Gagal menyimpan file APK.");
            }
            FlutterAppVersion::create([
                'version_name' => $validatedData['version_name'],
                'file_name' => $originalFileName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'release_notes' => $validatedData['release_notes'],
                'is_active' => false,
            ]);

            Log::info('APK uploaded: ' . $validatedData['version_name'] . ' at path: ' . $filePath);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Aplikasi Flutter versi ' . $validatedData['version_name'] . ' berhasil diunggah.');
        } catch (\Exception $e) {
            Log::error('APK upload error: ' . $e->getMessage());
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal mengunggah aplikasi: ' . $e->getMessage())
                ->with('open_upload_modal', true) // Buka modal juga jika ada exception lain
                ->withInput(); // Bawa input lama kembali
        }
    }

    /**
     * Handle the deletion of a Flutter APK.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $request->validate(['version_id' => 'required|exists:flutter_app_versions,id']);

        try {
            $version = FlutterAppVersion::findOrFail($request->input('version_id'));

            if (Storage::disk('local')->exists($version->file_path)) {
                Storage::disk('local')->delete($version->file_path);
            }

            $versionName = $version->version_name;
            $version->delete();

            Log::info('APK deleted: ' . $versionName);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Aplikasi versi ' . $versionName . ' berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('APK delete error: ' . $e->getMessage());
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal menghapus aplikasi: ' . $e->getMessage());
        }
    }

    /**
     * Handle the download of a Flutter APK.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Request $request)
    {
        $request->validate(['version_id' => 'required|exists:flutter_app_versions,id']);

        try {
            $version = FlutterAppVersion::findOrFail($request->input('version_id'));

            if (Storage::disk('local')->exists($version->file_path)) {
                return Storage::disk('local')->download($version->file_path, $version->file_name);
            }

            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'File aplikasi untuk versi ' . $version->version_name . ' tidak ditemukan di storage.');
        } catch (\Exception $e) {
            Log::error('APK download error: ' . $e->getMessage());
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal mengunduh aplikasi: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get available versions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function versions()
    {
        $activeVersion = FlutterAppVersion::where('is_active', true)
            ->first(['version_name', 'release_notes', 'file_path', 'created_at']);

        if ($activeVersion) {
            return response()->json($activeVersion);
        }
        return response()->json(['message' => 'No active version found.'], 404);
    }

    /**
     * Set the active version of the Flutter app.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setActiveVersion(Request $request)
    {
        $request->validate(['version_id' => 'required|exists:flutter_app_versions,id']);

        try {
            FlutterAppVersion::where('is_active', true)->update(['is_active' => false]);

            $versionToActivate = FlutterAppVersion::find($request->input('version_id'));
            $versionToActivate->is_active = true;
            $versionToActivate->save();

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi ' . $versionToActivate->version_name . ' berhasil diaktifkan.');
        } catch (\Exception $e) {
            Log::error('Set active version error: ' . $e->getMessage());
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal mengaktifkan versi: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a specific version of the Flutter app.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deactivateVersion(Request $request)
    {
        $request->validate(['version_id' => 'required|exists:flutter_app_versions,id']);

        try {
            $versionToDeactivate = FlutterAppVersion::findOrFail($request->input('version_id'));
            $versionToDeactivate->is_active = false;
            $versionToDeactivate->save();

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi ' . $versionToDeactivate->version_name . ' berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            Log::error('Deactivate version error: ' . $e->getMessage());
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal menonaktifkan versi: ' . $e->getMessage());
        }
    }

    /**
     * Handle the public download of an active Flutter APK.
     *
     * @param  \App\Models\FlutterAppVersion $version
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function publicDownload(FlutterAppVersion $version) // Menggunakan route model binding
    {
        try {
            // Pastikan versi yang diminta memang ada dan memiliki file_path
            if (!$version->file_path || !Storage::disk('local')->exists($version->file_path)) {
                Log::warning('Public download attempt for non-existent file or path for version ID: ' . $version->id . ', version name: ' . $version->version_name . ', path: ' . $version->file_path);
                abort(404, 'File aplikasi tidak ditemukan atau tidak dapat diunduh saat ini.');
            }
            return Storage::disk('local')->download($version->file_path, $version->file_name);
        } catch (\Exception $e) {
            Log::error('Public APK download error for version ID ' . $version->id . ': ' . $e->getMessage());
            // Sama seperti di atas, hindari redirect ke login untuk rute publik.
            abort(500, 'Gagal mengunduh aplikasi. Silakan coba lagi nanti.');
        }
    }

    /**
     * Menyediakan informasi versi aplikasi terbaru untuk Flutter API.
     * Endpoint ini akan diakses oleh aplikasi Flutter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestAppVersion(Request $request)
    {
        $activeApp = FlutterAppVersion::where('is_active', true)
            ->orderBy('created_at', 'desc') // Mengambil yang terbaru jika ada >1 aktif
            ->first();

        if (!$activeApp) {
            return response()->json([
                'message' => 'Tidak ada versi aplikasi aktif yang ditemukan.',
                'update_available' => false,
            ], 404); // Not Found
        }

        $downloadUrl = null;
        try {
            $downloadUrl = route('app.public_download', ['version' => $activeApp->version_name]);
        } catch (\Exception $e) {
            Log::error("Gagal membuat URL unduhan untuk aplikasi Flutter: " . $e->getMessage());
            // Jangan kirim detail error ke client
            return response()->json(['message' => 'Terjadi kesalahan internal.', 'update_available' => false], 500);
        }

        return response()->json([
            'message' => 'Versi aplikasi terbaru tersedia.',
            'update_available' => true,
            'data' => [
                'version_name' => $activeApp->version_name,
                'version_code' => $activeApp->version_code ?? null, // Tambahkan jika ada 
                'release_notes' => $activeApp->release_notes,
                'download_url' => $downloadUrl,
                'file_size' => $activeApp->file_size,
            ]
        ], 200);
    }
}
