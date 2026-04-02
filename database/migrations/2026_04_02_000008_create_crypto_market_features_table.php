<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_market_features', function (Blueprint $table) {
            $table->string('market_id')->primary();
            $table->foreign('market_id')->references('id')->on('markets');

            $table->string('asset', 10);
            $table->unsignedInteger('duration_sec');
            $table->bigInteger('open_ts');
            $table->bigInteger('close_ts');
            $table->string('outcome', 3)->nullable();

            // Oracle distance from break price at various intervals
            $table->integer('oracle_dist_bp_at_5m')->nullable();
            $table->integer('oracle_dist_bp_at_4m')->nullable();
            $table->integer('oracle_dist_bp_at_3m')->nullable();
            $table->integer('oracle_dist_bp_at_2m')->nullable();
            $table->integer('oracle_dist_bp_at_90s')->nullable();
            $table->integer('oracle_dist_bp_at_1m')->nullable();
            $table->integer('oracle_dist_bp_at_45s')->nullable();
            $table->integer('oracle_dist_bp_at_30s')->nullable();
            $table->integer('oracle_dist_bp_at_15s')->nullable();
            $table->integer('oracle_dist_bp_at_final')->nullable();

            // Oracle range over window and sub-windows
            $table->integer('oracle_range_5m_bp')->nullable();
            $table->integer('oracle_range_10m_bp')->nullable();
            $table->integer('oracle_range_15m_bp')->nullable();
            $table->integer('oracle_range_5m_at_3m')->nullable();
            $table->integer('oracle_range_5m_at_2m')->nullable();

            // Oracle trend
            $table->integer('oracle_trend_5m_bp')->nullable();
            $table->integer('oracle_trend_10m_bp')->nullable();

            // Oracle activity
            $table->unsignedInteger('oracle_tick_count')->nullable();
            $table->unsignedInteger('oracle_tick_gap_max_ms')->nullable();
            $table->unsignedInteger('oracle_crossings_total')->nullable();
            $table->unsignedInteger('oracle_crossings_last_5m')->nullable();
            $table->unsignedInteger('oracle_crossings_last_2m')->nullable();
            $table->unsignedInteger('oracle_committed_since_ms')->nullable();

            // Prior 30m context
            $table->integer('oracle_range_30m_prior_bp')->nullable();
            $table->integer('oracle_trend_30m_prior_bp')->nullable();
            $table->unsignedInteger('oracle_crossings_30m_prior')->nullable();

            // CLOB features
            $table->decimal('clob_yes_ask_final', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_min_5m', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_max_5m', 6, 4)->nullable();
            $table->decimal('clob_yes_ask_avg_5m', 6, 4)->nullable();
            $table->decimal('clob_spread_final', 6, 4)->nullable();
            $table->unsignedInteger('clob_snapshot_count')->nullable();
            $table->boolean('clob_in_lock_range')->nullable();

            // Time context
            $table->tinyInteger('hour_utc')->nullable();
            $table->tinyInteger('day_of_week')->nullable();

            // Coverage
            $table->boolean('has_full_oracle_coverage')->default(false);
            $table->boolean('has_clob_coverage')->default(false);
            $table->boolean('recording_gap')->default(false);

            $table->timestamp('computed_at')->nullable();

            $table->index(['asset', 'outcome', 'open_ts']);
            $table->index('open_ts');
            $table->index(['hour_utc', 'asset']);
            $table->index(['oracle_range_5m_bp', 'asset']);
            $table->index('oracle_committed_since_ms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_market_features');
    }
};
