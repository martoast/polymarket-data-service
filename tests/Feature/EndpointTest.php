<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use App\Models\User;
use App\Models\Window;
use App\Models\WindowFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(string $tier = 'pro'): User
    {
        return User::factory()->create(['tier' => $tier]);
    }

    private function actingAsUser(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function makeAsset(string $symbol = 'BTC'): Asset
    {
        return Asset::firstOrCreate(
            ['symbol' => $symbol],
            [
                'chain'       => 'ETH',
                'oracle_addr' => '0x' . str_pad(strtolower($symbol), 40, '0', STR_PAD_LEFT),
            ]
        );
    }

    private function makeWindow(Asset $asset, array $overrides = []): Window
    {
        $openTs = $overrides['open_ts'] ?? now()->timestamp * 1000;
        $id     = $overrides['id'] ?? strtolower($asset->symbol) . '-updown-5m-' . intdiv($openTs, 1000);

        return Window::create(array_merge([
            'id'                  => $id,
            'asset_id'            => $asset->id,
            'duration_sec'        => 300,
            'break_price_usd'     => 87000.00,
            'break_price_bp'      => 8700000,
            'open_ts'             => $openTs,
            'close_ts'            => $openTs + 300000,
            'outcome'             => 'YES',
            'has_oracle_coverage' => true,
            'has_clob_coverage'   => true,
            'recording_gap'       => false,
        ], $overrides, ['id' => $id, 'asset_id' => $asset->id]));
    }

    private function makeWindowFeature(Window $window, array $overrides = []): WindowFeature
    {
        return WindowFeature::create(array_merge([
            'window_id'               => $window->id,
            'asset'                   => $window->asset->symbol,
            'duration_sec'            => $window->duration_sec,
            'open_ts'                 => $window->open_ts,
            'close_ts'                => $window->close_ts,
            'outcome'                 => $window->outcome ?? 'YES',
            'oracle_tick_count'       => 20,
            'oracle_range_5m_bp'      => 150,
            'oracle_dist_bp_final'    => 50,
            'has_full_oracle_coverage' => true,
            'has_clob_coverage'       => true,
            'recording_gap'           => false,
            'computed_at'             => now()->timestamp * 1000,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Window tests
    // -------------------------------------------------------------------------

    public function test_windows_index_returns_data(): void
    {
        $asset  = $this->makeAsset('BTC');
        $window = $this->makeWindow($asset);
        $user   = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/windows');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'next_cursor', 'has_more'])
            ->assertJsonCount(1, 'data');
    }

    public function test_windows_index_filters_by_asset(): void
    {
        $btc = $this->makeAsset('BTC');
        $eth = $this->makeAsset('ETH');

        $this->makeWindow($btc, ['id' => 'btc-updown-5m-1000001']);
        $this->makeWindow($eth, ['id' => 'eth-updown-5m-1000002']);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/windows?asset=BTC');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.asset', 'BTC');
    }

    public function test_windows_index_enforces_tier_history_limit(): void
    {
        $asset = $this->makeAsset('BTC');

        // Create a window 10 days ago (outside free tier's 7-day window)
        $oldTs = now()->subDays(10)->timestamp * 1000;
        $this->makeWindow($asset, [
            'id'      => 'btc-updown-5m-' . intdiv($oldTs, 1000),
            'open_ts' => $oldTs,
            'close_ts' => $oldTs + 300000,
        ]);

        $user = $this->makeUser('free');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/windows');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_windows_show_returns_window(): void
    {
        $asset  = $this->makeAsset('BTC');
        $window = $this->makeWindow($asset);
        $user   = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/windows/' . $window->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $window->id)
            ->assertJsonPath('data.asset', 'BTC');
    }

    public function test_windows_show_returns_404_for_unknown(): void
    {
        $user = $this->makeUser('pro');

        $this->actingAsUser($user)
            ->getJson('/api/v1/windows/nonexistent-window-id')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Oracle tests
    // -------------------------------------------------------------------------

    public function test_oracle_ticks_requires_asset(): void
    {
        $user = $this->makeUser('pro');

        $this->actingAsUser($user)
            ->getJson('/api/v1/oracle/ticks')
            ->assertStatus(422);
    }

    public function test_oracle_ticks_returns_data(): void
    {
        $asset = $this->makeAsset('BTC');
        $ts    = now()->timestamp * 1000;

        OracleTick::create([
            'asset_id'  => $asset->id,
            'price_usd' => 87000.00,
            'price_bp'  => 8700000,
            'ts'        => $ts,
        ]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/oracle/ticks?asset=BTC');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.asset', 'BTC');
    }

    public function test_oracle_range_returns_stats(): void
    {
        $asset  = $this->makeAsset('BTC');
        $fromTs = now()->subMinutes(10)->timestamp * 1000;
        $toTs   = now()->timestamp * 1000;

        OracleTick::create(['asset_id' => $asset->id, 'price_usd' => 87000, 'price_bp' => 8700000, 'ts' => $fromTs + 1000]);
        OracleTick::create(['asset_id' => $asset->id, 'price_usd' => 88000, 'price_bp' => 8800000, 'ts' => $fromTs + 2000]);
        OracleTick::create(['asset_id' => $asset->id, 'price_usd' => 86000, 'price_bp' => 8600000, 'ts' => $fromTs + 3000]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson("/api/v1/oracle/range?asset=BTC&from={$fromTs}&to={$toTs}");

        $response->assertStatus(200)
            ->assertJsonPath('data.tick_count', 3)
            ->assertJsonPath('data.min_price_bp', 8600000)
            ->assertJsonPath('data.max_price_bp', 8800000);
    }

    // -------------------------------------------------------------------------
    // CLOB tests
    // -------------------------------------------------------------------------

    public function test_clob_snapshots_requires_window_or_asset(): void
    {
        $user = $this->makeUser('pro');

        $this->actingAsUser($user)
            ->getJson('/api/v1/clob/snapshots')
            ->assertStatus(422);
    }

    public function test_clob_snapshots_by_window(): void
    {
        $asset  = $this->makeAsset('BTC');
        $window = $this->makeWindow($asset);
        $ts     = now()->timestamp * 1000;

        ClobSnapshot::create([
            'window_id' => $window->id,
            'asset_id'  => $asset->id,
            'yes_ask'   => 0.90,
            'yes_bid'   => 0.88,
            'no_ask'    => 0.12,
            'no_bid'    => 0.10,
            'ts'        => $ts,
        ]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/clob/snapshots?window_id=' . $window->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.window_id', $window->id);
    }

    // -------------------------------------------------------------------------
    // Window feature tests
    // -------------------------------------------------------------------------

    public function test_features_returns_data(): void
    {
        $asset   = $this->makeAsset('BTC');
        $window  = $this->makeWindow($asset);
        $feature = $this->makeWindowFeature($window);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/features');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_features_quality_strict_filters(): void
    {
        $asset = $this->makeAsset('BTC');

        // Good window/feature
        $goodWindow  = $this->makeWindow($asset, ['id' => 'btc-updown-5m-1000001']);
        $this->makeWindowFeature($goodWindow, [
            'has_full_oracle_coverage' => true,
            'has_clob_coverage'        => true,
            'recording_gap'            => false,
        ]);

        // Bad window/feature — has recording gap
        $badWindow = $this->makeWindow($asset, [
            'id'           => 'btc-updown-5m-1000002',
            'open_ts'      => now()->subMinutes(30)->timestamp * 1000,
            'close_ts'     => now()->subMinutes(25)->timestamp * 1000,
            'recording_gap' => true,
        ]);
        $this->makeWindowFeature($badWindow, [
            'open_ts'      => $badWindow->open_ts,
            'close_ts'     => $badWindow->close_ts,
            'recording_gap' => true,
        ]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/features?quality=strict');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // -------------------------------------------------------------------------
    // Active markets test
    // -------------------------------------------------------------------------

    public function test_active_markets_returns_unresolved(): void
    {
        $asset = $this->makeAsset('BTC');
        $nowMs = now()->timestamp * 1000;

        // Active (unresolved, closes in the future)
        Window::create([
            'id'              => 'btc-updown-5m-active',
            'asset_id'        => $asset->id,
            'duration_sec'    => 300,
            'break_price_usd' => 87000,
            'break_price_bp'  => 8700000,
            'open_ts'         => $nowMs - 60000,
            'close_ts'        => $nowMs + 240000,
            'outcome'         => null,
        ]);

        // Resolved
        Window::create([
            'id'              => 'btc-updown-5m-resolved',
            'asset_id'        => $asset->id,
            'duration_sec'    => 300,
            'break_price_usd' => 87000,
            'break_price_bp'  => 8700000,
            'open_ts'         => $nowMs - 600000,
            'close_ts'        => $nowMs - 300000,
            'outcome'         => 'YES',
        ]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->getJson('/api/v1/markets/active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'btc-updown-5m-active');
    }

    // -------------------------------------------------------------------------
    // CSV export — tier check
    // -------------------------------------------------------------------------

    public function test_csv_export_requires_pro(): void
    {
        $asset   = $this->makeAsset('BTC');
        $window  = $this->makeWindow($asset);
        $this->makeWindowFeature($window);

        $user = $this->makeUser('builder');

        $this->actingAsUser($user)
            ->getJson('/api/v1/features?format=csv')
            ->assertStatus(403)
            ->assertJsonPath('code', 'TIER_REQUIRED');
    }

    // -------------------------------------------------------------------------
    // Backtest tests
    // -------------------------------------------------------------------------

    public function test_backtest_runs_filter(): void
    {
        $asset  = $this->makeAsset('BTC');
        $window = $this->makeWindow($asset);
        $this->makeWindowFeature($window, ['oracle_range_5m_bp' => 200]);

        // Create a feature that won't match
        $window2 = $this->makeWindow($asset, ['id' => 'btc-updown-5m-1000003', 'open_ts' => now()->subMinutes(10)->timestamp * 1000]);
        $this->makeWindowFeature($window2, [
            'open_ts'            => $window2->open_ts,
            'close_ts'           => $window2->close_ts,
            'oracle_range_5m_bp' => 50,
        ]);

        $user = $this->makeUser('pro');

        $response = $this->actingAsUser($user)
            ->postJson('/api/v1/backtest', [
                'conditions' => [
                    ['field' => 'oracle_range_5m_bp', 'op' => '>=', 'value' => 150],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_backtest_rejects_invalid_field(): void
    {
        $user = $this->makeUser('pro');

        $this->actingAsUser($user)
            ->postJson('/api/v1/backtest', [
                'conditions' => [
                    ['field' => 'password', 'op' => '=', 'value' => 1],
                ],
            ])
            ->assertStatus(422);
    }
}
