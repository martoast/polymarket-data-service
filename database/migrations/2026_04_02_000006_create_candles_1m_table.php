<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE candles_1m (
                asset_id  SMALLINT NOT NULL REFERENCES assets(id),
                open_usd  NUMERIC(14,2) NOT NULL,
                high_usd  NUMERIC(14,2) NOT NULL,
                low_usd   NUMERIC(14,2) NOT NULL,
                close_usd NUMERIC(14,2) NOT NULL,
                volume    NUMERIC(20,8),
                ts        BIGINT NOT NULL
            )
        ');

        DB::statement("SELECT create_hypertable('candles_1m', 'ts', chunk_time_interval => 86400000)");
        DB::statement("SELECT set_integer_now_func('candles_1m', 'current_epoch_ms')");
        DB::statement("SELECT add_retention_policy('candles_1m', BIGINT '15552000000')");

        DB::statement('CREATE UNIQUE INDEX candles_1m_asset_ts ON candles_1m (asset_id, ts)');
    }

    public function down(): void
    {
        Schema::dropIfExists('candles_1m');
    }
};
