<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('windows', function (Blueprint $table) {
            $table->string('id')->primary(); // gamma slug: btc-updown-5m-1773136800
            $table->unsignedSmallInteger('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');
            $table->smallInteger('duration_sec'); // 300 or 900
            $table->decimal('break_price_usd', 14, 2);
            $table->bigInteger('break_price_bp');
            $table->bigInteger('open_ts');
            $table->bigInteger('close_ts');
            $table->bigInteger('resolved_ts')->nullable();
            $table->string('outcome', 3)->nullable(); // YES | NO
            $table->string('condition_id')->nullable()->index(); // hex conditionId
            $table->string('gamma_slug')->nullable();
            $table->boolean('has_oracle_coverage')->default(false);
            $table->boolean('has_clob_coverage')->default(false);
            $table->boolean('recording_gap')->default(false);
        });

        Schema::table('windows', function (Blueprint $table) {
            $table->index(['asset_id', 'open_ts']);
            $table->index(['asset_id', 'outcome', 'open_ts']);
            $table->index('close_ts');
            $table->index('open_ts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('windows');
    }
};
