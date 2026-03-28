<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('window_features', function (Blueprint $table) {
            $table->string('window_id')->primary();
            $table->foreign('window_id')->references('id')->on('windows');

            // Identity (denormalized)
            $table->string('asset', 10);
            $table->smallInteger('duration_sec');
            $table->bigInteger('open_ts');
            $table->bigInteger('close_ts');
            $table->string('outcome', 3); // YES | NO

            // Oracle distance at fixed time points before close
            $table->integer('oracle_dist_bp_at_5m')->nullable();
            $table->integer('oracle_dist_bp_at_4m')->nullable();
            $table->integer('oracle_dist_bp_at_3m')->nullable();
            $table->integer('oracle_dist_bp_at_2m')->nullable();
            $table->integer('oracle_dist_bp_at_90s')->nullable();
            $table->integer('oracle_dist_bp_at_1m')->nullable();
            $table->integer('oracle_dist_bp_at_45s')->nullable();
            $table->integer('oracle_dist_bp_at_30s')->nullable();
            $table->integer('oracle_dist_bp_at_15s')->nullable();
            $table->integer('oracle_dist_bp_final')->nullable();

            // Oracle volatility
            $table->integer('oracle_range_5m_bp')->nullable();
            $table->integer('oracle_range_10m_bp')->nullable();
            $table->integer('oracle_range_15m_bp')->nullable();
            $table->integer('oracle_range_5m_at_3m')->nullable();
            $table->integer('oracle_range_5m_at_2m')->nullable();

            // Oracle trend
            $table->integer('oracle_trend_5m_bp')->nullable();
            $table->integer('oracle_trend_10m_bp')->nullable();

            // Oracle density
            $table->integer('oracle_tick_count')->nullable();
            $table->integer('oracle_tick_gap_max_ms')->nullable();

            // Oracle crossings
            $table->smallInteger('oracle_crossings_total')->nullable();
            $table->smallInteger('oracle_crossings_last_5m')->nullable();
            $table->smallInteger('oracle_crossings_last_2m')->nullable();
            $table->integer('oracle_committed_since_ms')->nullable();

            // CLOB features
            $table->decimal('clob_yes_ask_final', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_min_5m', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_max_5m', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_avg_5m', 6, 4)->nullable();
            $table->decimal('clob_spread_final', 6, 4)->nullable();
            $table->integer('clob_snapshot_count')->nullable();
            $table->boolean('clob_in_lock_range')->nullable();

            // Context features
            $table->integer('oracle_range_30m_prior_bp')->nullable();
            $table->integer('oracle_trend_30m_prior_bp')->nullable();
            $table->smallInteger('oracle_crossings_30m_prior')->nullable();
            $table->smallInteger('hour_utc')->nullable();
            $table->smallInteger('day_of_week')->nullable();

            // Data quality flags
            $table->boolean('has_full_oracle_coverage')->default(false);
            $table->boolean('has_clob_coverage')->default(false);
            $table->boolean('recording_gap')->default(false);

            $table->bigInteger('computed_at');
        });

        Schema::table('window_features', function (Blueprint $table) {
            $table->index(['asset', 'outcome', 'open_ts']);
            $table->index('open_ts');
            $table->index(['hour_utc', 'asset']);
            $table->index(['oracle_range_5m_bp', 'asset']);
            $table->index('oracle_committed_since_ms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('window_features');
    }
};
