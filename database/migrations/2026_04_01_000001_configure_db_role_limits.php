<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Applies per-role PostgreSQL settings that survive container restarts but
 * would be lost if volumes are nuked and the DB is rebuilt from scratch.
 *
 * statement_timeout: kills any query that runs longer than 30 seconds.
 * This is the primary defence against runaway full-table scans crashing the server.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = config('database.connections.pgsql.username', 'sail');
        DB::statement("ALTER ROLE {$role} SET statement_timeout = '30s'");
    }

    public function down(): void
    {
        $role = config('database.connections.pgsql.username', 'sail');
        DB::statement("ALTER ROLE {$role} RESET statement_timeout");
    }
};
