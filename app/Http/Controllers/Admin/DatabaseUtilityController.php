<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

class DatabaseUtilityController extends Controller
{
    /**
     * Display the database utility page with tables and their structures.
     */
    public function index(): View
    {
        $backups = [];
        $backupPath = storage_path('app/backup');
        if (is_dir($backupPath)) {
            $files = array_filter(scandir($backupPath), function ($file) use ($backupPath) {
                return is_file($backupPath . DIRECTORY_SEPARATOR . $file);
            });
            foreach ($files as $file) {
                $filePath = $backupPath . DIRECTORY_SEPARATOR . $file;
                $backups[] = [
                    'name' => $file,
                    'size' => formatBytes(filesize($filePath)),
                    'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }
            // Urutkan terbaru di atas
            usort($backups, function ($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });
        }
        return view('admin.database_utility.index', compact('backups'));
    }

    /**
     * Get table names - compatible with Laravel 11
     */
    protected function getTableNames(): array
    {
        try {
            // Untuk MySQL
            if (DB::connection()->getDriverName() === 'mysql') {
                $tables = DB::select('SHOW TABLES');
                $key = 'Tables_in_' . DB::connection()->getDatabaseName();
                return array_column($tables, $key);
            }

            // Untuk database lain (SQLite, PostgreSQL, SQL Server)
            return DB::connection()->getSchemaBuilder()->getAllTables();
        } catch (\Exception $e) {
            // Fallback jika semua metode gagal
            return [];
        }
    }

    /**
     * Get detailed information about table columns
     */
    protected function getTableColumnsDetails(string $tableName): array
    {
        $columns = [];
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                $dbColumns = DB::select("SHOW COLUMNS FROM `{$tableName}`");

                foreach ($dbColumns as $column) {
                    $columns[] = [
                        'name' => $column->Field,
                        'type' => $column->Type,
                        'nullable' => $column->Null === 'YES',
                        'key' => $column->Key,
                        'default' => $column->Default,
                        'extra' => $column->Extra,
                    ];
                }
            } else {
                // Fallback untuk non-MySQL databases
                $columns = DB::connection()->getSchemaBuilder()->getColumns($tableName);
            }
        } catch (\Exception $e) {
            // Simple fallback
            foreach (Schema::getColumnListing($tableName) as $column) {
                $columns[] = [
                    'name' => $column,
                    'type' => 'unknown',
                    'nullable' => true,
                    'key' => '',
                    'default' => null,
                    'extra' => ''
                ];
            }
        }

        return $columns;
    }

