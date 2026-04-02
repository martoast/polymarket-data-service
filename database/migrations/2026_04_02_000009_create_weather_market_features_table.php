<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_market_features', function (Blueprint $table) {
            $table->string('market_id')->primary();
            $table->foreign('market_id')->references('id')->on('markets');

            $table->string('asset', 30);       // e.g. 'RJTT'
            $table->string('station', 30);     // ICAO code
            $table->bigInteger('open_ts');
            $table->bigInteger('close_ts');
            $table->string('outcome', 3)->nullable();

            // Temperature at market open
            $table->decimal('temp_at_open_c', 5, 2)->nullable();

            // Break value (temperature threshold in Celsius)
            $table->decimal('break_value_c', 5, 2)->nullable();

            // Running max captured at close_ts
            $table->decimal('running_max_at_close_c', 5, 2)->nullable();

            // Final observed daily maximum (after resolution)
            $table->decimal('final_max_c', 5, 2)->nullable();

            // Forecast context
            $table->decimal('forecast_at_open_c', 5, 2)->nullable();
            $table->decimal('forecast_deviation_c', 5, 2)->nullable();  // actual - forecast

            // Temporal context
            $table->decimal('hours_above_threshold', 4, 1)->nullable();
            $table->tinyInteger('hour_utc')->nullable();
            $table->tinyInteger('day_of_week')->nullable();
            $table->string('season', 10)->nullable(); // 'winter', 'spring', 'summer', 'autumn'

            // CLOB features (same as crypto — market_id based)
            $table->decimal('clob_yes_ask_final', 6, 4)->nullable();
            $table->decimal('clob_spread_final', 6, 4)->nullable();
            $table->unsignedInteger('clob_snapshot_count')->nullable();

            // Coverage
            $table->boolean('has_reading_coverage')->default(false);
            $table->boolean('has_clob_coverage')->default(false);

            $table->timestamp('computed_at')->nullable();

            $table->index(['asset', 'outcome', 'open_ts']);
            $table->index('open_ts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_market_features');
    }
};
