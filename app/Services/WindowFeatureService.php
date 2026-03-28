<?php

namespace App\Services;

use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use App\Models\Window;
use App\Models\WindowFeature;
use Illuminate\Support\Facades\DB;

class WindowFeatureService
{
    public function computeFeatures(string $windowId): void
    {
        $window = Window::with('asset')->find($windowId);
        if (!$window || !$window->outcome) {
            return;
        }

        $assetId = $window->asset_id;
        $openTs  = $window->open_ts;
        $closeTs = $window->close_ts;
        $breakBp = $window->break_price_bp;

        // Load oracle ticks for this window plus 30 min prior context
        $ticks = OracleTick::where('asset_id', $assetId)
            ->whereBetween('ts', [$openTs - 1800000, $closeTs])
            ->orderBy('ts')
            ->get(['price_bp', 'ts']);

        // Window ticks only (open_ts..close_ts)
        $windowTicks = $ticks->filter(fn ($t) => $t->ts >= $openTs && $t->ts <= $closeTs);

        // Helper: find nearest oracle tick price_bp at a given offset from close
        $priceAtOffset = function (int $offsetSec) use ($windowTicks, $closeTs): ?int {
            $target  = $closeTs - ($offsetSec * 1000);
            $nearest = $windowTicks->sortBy(fn ($t) => abs($t->ts - $target))->first();
            return $nearest?->price_bp;
        };

        // Oracle distance from break price (positive = oracle on YES side)
        $distAt = function (?int $priceBp) use ($breakBp): ?int {
            if ($priceBp === null) {
                return null;
            }
            return $priceBp - $breakBp;
        };

        // Count oracle crossings of the break price
        $crossings = function ($tickCollection) use ($breakBp): int {
            $count = 0;
            $prev  = null;
            foreach ($tickCollection as $tick) {
                $side = $tick->price_bp >= $breakBp ? 1 : -1;
                if ($prev !== null && $side !== $prev) {
                    $count++;
                }
                $prev = $side;
            }
            return $count;
        };

        // Oracle price range (high - low in bp) for a tick collection
        $oracleRange = function ($tickCollection): ?int {
            if ($tickCollection->isEmpty()) {
                return null;
            }
            return $tickCollection->max('price_bp') - $tickCollection->min('price_bp');
        };

        // Time-sliced window tick collections
        $ticks5m  = $windowTicks->filter(fn ($t) => $t->ts >= ($closeTs - 300000));
        $ticks10m = $windowTicks->filter(fn ($t) => $t->ts >= ($closeTs - 600000));
        $ticks15m = $windowTicks->filter(fn ($t) => $t->ts >= ($closeTs - 900000));
        $ticks2m  = $windowTicks->filter(fn ($t) => $t->ts >= ($closeTs - 120000));

        // Prior 30 min context ticks (before window open)
        $priorTicks = $ticks->filter(
            fn ($t) => $t->ts >= ($openTs - 1800000) && $t->ts < $openTs
        );

        // Oracle committed since: ms since last crossing at close
        $lastCrossTs = null;
        $prevSide    = null;
        foreach ($windowTicks->sortBy('ts') as $tick) {
            $side = $tick->price_bp >= $breakBp ? 1 : -1;
            if ($prevSide !== null && $side !== $prevSide) {
                $lastCrossTs = $tick->ts;
            }
            $prevSide = $side;
        }
        $committedSince = $lastCrossTs
            ? ($closeTs - $lastCrossTs)
            : ($closeTs - $openTs);

        // CLOB features (last 5 minutes of the window)
        $clob5m = ClobSnapshot::where('window_id', $windowId)
            ->where('ts', '>=', $closeTs - 300000)
            ->orderBy('ts')
            ->get(['yes_ask', 'yes_bid', 'ts']);

        $clobFinal = $clob5m->last();

        // Tick gap — max gap between consecutive oracle ticks
        $maxGap = 0;
        $prevTs = null;
        foreach ($windowTicks->sortBy('ts') as $tick) {
            if ($prevTs !== null) {
                $gap = $tick->ts - $prevTs;
                if ($gap > $maxGap) {
                    $maxGap = $gap;
                }
            }
            $prevTs = $tick->ts;
        }

        $features = [
            'window_id'    => $windowId,
            'asset'        => $window->asset->symbol,
            'duration_sec' => $window->duration_sec,
            'open_ts'      => $openTs,
            'close_ts'     => $closeTs,
            'outcome'      => $window->outcome,

            // Oracle distance at fixed time offsets from close
            'oracle_dist_bp_at_5m'  => $distAt($priceAtOffset(300)),
            'oracle_dist_bp_at_4m'  => $distAt($priceAtOffset(240)),
            'oracle_dist_bp_at_3m'  => $distAt($priceAtOffset(180)),
            'oracle_dist_bp_at_2m'  => $distAt($priceAtOffset(120)),
            'oracle_dist_bp_at_90s' => $distAt($priceAtOffset(90)),
            'oracle_dist_bp_at_1m'  => $distAt($priceAtOffset(60)),
            'oracle_dist_bp_at_45s' => $distAt($priceAtOffset(45)),
            'oracle_dist_bp_at_30s' => $distAt($priceAtOffset(30)),
            'oracle_dist_bp_at_15s' => $distAt($priceAtOffset(15)),
            'oracle_dist_bp_final'  => $distAt($windowTicks->last()?->price_bp),

            // Oracle volatility (price range in bp)
            'oracle_range_5m_bp'    => $oracleRange($ticks5m),
            'oracle_range_10m_bp'   => $oracleRange($ticks10m),
            'oracle_range_15m_bp'   => $oracleRange($ticks15m),
            'oracle_range_5m_at_3m' => $oracleRange(
                $windowTicks->filter(
                    fn ($t) => $t->ts >= ($closeTs - 480000) && $t->ts <= ($closeTs - 180000)
                )
            ),
            'oracle_range_5m_at_2m' => $oracleRange(
                $windowTicks->filter(
                    fn ($t) => $t->ts >= ($closeTs - 420000) && $t->ts <= ($closeTs - 120000)
                )
            ),

            // Oracle trend (net price change in bp)
            'oracle_trend_5m_bp'  => $ticks5m->isNotEmpty()
                ? ($ticks5m->last()->price_bp - $ticks5m->first()->price_bp)
                : null,
            'oracle_trend_10m_bp' => $ticks10m->isNotEmpty()
                ? ($ticks10m->last()->price_bp - $ticks10m->first()->price_bp)
                : null,

            // Oracle density / data quality
            'oracle_tick_count'      => $windowTicks->count(),
            'oracle_tick_gap_max_ms' => $maxGap ?: null,

            // Crossings (how many times price crossed break price)
            'oracle_crossings_total'    => $crossings($windowTicks),
            'oracle_crossings_last_5m'  => $crossings($ticks5m),
            'oracle_crossings_last_2m'  => $crossings($ticks2m),
            'oracle_committed_since_ms' => $committedSince,

            // CLOB features
            'clob_yes_ask_final'  => $clobFinal?->yes_ask,
            'clob_yes_ask_min_5m' => $clob5m->min('yes_ask'),
            'clob_yes_ask_max_5m' => $clob5m->max('yes_ask'),
            'clob_yes_ask_avg_5m' => $clob5m->avg('yes_ask'),
            'clob_spread_final'   => ($clobFinal && $clobFinal->yes_ask && $clobFinal->yes_bid)
                ? ($clobFinal->yes_ask - $clobFinal->yes_bid)
                : null,
            'clob_snapshot_count' => $clob5m->count(),
            'clob_in_lock_range'  => $clob5m->contains(
                fn ($s) => $s->yes_ask >= 0.84 && $s->yes_ask <= 0.91
            ),

            // Prior 30-minute context
            'oracle_range_30m_prior_bp'  => $oracleRange($priorTicks),
            'oracle_trend_30m_prior_bp'  => $priorTicks->isNotEmpty()
                ? ($priorTicks->last()->price_bp - $priorTicks->first()->price_bp)
                : null,
            'oracle_crossings_30m_prior' => $crossings($priorTicks),

            // Temporal context
            'hour_utc'    => (int) date('G', intdiv($openTs, 1000)),
            'day_of_week' => (int) date('N', intdiv($openTs, 1000)) - 1, // 0=Monday

            // Coverage quality flags
            'has_full_oracle_coverage' => $window->has_oracle_coverage,
            'has_clob_coverage'        => $window->has_clob_coverage,
            'recording_gap'            => $window->recording_gap,

            'computed_at' => (int) (microtime(true) * 1000),
        ];

        WindowFeature::updateOrCreate(['window_id' => $windowId], $features);

        // Mark the window as having oracle coverage if we found ticks
        if ($windowTicks->isNotEmpty()) {
            DB::table('windows')
                ->where('id', $window->id)
                ->update(['has_oracle_coverage' => true]);
        }
    }
}
