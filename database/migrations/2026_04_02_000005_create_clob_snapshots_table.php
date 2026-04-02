<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE clob_snapshots (
                market_id VARCHAR(120) NOT NULL REFERENCES markets(id),
                asset_id  SMALLINT NOT NULL REFERENCES assets(id),
                yes_ask   NUMERIC(6,4),
                yes_bid   NUMERIC(6,4),
                no_ask    NUMERIC(6,4),
                no_bid    NUMERIC(6,4),
                ts        BIGINT NOT NULL
            )
        ');

        DB::statement("SELECT create_hypertable('clob_snapshots', 'ts', chunk_time_interval => 86400000)");
        DB::statement("SELECT set_integer_now_func('clob_snapshots', 'current_epoch_ms')");
        DB::statement("ALTER TABLE clob_snapshots SET (timescaledb.compress, timescaledb.compress_orderby = 'ts DESC')");
        DB::statement("SELECT add_compression_policy('clob_snapshots', BIGINT '604800000')");
        DB::statement("SELECT add_retention_policy('clob_snapshots', BIGINT '15552000000')");

        DB::statement('CREATE INDEX clob_snapshots_market_ts ON clob_snapshots (market_id, ts DESC)');
        DB::statement('CREATE INDEX clob_snapshots_asset_ts  ON clob_snapshots (asset_id, ts DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('clob_snapshots');
    }
};
