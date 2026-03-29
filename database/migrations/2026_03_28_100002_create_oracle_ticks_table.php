<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oracle_ticks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');
            $table->decimal('price_usd', 14, 2);
            $table->bigInteger('price_bp');
            $table->bigInteger('ts');
        });

        Schema::table('oracle_ticks', function (Blueprint $table) {
            $table->index(['asset_id', 'ts']);
        });

        // TimescaleDB hypertable (only runs on PostgreSQL)
        if (DB::getDriverName() === 'pgsql') {
            // Hypertable requires no unique index that excludes the partition column (ts)
            DB::statement("ALTER TABLE oracle_ticks DROP CONSTRAINT oracle_ticks_pkey");
            DB::statement("SELECT create_hypertable('oracle_ticks', 'ts', chunk_time_interval => 2592000000, if_not_exists => TRUE)");
            DB::statement("ALTER TABLE oracle_ticks SET (timescaledb.compress, timescaledb.compress_orderby = 'ts DESC')");
            DB::statement("SELECT add_compression_policy('oracle_ticks', BIGINT '604800000', if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_ticks');
    }
};
