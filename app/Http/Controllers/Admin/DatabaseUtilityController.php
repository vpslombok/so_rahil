<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
     */
    public function createBackup()
    {
        $backupPath = storage_path('app/backup');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filePath = $backupPath . DIRECTORY_SEPARATOR . $filename;

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
     * Jalankan migrate artisan command dari halaman admin
     */
    public function runMigration()
    {
        try {
            \Artisan::call('migrate', ['--force' => true]);
            return redirect()->route('admin.database.utility')->with('success', 'Migrasi database berhasil dijalankan.');
        } catch (\Exception $e) {
            return redirect()->route('admin.database.utility')->with('error', 'Gagal menjalankan migrasi: ' . $e->getMessage());
        }
    }
}
