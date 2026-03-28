<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_audit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('filename')->unique();
            $table->bigInteger('started_at');
            $table->bigInteger('ended_at')->nullable();
            $table->integer('event_count')->nullable();
            $table->integer('oracle_count')->nullable();
            $table->integer('clob_count')->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('ingest_status', 20)->default('pending');
            $table->text('ingest_error')->nullable();
            $table->bigInteger('ingested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_audit');
    }
};
