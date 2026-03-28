<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backfill_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->timestamp('processed_at');
            $table->unsignedInteger('total_lines')->default(0);
            $table->unsignedInteger('windows')->default(0);
            $table->unsignedInteger('oracle')->default(0);
            $table->unsignedInteger('clob')->default(0);
            $table->unsignedInteger('candles')->default(0);
            $table->unsignedInteger('resolutions')->default(0);
            $table->unsignedInteger('skipped_clob')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backfill_sessions');
    }
};