    /**
     * Get sample data from table (5 first rows)
     */
    protected function getTableSampleData(string $tableName): array
    {
        try {
            return DB::table($tableName)
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return (array)$item;
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Display table data with pagination
     */
    public function showTableData(string $tableName): View
    {
        if (!Schema::hasTable($tableName)) {
            abort(404, 'Tabel tidak ditemukan');
        }

        $columns = Schema::getColumnListing($tableName);
        $data = DB::table($tableName)->paginate(20);
        $tableStructure = $this->getTableColumnsDetails($tableName);

        return view('admin.database_utility.table_data', compact('tableName', 'columns', 'data', 'tableStructure'));
    }

    /**
     * Store a newly created table in storage.
     */
    public function storeTable(Request $request): RedirectResponse
    {
        $request->validate([
            'table_name' => 'required|string|regex:/^[a-zA-Z0-9_]+$/|max:64',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string|regex:/^[a-zA-Z0-9_]+$/|max:64',
            'columns.*.type' => 'required|string|in:string,integer,text,date,boolean,id,timestamps',
        ]);

        $tableName = $request->input('table_name');

        if (Schema::hasTable($tableName)) {
            return back()->with('error', "Tabel '{$tableName}' sudah ada.");
        }

        DB::beginTransaction();
        try {
            Schema::create($tableName, function (Blueprint $table) use ($request) {
                foreach ($request->input('columns') as $column) {
                    $columnName = $column['name'];
                    $columnType = $column['type'];

                    if ($columnType === 'id') {
                        $table->id($columnName);
                    } elseif ($columnType === 'timestamps') {
                        $table->timestamps();
                    } else {
                        $table->{$columnType}($columnName)->nullable();
                    }
                }
            });

            DB::commit();
            return back()->with('success', "Tabel '{$tableName}' berhasil dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Gagal membuat tabel '{$tableName}': " . $e->getMessage());
        }
    }

    /**
     * Remove the specified table from storage.
     */
    public function destroyTable(string $tableName): RedirectResponse
    {
        if (!Schema::hasTable($tableName)) {
            return back()->with('error', "Tabel '{$tableName}' tidak ditemukan.");
        }

        DB::beginTransaction();
        try {
            Schema::dropIfExists($tableName);
            DB::commit();
            return back()->with('success', "Tabel '{$tableName}' berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Gagal menghapus tabel '{$tableName}': " . $e->getMessage());
        }
    }

    /**
     * Backup database ke storage/app/backup
     *
     * Menggunakan spatie/db-dumper jika exec() tidak tersedia.
     * composer require spatie/db-dumper
     */
    public function createBackup()
    {
        $backupPath = storage_path('app/backup');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filePath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        // Cek jika SEMUA fungsi eksekusi proses eksternal dinonaktifkan
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
        $exec_disabled = !function_exists('exec') || in_array('exec', $disabled);
        $proc_open_disabled = !function_exists('proc_open') || in_array('proc_open', $disabled);

        if ($exec_disabled && $proc_open_disabled) {
            return redirect()->route('admin.database.utility')->with('error', 'Backup database otomatis tidak didukung di server ini karena fungsi exec() dan proc_open() dinonaktifkan. Silakan lakukan backup manual melalui phpMyAdmin/cPanel.');
        }

        // Jika exec tersedia, gunakan mysqldump
        if (!$exec_disabled) {
            $db = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            $command = "mysqldump --user={$user} --password={$pass} --host={$host} --port={$port} {$db} > \"{$filePath}\"";
            $result = null;
            $output = null;
            exec($command, $output, $result);

            if ($result === 0) {
                return redirect()->route('admin.database.utility')->with('success', 'Backup database berhasil: ' . $filename);
            } else {
                return redirect()->route('admin.database.utility')->with('error', 'Backup database gagal.');
            }
        }

        // Jika exec tidak tersedia, coba gunakan spatie/db-dumper (proc_open)
        try {
            if (class_exists('Spatie\\DbDumper\\Databases\\MySql')) {
                \Spatie\DbDumper\Databases\MySql::create()
                    ->setDbName(config('database.connections.mysql.database'))
                    ->setUserName(config('database.connections.mysql.username'))
                    ->setPassword(config('database.connections.mysql.password'))
                    ->setHost(config('database.connections.mysql.host'))
                    ->setPort(config('database.connections.mysql.port', 3306))
                    ->dumpToFile($filePath);
                return redirect()->route('admin.database.utility')->with('success', 'Backup database berhasil: ' . $filename);
            } else {
                return redirect()->route('admin.database.utility')->with('error', 'Backup database gagal: Package spatie/db-dumper belum terpasang. Jalankan composer require spatie/db-dumper');
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.database.utility')->with('error', 'Backup database gagal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus file backup database
     */
    public function deleteBackup($filename)
    {
        $backupPath = storage_path('app/backup/' . $filename);
        if (file_exists($backupPath)) {
            unlink($backupPath);
            return redirect()->route('admin.database.utility')->with('success', 'File backup berhasil dihapus.');
        } else {
            return redirect()->route('admin.database.utility')->with('error', 'File backup tidak ditemukan.');
        }
    }

    /**
     * Download file backup database
     */
    public function downloadBackup($filename)
    {
        $backupPath = storage_path('app/backup/' . $filename);
        if (file_exists($backupPath)) {
            return response()->download($backupPath);
        } else {
            return redirect()->route('admin.database.utility')->with('error', 'File backup tidak ditemukan.');
        }
    }

    /**
     * Jalankan migrasi database dari halaman admin.
     */
    public function runMigration()
    {
        try {
            $output = Artisan::call('migrate', ['--force' => true]);
            $tables = $this->getTableNames();
            $successMsg = 'Migrasi database berhasil dijalankan.';
            if (!empty($tables)) {
                $successMsg .= ' Tabel saat ini: ' . implode(', ', $tables);
            }
            return redirect()->back()->with('success', $successMsg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menjalankan migrasi: ' . $e->getMessage());
        }
    }

    /**
     * Upload file backup database ke storage/app/backup
     */
    public function uploadBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt|max:10240', // max 10MB
        ]);
        $file = $request->file('backup_file');
        $backupPath = storage_path('app/backup');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        $filename = 'upload_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientOriginalName());
        $file->move($backupPath, $filename);
        return redirect()->route('admin.database.utility')->with('success', 'File backup berhasil diupload: ' . $filename);
    }

    /**
     * Restore database dari file backup (.sql)
     * - Restore biasa: tidak drop tabel, error jika tabel sudah ada
     * - Force restore: drop semua tabel (kecuali migrations) sebelum restore
     */
    public function restoreBackup($filename, Request $request = null)
    {
        $backupPath = storage_path('app/backup/' . $filename);
        if (!file_exists($backupPath)) {
            return redirect()->route('admin.database.utility')->with('error', 'File backup tidak ditemukan.');
        }

        // Cek fungsi eksekusi
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
        $exec_disabled = !function_exists('exec') || in_array('exec', $disabled);
        $proc_open_disabled = !function_exists('proc_open') || in_array('proc_open', $disabled);
        if ($exec_disabled && $proc_open_disabled) {
            return redirect()->route('admin.database.utility')->with('error', 'Restore otomatis tidak didukung di server ini karena fungsi exec() dan proc_open() dinonaktifkan. Silakan restore manual via phpMyAdmin/cPanel.');
        }

        // Jika force, drop semua views dan tabel dulu
        $isForce = $request && $request->has('force');
        if ($isForce) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                // Drop all views first
                $views = DB::select("SELECT table_name FROM information_schema.views WHERE table_schema = ?", [config('database.connections.mysql.database')]);
                foreach ($views as $view) {
                    DB::statement("DROP VIEW IF EXISTS `{$view->table_name}`;");
                }
                // Drop all tables except migrations (ambil key dinamis)
                $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
                if (!empty($tables)) {
                    $tableKeys = array_keys((array)$tables[0]);
                    $nameKey = $tableKeys[0]; // kolom nama tabel
                    foreach ($tables as $table) {
                        $tableName = $table->$nameKey;
                        if ($tableName !== 'migrations') {
                            DB::statement("DROP TABLE IF EXISTS `{$tableName}`;");
                        }
                    }
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $e) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                return redirect()->route('admin.database.utility')->with('error', 'Gagal drop tabel/views sebelum restore: ' . $e->getMessage());
            }
        }

        // Restore hanya untuk MySQL
        $db = escapeshellarg(config('database.connections.mysql.database'));
        $user = escapeshellarg(config('database.connections.mysql.username'));
        $pass = config('database.connections.mysql.password');
        $host = escapeshellarg(config('database.connections.mysql.host'));
        $port = escapeshellarg(config('database.connections.mysql.port', 3306));
        $file = escapeshellarg($backupPath);

        $passOpt = $pass !== '' ? "-p" . escapeshellarg($pass) : '';
        $command = "mysql --user={$user} {$passOpt} --host={$host} --port={$port} {$db} < {$file} 2>&1";
        $output = [];
        $result = null;
        exec($command, $output, $result);

        if ($result === 0) {
            $msg = $isForce ? 'Force restore database berhasil dari file: ' : 'Restore database berhasil dari file: ';
            return redirect()->route('admin.database.utility')->with('success', $msg . $filename);
        } else {
            $errorMsg = $isForce ? 'Force restore database gagal.' : 'Restore database gagal.';
            if (!empty($output)) {
                $errorMsg .= ' Pesan error: ' . implode(' ', $output);
            }
            return redirect()->route('admin.database.utility')->with('error', $errorMsg);
        }
    }
}
