<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->string('symbol', 30)->unique();   // BTC, ETH, SOL, RJTT
            $table->string('name', 100);              // Bitcoin, Tokyo High Temp
            $table->string('unit', 20);               // 'usd', 'celsius', 'fahrenheit'
            $table->jsonb('source_config')->nullable(); // oracle addr, lat/lng, station id, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
