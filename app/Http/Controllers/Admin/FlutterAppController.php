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
     * Handle the upload of a new Flutter APK (now via Google Drive link).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file_name' => 'required|url|max:500',
            'version_name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('flutter_app_versions', 'version_name')
            ],
            'release_notes' => 'nullable|string|max:5000',
        ]);

        \Log::info('FlutterAppManager: upload attempt', [
            'user_id' => optional($request->user())->id,
            'version_name' => $request->version_name,
            'file_name' => $request->file_name,
            'ip' => $request->ip(),
        ]);

        if ($validator->fails()) {
            \Log::warning('FlutterAppManager: upload validation failed', [
                'user_id' => optional($request->user())->id,
                'errors' => $validator->errors()->all(),
                'ip' => $request->ip(),
            ]);
            return redirect()->route('admin.flutter_app.manager')
                ->withErrors($validator, 'uploadForm')
                ->withInput()
                ->with('open_upload_modal', true);
        }

        $validatedData = $validator->validated();

        try {
            FlutterAppVersion::create([
                'version_name' => $validatedData['version_name'],
                'file_name' => $validatedData['file_name'],
                'release_notes' => $validatedData['release_notes'],
                'is_active' => false,
                'file_name' => $validatedData['file_name'], // file_name diisi link Google Drive
                'file_path' => $validatedData['file_name'], // file_path diisi link Google Drive
            ]);

            \Log::info('FlutterAppManager: version created', [
                'user_id' => optional($request->user())->id,
                'version_name' => $validatedData['version_name'],
                'file_name' => $validatedData['file_name'],
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi aplikasi Flutter ' . $validatedData['version_name'] . ' berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('FlutterAppManager: upload exception', [
                'user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal menambah versi aplikasi: ' . $e->getMessage())
                ->with('open_upload_modal', true)
                ->withInput();
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

        \Log::info('FlutterAppManager: delete attempt', [
            'user_id' => optional($request->user())->id,
            'version_id' => $request->version_id,
            'ip' => $request->ip(),
        ]);

        try {
            $version = FlutterAppVersion::findOrFail($request->input('version_id'));
            $versionName = $version->version_name;
            $version->delete();

            \Log::info('FlutterAppManager: version deleted', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'version_name' => $versionName,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi aplikasi ' . $versionName . ' berhasil dihapus.');
        } catch (\Exception $e) {
            \Log::error('FlutterAppManager: delete exception', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal menghapus versi aplikasi: ' . $e->getMessage());
        }
    }

    /**
     * Handle the download of a Flutter APK.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */

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

        \Log::info('FlutterAppManager: set active attempt', [
            'user_id' => optional($request->user())->id,
            'version_id' => $request->version_id,
            'ip' => $request->ip(),
        ]);

        try {
            FlutterAppVersion::where('is_active', true)->update(['is_active' => false]);

            $versionToActivate = FlutterAppVersion::find($request->input('version_id'));
            $versionToActivate->is_active = true;
            $versionToActivate->save();

            \Log::info('FlutterAppManager: set active success', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'version_name' => $versionToActivate->version_name,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi ' . $versionToActivate->version_name . ' berhasil diaktifkan.');
        } catch (\Exception $e) {
            \Log::error('FlutterAppManager: set active exception', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
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

        \Log::info('FlutterAppManager: deactivate attempt', [
            'user_id' => optional($request->user())->id,
            'version_id' => $request->version_id,
            'ip' => $request->ip(),
        ]);

        try {
            $versionToDeactivate = FlutterAppVersion::findOrFail($request->input('version_id'));
            $versionToDeactivate->is_active = false;
            $versionToDeactivate->save();

            \Log::info('FlutterAppManager: deactivate success', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'version_name' => $versionToDeactivate->version_name,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi ' . $versionToDeactivate->version_name . ' berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            \Log::error('FlutterAppManager: deactivate exception', [
                'user_id' => optional($request->user())->id,
                'version_id' => $request->version_id,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal menonaktifkan versi: ' . $e->getMessage());
        }
    }

    /**
     * Handle the public download of an active Flutter APK.
     *
     * @param  \App\Models\FlutterAppVersion $version
     * @return \Illuminate\Http\RedirectResponse
     */
    public function publicDownload(FlutterAppVersion $version)
    {
        try {
            if ($version->file_path) {
                Log::info('Public download redirect to Google Drive', [
                    'version_id' => $version->id,
                    'version_name' => $version->version_name,
                    'file_name' => $version->file_path,
                ]);
                return redirect()->away($version->file_path);
            }
            Log::warning('Public download failed, no file_name', [
                'version_id' => $version->id,
                'version_name' => $version->version_name,
            ]);
            abort(404, 'Link Google Drive tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Public APK download error for version ID ' . $version->id . ': ' . $e->getMessage());
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
            $downloadUrl = route('app.public_download', ['version' => $activeApp->id]);
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

    /**
     * Update an existing Flutter app version (edit modal).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'version_id' => 'required|exists:flutter_app_versions,id',
            'file_name' => 'required|url|max:500',
            'version_name' => [
                'required',
                'string',
                'max:25',
                Rule::unique('flutter_app_versions', 'version_name')->ignore($request->version_id)
            ],
            'release_notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.flutter_app.manager')
                ->withErrors($validator, 'uploadForm')
                ->withInput()
                ->with('open_upload_modal', false);
        }

        $validated = $validator->validated();

        try {
            $version = FlutterAppVersion::findOrFail($validated['version_id']);
            $version->version_name = $validated['version_name'];
            $version->file_name = $validated['file_name'];
            $version->file_name = $validated['file_name'];
            $version->file_path = $validated['file_name'];
            $version->release_notes = $validated['release_notes'];
            $version->save();

            return redirect()->route('admin.flutter_app.manager')
                ->with('success_message_flutter_app', 'Versi aplikasi berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->route('admin.flutter_app.manager')
                ->with('error_message_flutter_app', 'Gagal memperbarui versi aplikasi: ' . $e->getMessage());
        }
    }
}
