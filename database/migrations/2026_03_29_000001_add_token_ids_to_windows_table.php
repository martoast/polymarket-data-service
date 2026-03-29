<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('windows', function (Blueprint $table) {
            $table->string('yes_token_id')->nullable()->after('condition_id');
            $table->string('no_token_id')->nullable()->after('yes_token_id');
        });
    }

    public function down(): void
    {
        Schema::table('windows', function (Blueprint $table) {
            $table->dropColumn(['yes_token_id', 'no_token_id']);
        });
    }
};
