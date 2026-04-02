<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Shared epoch-ms function used by all integer-ts hypertables
        DB::statement("
            CREATE OR REPLACE FUNCTION current_epoch_ms()
            RETURNS BIGINT LANGUAGE SQL STABLE AS \$\$ SELECT (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT \$\$
        ");

        DB::statement('
            CREATE TABLE oracle_ticks (
                asset_id  SMALLINT NOT NULL REFERENCES assets(id),
                price_usd NUMERIC(14,2) NOT NULL,
                price_bp  BIGINT NOT NULL,
                ts        BIGINT NOT NULL
            )
        ');

        DB::statement("SELECT create_hypertable('oracle_ticks', 'ts', chunk_time_interval => 86400000)");
        DB::statement("SELECT set_integer_now_func('oracle_ticks', 'current_epoch_ms')");
        DB::statement("ALTER TABLE oracle_ticks SET (timescaledb.compress, timescaledb.compress_orderby = 'ts DESC')");
        DB::statement("SELECT add_compression_policy('oracle_ticks', BIGINT '604800000')");
        DB::statement("SELECT add_retention_policy('oracle_ticks', BIGINT '15552000000')");

        DB::statement('CREATE INDEX oracle_ticks_asset_ts ON oracle_ticks (asset_id, ts DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_ticks');
    }
};
