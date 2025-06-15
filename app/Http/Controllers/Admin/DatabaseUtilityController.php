<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DatabaseUtilityController extends Controller
{
    /**
     * Display the database utility page with tables and their structures.
     */
    public function index(): View
    {
        $tables = [];
        $tableDetails = [];
        
        try {
            // Cara mendapatkan daftar tabel di Laravel 11
            $tables = $this->getTableNames();
            
            // Filter out Laravel internal tables
            $excludedTables = ['migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens', 'sessions'];
            $tables = array_filter($tables, function ($table) use ($excludedTables) {
                return !in_array($table, $excludedTables);
            });
            
            sort($tables);

            // Get details for each table
            foreach ($tables as $table) {
                $tableDetails[$table] = [
                    'columns' => Schema::getColumnListing($table),
                    'columnsDetails' => $this->getTableColumnsDetails($table),
                    'rowCount' => DB::table($table)->count(),
                    'sampleData' => $this->getTableSampleData($table),
                ];
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengambil data database: ' . $e->getMessage());
        }

        return view('admin.database_utility.index', compact('tables', 'tableDetails'));
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
}