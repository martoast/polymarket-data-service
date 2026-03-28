<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candles_1m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');
            $table->decimal('open_usd', 14, 2);
            $table->decimal('high_usd', 14, 2);
            $table->decimal('low_usd', 14, 2);
            $table->decimal('close_usd', 14, 2);
            $table->decimal('volume', 20, 8)->nullable();
            $table->bigInteger('ts');

            $table->unique(['asset_id', 'ts']);
        });

        Schema::table('candles_1m', function (Blueprint $table) {
            $table->index(['asset_id', 'ts']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT create_hypertable('candles_1m', 'ts', chunk_time_interval => 2592000000, if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candles_1m');
    }
};
