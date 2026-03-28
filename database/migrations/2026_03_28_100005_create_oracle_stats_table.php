<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oracle_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');
            $table->bigInteger('ts');
            $table->smallInteger('bucket_sec'); // 60, 300, 600, 1800
            $table->bigInteger('high_bp');
            $table->bigInteger('low_bp');
            $table->bigInteger('open_bp');
            $table->bigInteger('close_bp');
            $table->smallInteger('tick_count');
        });

        Schema::table('oracle_stats', function (Blueprint $table) {
            $table->index(['asset_id', 'bucket_sec', 'ts']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT create_hypertable('oracle_stats', 'ts', chunk_time_interval => 2592000000, if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_stats');
    }
};
