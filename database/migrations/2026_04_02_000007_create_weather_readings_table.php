<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE weather_readings (
                asset_id             SMALLINT NOT NULL REFERENCES assets(id),
                temp_c               NUMERIC(5,2) NOT NULL,
                temp_f               NUMERIC(5,2) NOT NULL,
                running_daily_max_c  NUMERIC(5,2) NOT NULL,
                source               VARCHAR(20) NOT NULL DEFAULT \'observed\',
                station_local_date   DATE NOT NULL,
                ts                   BIGINT NOT NULL
            )
        ');

        DB::statement("SELECT create_hypertable('weather_readings', 'ts', chunk_time_interval => 604800000)");
        DB::statement("SELECT set_integer_now_func('weather_readings', 'current_epoch_ms')");
        DB::statement("SELECT add_retention_policy('weather_readings', BIGINT '31536000000')");

        DB::statement('CREATE INDEX weather_readings_asset_ts   ON weather_readings (asset_id, ts DESC)');
        DB::statement('CREATE INDEX weather_readings_asset_date ON weather_readings (asset_id, station_local_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};
