<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Categories (idempotent) ───────────────────────────────────────────
        $categories = [
            ['slug' => 'crypto',  'name' => 'Crypto',  'description' => 'Cryptocurrency price prediction markets (BTC, ETH, SOL)'],
            ['slug' => 'weather', 'name' => 'Weather', 'description' => 'Daily weather prediction markets (temperature highs, etc.)'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $cryptoId  = Category::where('slug', 'crypto')->value('id');
        $weatherId = Category::where('slug', 'weather')->value('id');

        // ── Assets (idempotent) ───────────────────────────────────────────────
        $assets = [
            // Crypto
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'BTC',
                'name'          => 'Bitcoin',
                'unit'          => 'usd',
                'source_config' => json_encode(['oracle_addr' => '0xF4030086522a5bEEa4988F8cA5B36dbC97BeE88b', 'chain' => 'ethereum', 'chainlink_symbol' => 'btc/usd']),
            ],
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'ETH',
                'name'          => 'Ethereum',
                'unit'          => 'usd',
                'source_config' => json_encode(['oracle_addr' => '0x5f4eC3Df9cbd43714FE2740f5E3616155c5b8419', 'chain' => 'ethereum', 'chainlink_symbol' => 'eth/usd']),
            ],
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'SOL',
                'name'          => 'Solana',
                'unit'          => 'usd',
                'source_config' => json_encode(['oracle_addr' => '0x4ffC43a60e009B551865A93d232E33Fce9f01507', 'chain' => 'ethereum', 'chainlink_symbol' => 'sol/usd']),
            ],
            // Weather — Tokyo Haneda Airport (ICAO: RJTT)
            [
                'category_id'   => $weatherId,
                'symbol'        => 'RJTT',
                'name'          => 'Tokyo High Temperature',
                'unit'          => 'celsius',
                'source_config' => json_encode([
                    'icao'          => 'RJTT',
                    'city'          => 'Tokyo',
                    'country'       => 'JP',
                    'latitude'      => 35.5494,
                    'longitude'     => 139.7798,
                    'timezone'      => 'Asia/Tokyo',
                    'wunderground'  => 'RJTT',
                    'open_meteo'    => true,
                ]),
            ],
        ];

        foreach ($assets as $asset) {
            Asset::firstOrCreate(['symbol' => $asset['symbol']], $asset);
        }

        // ── Test accounts ─────────────────────────────────────────────────────
        $testUsers = [
            ['email' => 'admin@test.com',   'name' => 'Admin',        'tier' => 'pro'],
            ['email' => 'builder@test.com', 'name' => 'Builder User', 'tier' => 'builder'],
            ['email' => 'free@test.com',    'name' => 'Free User',    'tier' => 'free'],
        ];

        foreach ($testUsers as $attrs) {
            $user = User::firstOrCreate(['email' => $attrs['email']], [
                'name'              => $attrs['name'],
                'password'          => Hash::make('password'),
                'tier'              => $attrs['tier'],
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            if (! $user->api_key) {
                $user->tokens()->delete();
                $plain = $user->createToken('api')->plainTextToken;
                $user->update(['api_key' => $plain]);
            }
        }
    }
}
