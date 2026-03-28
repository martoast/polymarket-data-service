<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clob_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('window_id');
            $table->foreign('window_id')->references('id')->on('windows');
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');
            $table->decimal('yes_ask', 6, 4)->nullable();
            $table->decimal('yes_bid', 6, 4)->nullable();
            $table->decimal('no_ask', 6, 4)->nullable();
            $table->decimal('no_bid', 6, 4)->nullable();
            $table->bigInteger('ts');
        });

        Schema::table('clob_snapshots', function (Blueprint $table) {
            $table->index(['window_id', 'ts']);
            $table->index(['asset_id', 'ts']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT create_hypertable('clob_snapshots', 'ts', chunk_time_interval => 2592000000, if_not_exists => TRUE)");
            DB::statement("SELECT add_compression_policy('clob_snapshots', BIGINT '604800000', if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clob_snapshots');
    }
};
