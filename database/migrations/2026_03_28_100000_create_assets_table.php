<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('symbol', 10)->unique();
            $table->string('chain', 50);
            $table->string('oracle_addr', 42);
        });

        DB::table('assets')->insert([
            ['symbol' => 'BTC', 'chain' => 'ethereum', 'oracle_addr' => '0xF4030086522a5bEEa4988F8cA5B36dbC97BeE88b'],
            ['symbol' => 'ETH', 'chain' => 'ethereum', 'oracle_addr' => '0x5f4eC3Df9cbd43714FE2740f5E3616155c5b8419'],
            ['symbol' => 'SOL', 'chain' => 'ethereum', 'oracle_addr' => '0x4ffC43a60e009B551865A93d232E33Fce9f01507'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
