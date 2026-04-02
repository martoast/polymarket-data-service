<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            // e.g. 'btc-updown-5m-1775167500', 'tokyo-high-temp-2026-04-03'
            $table->string('id')->primary();

            $table->string('category', 20)->index(); // 'crypto', 'weather' — denorm for fast filter
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');

            $table->unsignedInteger('duration_sec');   // 300, 900, 86400
            $table->string('duration_label', 10);      // '5m', '15m', '1d'

            // Generic break value — price in USD for crypto, temp in Celsius for weather, etc.
            $table->decimal('break_value', 14, 4)->default(0);
            $table->string('value_unit', 20)->default('usd'); // 'usd', 'celsius'

            $table->bigInteger('open_ts');   // epoch ms
            $table->bigInteger('close_ts');  // epoch ms
            $table->bigInteger('resolved_ts')->nullable();

            $table->string('outcome', 3)->nullable(); // 'YES' | 'NO'

            // Polymarket identifiers
            $table->string('condition_id')->nullable()->index();
            $table->string('gamma_slug')->nullable();
            $table->string('yes_token_id')->nullable();
            $table->string('no_token_id')->nullable();

            // Recording quality flags
            $table->boolean('has_coverage')->default(false);
            $table->boolean('recording_gap')->default(false);

            $table->index(['asset_id', 'open_ts']);
            $table->index(['category', 'close_ts']);
            $table->index(['outcome', 'open_ts']);
            $table->index('close_ts');
            $table->index('open_ts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
