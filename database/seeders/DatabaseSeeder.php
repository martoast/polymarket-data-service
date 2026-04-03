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
        // Pass source_config as array — Eloquent's array cast handles JSON encoding.
        $assets = [
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'BTC',
                'name'          => 'Bitcoin',
                'unit'          => 'usd',
                'source_config' => ['oracle_addr' => '0xF4030086522a5bEEa4988F8cA5B36dbC97BeE88b', 'chain' => 'ethereum', 'chainlink_symbol' => 'btc/usd'],
            ],
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'ETH',
                'name'          => 'Ethereum',
                'unit'          => 'usd',
                'source_config' => ['oracle_addr' => '0x5f4eC3Df9cbd43714FE2740f5E3616155c5b8419', 'chain' => 'ethereum', 'chainlink_symbol' => 'eth/usd'],
            ],
            [
                'category_id'   => $cryptoId,
                'symbol'        => 'SOL',
                'name'          => 'Solana',
                'unit'          => 'usd',
                'source_config' => ['oracle_addr' => '0x4ffC43a60e009B551865A93d232E33Fce9f01507', 'chain' => 'ethereum', 'chainlink_symbol' => 'sol/usd'],
            ],
            // ── Weather assets ────────────────────────────────────────────────
            // Each entry maps to one weather station used for Open-Meteo polling.
            // 'city' must match the Polymarket Gamma slug exactly after lowercasing
            // and replacing spaces with hyphens (e.g. "Los Angeles" → "los-angeles").
            // US cities use °F markets; international cities use °C markets.

            // Tokyo — Haneda Airport (RJTT) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'RJTT',
                'name'          => 'Tokyo High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'RJTT',
                    'city'       => 'Tokyo',
                    'country'    => 'JP',
                    'latitude'   => 35.5494,
                    'longitude'  => 139.7798,
                    'timezone'   => 'Asia/Tokyo',
                    'open_meteo' => true,
                ],
            ],
            // London — Heathrow (EGLL) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'EGLL',
                'name'          => 'London High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'EGLL',
                    'city'       => 'London',
                    'country'    => 'GB',
                    'latitude'   => 51.4775,
                    'longitude'  => -0.4614,
                    'timezone'   => 'Europe/London',
                    'open_meteo' => true,
                ],
            ],
            // Paris — CDG (LFPG) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'LFPG',
                'name'          => 'Paris High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'LFPG',
                    'city'       => 'Paris',
                    'country'    => 'FR',
                    'latitude'   => 49.0097,
                    'longitude'  => 2.5479,
                    'timezone'   => 'Europe/Paris',
                    'open_meteo' => true,
                ],
            ],
            // Singapore — Changi (WSSS) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'WSSS',
                'name'          => 'Singapore High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'WSSS',
                    'city'       => 'Singapore',
                    'country'    => 'SG',
                    'latitude'   => 1.3644,
                    'longitude'  => 103.9915,
                    'timezone'   => 'Asia/Singapore',
                    'open_meteo' => true,
                ],
            ],
            // Seoul — Incheon (RKSI) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'RKSI',
                'name'          => 'Seoul High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'RKSI',
                    'city'       => 'Seoul',
                    'country'    => 'KR',
                    'latitude'   => 37.4693,
                    'longitude'  => 126.4503,
                    'timezone'   => 'Asia/Seoul',
                    'open_meteo' => true,
                ],
            ],
            // Beijing — Capital Airport (ZBAA) — °C
            [
                'category_id'   => $weatherId,
                'symbol'        => 'ZBAA',
                'name'          => 'Beijing High Temperature',
                'unit'          => 'celsius',
                'source_config' => [
                    'icao'       => 'ZBAA',
                    'city'       => 'Beijing',
                    'country'    => 'CN',
                    'latitude'   => 40.0799,
                    'longitude'  => 116.5857,
                    'timezone'   => 'Asia/Shanghai',
                    'open_meteo' => true,
                ],
            ],
            // Chicago — O'Hare (KORD) — °F
            [
                'category_id'   => $weatherId,
                'symbol'        => 'KORD',
                'name'          => 'Chicago High Temperature',
                'unit'          => 'fahrenheit',
                'source_config' => [
                    'icao'       => 'KORD',
                    'city'       => 'Chicago',
                    'country'    => 'US',
                    'latitude'   => 41.9742,
                    'longitude'  => -87.9073,
                    'timezone'   => 'America/Chicago',
                    'open_meteo' => true,
                ],
            ],
            // New York City — JFK (KJFK) — °F  (Gamma slug: "nyc")
            [
                'category_id'   => $weatherId,
                'symbol'        => 'KJFK',
                'name'          => 'New York City High Temperature',
                'unit'          => 'fahrenheit',
                'source_config' => [
                    'icao'       => 'KJFK',
                    'city'       => 'nyc',       // Polymarket uses "nyc" as the slug
                    'country'    => 'US',
                    'latitude'   => 40.6413,
                    'longitude'  => -73.7781,
                    'timezone'   => 'America/New_York',
                    'open_meteo' => true,
                ],
            ],
            // Los Angeles — LAX (KLAX) — °F
            [
                'category_id'   => $weatherId,
                'symbol'        => 'KLAX',
                'name'          => 'Los Angeles High Temperature',
                'unit'          => 'fahrenheit',
                'source_config' => [
                    'icao'       => 'KLAX',
                    'city'       => 'Los Angeles',
                    'country'    => 'US',
                    'latitude'   => 33.9425,
                    'longitude'  => -118.4081,
                    'timezone'   => 'America/Los_Angeles',
                    'open_meteo' => true,
                ],
            ],
            // Miami — MIA (KMIA) — °F
            [
                'category_id'   => $weatherId,
                'symbol'        => 'KMIA',
                'name'          => 'Miami High Temperature',
                'unit'          => 'fahrenheit',
                'source_config' => [
                    'icao'       => 'KMIA',
                    'city'       => 'Miami',
                    'country'    => 'US',
                    'latitude'   => 25.7959,
                    'longitude'  => -80.2870,
                    'timezone'   => 'America/New_York',
                    'open_meteo' => true,
                ],
            ],
        ];

        foreach ($assets as $asset) {
            Asset::updateOrCreate(['symbol' => $asset['symbol']], $asset);
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
