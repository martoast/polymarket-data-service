<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Assets (idempotent)
        $assets = [
            ['symbol' => 'BTC', 'name' => 'Bitcoin'],
            ['symbol' => 'ETH', 'name' => 'Ethereum'],
            ['symbol' => 'SOL', 'name' => 'Solana'],
        ];

        foreach ($assets as $asset) {
            Asset::firstOrCreate(['symbol' => $asset['symbol']], $asset);
        }

        // Test accounts — provision an api_key for each if they don't have one
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
