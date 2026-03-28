<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use App\Models\Window;
use App\Models\WindowFeature;
use App\Services\WindowFeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WindowFeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        // Asset is seeded by the migration; fetch BTC
        $this->asset = Asset::where('symbol', 'BTC')->firstOrFail();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeWindow(array $overrides = []): Window
    {
        $openTs  = strtotime('2025-01-15 10:00:00') * 1000;
        $closeTs = $openTs + 300_000; // 5 min window

        $defaults = [
            'id'              => 'btc-updown-5m-' . intdiv($openTs, 1000),
            'asset_id'        => $this->asset->id,
            'duration_sec'    => 300,
            'break_price_usd' => 87000.00,
            'break_price_bp'  => 8700000,
            'open_ts'         => $openTs,
            'close_ts'        => $closeTs,
            'outcome'         => 'YES',
            'condition_id'    => '0xDEADBEEF',
        ];

        $data   = array_merge($defaults, $overrides);
        $window = new Window($data);
        $window->save();

        return $window->fresh();
    }

    private function insertOracleTick(int $assetId, int $priceBp, int $ts): void
    {
        DB::table('oracle_ticks')->insert([
            'asset_id'  => $assetId,
            'price_usd' => $priceBp / 100,
            'price_bp'  => $priceBp,
            'ts'        => $ts,
        ]);
    }

    private function insertClobSnapshot(string $windowId, int $assetId, float $yesAsk, float $yesBid, int $ts): void
    {
        DB::table('clob_snapshots')->insert([
            'window_id' => $windowId,
            'asset_id'  => $assetId,
            'yes_ask'   => $yesAsk,
            'yes_bid'   => $yesBid,
            'no_ask'    => 1 - $yesBid,
            'no_bid'    => 1 - $yesAsk,
            'ts'        => $ts,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_computes_features_and_creates_row(): void
    {
        $window  = $this->makeWindow();
        $openTs  = $window->open_ts;
        $closeTs = $window->close_ts;

        // Insert 5 oracle ticks spread across the window
        for ($i = 0; $i < 5; $i++) {
            $ts      = $openTs + ($i * 60_000); // one per minute
            $priceBp = 8700100 + ($i * 50);    // above break_price_bp = 8700000
            $this->insertOracleTick($this->asset->id, $priceBp, $ts);
        }

        // Insert 2 clob snapshots in last 5 min
        $this->insertClobSnapshot($window->id, $this->asset->id, 0.87, 0.85, $closeTs - 200_000);
        $this->insertClobSnapshot($window->id, $this->asset->id, 0.88, 0.86, $closeTs - 100_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);

        $feature = WindowFeature::find($window->id);

        $this->assertNotNull($feature, 'window_features row should exist');
        $this->assertEquals('YES', $feature->outcome);
        $this->assertEquals(5, $feature->oracle_tick_count);
        $this->assertEquals('BTC', $feature->asset);
    }

    public function test_hour_utc_is_correct(): void
    {
        // Force open_ts to a known hour (2025-01-15 14:00:00 UTC = hour 14)
        $openTs  = strtotime('2025-01-15 14:00:00 UTC') * 1000;
        $closeTs = $openTs + 300_000;

        $window = $this->makeWindow([
            'id'       => 'btc-updown-5m-' . intdiv($openTs, 1000),
            'open_ts'  => $openTs,
            'close_ts' => $closeTs,
        ]);

        // Need at least one tick so computeFeatures has data
        $this->insertOracleTick($this->asset->id, 8700100, $openTs + 10_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);

        $feature = WindowFeature::find($window->id);
        $this->assertNotNull($feature);
        $this->assertEquals(14, $feature->hour_utc);
    }

    public function test_no_feature_computed_if_outcome_is_null(): void
    {
        $window = $this->makeWindow(['outcome' => null]);

        $this->insertOracleTick($this->asset->id, 8700100, $window->open_ts + 10_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);

        $this->assertNull(WindowFeature::find($window->id));
    }

    public function test_oracle_crossings_counted_correctly(): void
    {
        $window  = $this->makeWindow();
        $openTs  = $window->open_ts;
        $closeTs = $window->close_ts;
        $breakBp = $window->break_price_bp; // 8700000

        // Ticks: above, below, above (= 2 crossings)
        $this->insertOracleTick($this->asset->id, $breakBp + 100, $openTs + 30_000);
        $this->insertOracleTick($this->asset->id, $breakBp - 100, $openTs + 90_000);
        $this->insertOracleTick($this->asset->id, $breakBp + 100, $openTs + 150_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);

        $feature = WindowFeature::find($window->id);
        $this->assertNotNull($feature);
        $this->assertEquals(2, $feature->oracle_crossings_total);
    }

    public function test_clob_in_lock_range_true_when_yes_ask_in_range(): void
    {
        $window  = $this->makeWindow();
        $closeTs = $window->close_ts;

        $this->insertOracleTick($this->asset->id, 8700100, $window->open_ts + 10_000);

        // yes_ask = 0.87 is within lock range [0.84, 0.91]
        $this->insertClobSnapshot($window->id, $this->asset->id, 0.87, 0.85, $closeTs - 60_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);

        $feature = WindowFeature::find($window->id);
        $this->assertNotNull($feature);
        $this->assertTrue((bool) $feature->clob_in_lock_range);
    }

    public function test_updateOrCreate_replaces_existing_feature_row(): void
    {
        $window = $this->makeWindow();
        $this->insertOracleTick($this->asset->id, 8700100, $window->open_ts + 10_000);

        $service = new WindowFeatureService();
        $service->computeFeatures($window->id);
        $service->computeFeatures($window->id); // second call — should update, not duplicate

        $this->assertDatabaseCount('window_features', 1);
    }
}
