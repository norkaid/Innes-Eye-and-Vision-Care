<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Read-only database browser for Super Admins. Nothing can be changed here.
 * Framework tables that hold sessions, tokens or queued jobs are not listed,
 * and secret columns (password hashes, tokens) are never shown.
 */
class DatabaseController extends Controller
{
    private const HIDDEN_TABLES = [
        'sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sqlite_sequence',
    ];

    private const HIDDEN_COLUMNS = [
        'password', 'remember_token', 'token', 'payload', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    /** Binary columns (image files) are shown as a size instead of raw bytes. */
    private const BINARY_COLUMNS = ['media_contents.data'];

    public function index()
    {
        $tables = collect($this->tables())->map(fn (string $name) => [
            'name' => $name,
            'label' => Str::headline($name),
            'rows' => DB::table($name)->count(),
        ]);

        return view('admin.database.index', [
            'tables' => $tables,
            'driver' => DB::connection()->getDriverName(),
            'database' => DB::connection()->getDriverName() === 'sqlite' ? basename((string) DB::connection()->getDatabaseName()) : DB::connection()->getDatabaseName(),
        ]);
    }

    public function show(Request $request, string $table, AuditLogger $audit)
    {
        abort_unless(in_array($table, $this->tables(), true), 404);

        $columns = Schema::getColumnListing($table);
        $visible = array_values(array_diff($columns, self::HIDDEN_COLUMNS));
        $hidden = array_values(array_intersect($columns, self::HIDDEN_COLUMNS));

        $binary = array_values(array_filter($visible, fn ($c) => in_array("{$table}.{$c}", self::BINARY_COLUMNS, true)));
        $query = DB::table($table)->select(array_diff($visible, $binary));
        foreach ($binary as $column) {
            $query->selectRaw('length('.DB::getQueryGrammar()->wrap($column).') as '.DB::getQueryGrammar()->wrap($column));
        }
        if (in_array('id', $columns, true)) {
            $query->orderByDesc('id');
        }
        $rows = $query->paginate(50)->withQueryString();

        if ($rows->currentPage() === 1) {
            $audit->log('database.table_viewed', null, ['table' => $table]);
        }

        return view('admin.database.show', [
            'table' => $table,
            'label' => Str::headline($table),
            'columns' => $visible,
            'binary' => $binary,
            'hidden' => $hidden,
            'rows' => $rows,
        ]);
    }

    /** @return list<string> */
    private function tables(): array
    {
        $connection = DB::connection();
        $schema = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) ? $connection->getDatabaseName() : null;

        return collect(Schema::getTables($schema))
            ->when($schema, fn ($c) => $c->where('schema', $schema))
            ->pluck('name')
            ->reject(fn ($name) => in_array($name, self::HIDDEN_TABLES, true) || str_starts_with($name, 'sqlite_'))
            ->sort()->values()->all();
    }
}
