<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Runs every 5 minutes via scheduler.
 *
 * 1. Fills break_value for crypto markets that still have 0 (oracle tick closest to open_ts).
 * 2. Sets has_coverage = true for any market that has at least one clob_snapshot.
 * 3. For expired crypto markets with no outcome, queries Gamma for resolution.
 */
class BackfillMarketsCommand extends Command
{
    protected $signature   = 'recorder:backfill-markets';
    protected $description = 'Backfill break_value, has_coverage, and outcomes for markets';

    private string $gammaBase = 'https://gamma-api.polymarket.com';

    public function handle(): int
    {
        $this->fillCryptoBreakValues();
        $this->fillCoverage();
        $this->resolveExpiredCrypto();
        $this->resolveExpiredWeather();

        return self::SUCCESS;
    }

    // ── 1. Crypto break_value ─────────────────────────────────────────────

    private function fillCryptoBreakValues(): void
    {
        $markets = DB::table('markets')
            ->where('category', 'crypto')
            ->where('break_value', 0)
            ->get(['id', 'asset_id', 'open_ts']);

        $filled = 0;
        foreach ($markets as $market) {
            $tick = DB::table('oracle_ticks')
                ->where('asset_id', $market->asset_id)
                ->orderByRaw('ABS(ts - ?)', [$market->open_ts])
                ->limit(1)
                ->first(['price_usd', 'ts']);

            if ($tick && abs($tick->ts - $market->open_ts) <= 300_000) {
                DB::table('markets')
                    ->where('id', $market->id)
                    ->update(['break_value' => $tick->price_usd]);
                $filled++;
            }
        }

        if ($filled > 0) {
            Log::info("[backfill] Filled break_value for {$filled} crypto markets.");
            $this->line("[backfill] break_value: filled {$filled} crypto markets.");
        }
    }

    // ── 2. has_coverage ───────────────────────────────────────────────────

    private function fillCoverage(): void
    {
        // Only check markets opened in the last 7 days — older uncovered markets
        // will never get clob data. Time-bounding the EXISTS subquery lets
        // TimescaleDB prune old chunks and avoids full 6.5 GB scans.
        $sevenDaysAgoMs = (int) ((microtime(true) - 7 * 86400) * 1000);
        $oneDayAgoMs    = (int) ((microtime(true) - 86400) * 1000);

        $updated = DB::table('markets')
            ->where('has_coverage', false)
            ->where('open_ts', '>', $sevenDaysAgoMs)
            ->whereExists(function ($q) use ($oneDayAgoMs) {
                $q->select(DB::raw(1))
                  ->from('clob_snapshots')
                  ->whereColumn('clob_snapshots.market_id', 'markets.id')
                  ->where('ts', '>', $oneDayAgoMs)
                  ->limit(1);
            })
            ->update(['has_coverage' => true]);

        if ($updated > 0) {
            Log::info("[backfill] has_coverage: updated {$updated} markets.");
            $this->line("[backfill] has_coverage: updated {$updated} markets.");
        }
    }

    // ── 3. Resolve expired crypto markets ─────────────────────────────────

    private function resolveExpiredCrypto(): void
    {
        $nowMs = (int) (microtime(true) * 1000);

        $expired = DB::table('markets')
            ->where('category', 'crypto')
            ->whereNull('outcome')
            ->where('close_ts', '<', $nowMs - 60_000) // at least 60s past close
            ->whereNotNull('gamma_slug')
            ->get(['id', 'gamma_slug']);

        $resolved = 0;
        foreach ($expired as $market) {
            try {
                $resp = Http::withUserAgent('Mozilla/5.0')
                    ->timeout(8)
                    ->get("{$this->gammaBase}/markets", ['slug' => $market->gamma_slug]);

                if (! $resp->successful()) {
                    continue;
                }

                $data = $resp->json();
                if (empty($data) || ! is_array($data)) {
                    continue;
                }

                $entry = $data[0] ?? null;
                if (! $entry) {
                    continue;
                }

                // Determine outcome from resolution fields
                $outcome = null;
                if (isset($entry['winner'])) {
                    $outcome = strtoupper($entry['winner']) === 'YES' ? 'YES' : 'NO';
                } elseif (isset($entry['resolution'])) {
                    $r = strtolower($entry['resolution']);
                    if ($r === 'yes') {
                        $outcome = 'YES';
                    } elseif ($r === 'no') {
                        $outcome = 'NO';
                    }
                }

                if ($outcome) {
                    DB::table('markets')
                        ->where('id', $market->id)
                        ->update([
                            'outcome'    => $outcome,
                            'recording_gap' => false,
                        ]);
                    $resolved++;
                }
            } catch (\Throwable $e) {
                Log::debug("[backfill] Failed to resolve {$market->id}: " . $e->getMessage());
            }
        }

        if ($resolved > 0) {
            Log::info("[backfill] Resolved {$resolved} expired crypto markets via Gamma.");
            $this->line("[backfill] outcomes: resolved {$resolved} expired crypto markets.");
        }
    }

    // ── 4. Resolve expired weather markets ────────────────────────────────
    //
    // Primary: derive outcome from our own sensor data (weather_readings).
    // The daily max for the market's local date is already recorded by the
    // weather recorder. We compare it to the market's break_value using the
    // bracket type encoded in the market ID suffix.
    //
    // Fallback: Gamma API — used only when we have no readings for that date
    // (e.g. recorder was offline, or market predates this service).

    private function resolveExpiredWeather(): void
    {
        $nowMs = (int) (microtime(true) * 1000);

        $expired = DB::table('markets')
            ->where('markets.category', 'weather')
            ->whereNull('markets.outcome')
            ->where('markets.close_ts', '<', $nowMs - 60_000)
            ->whereNotNull('markets.gamma_slug')
            ->select('markets.id', 'markets.asset_id', 'markets.gamma_slug', 'markets.group_item_title')
            ->get();

        $resolvedSensor = 0;
        $resolvedGamma  = 0;

        foreach ($expired as $market) {
            // Extract local date from slug: "...on-april-3-2026"
            if (! preg_match('/on-(\w+)-(\d+)-(\d{4})$/', $market->gamma_slug, $m)) {
                continue;
            }
            $localDate = date('Y-m-d', strtotime("{$m[1]} {$m[2]}, {$m[3]}"));
            if (! $localDate) {
                continue;
            }

            // Try sensor data first.
            // US cities (°F markets) use temp_f for comparison; others use running_daily_max_c.
            $usesFahrenheit = $market->group_item_title && str_contains($market->group_item_title, '°F');

            $dailyMax = $usesFahrenheit
                ? DB::table('weather_readings')
                    ->where('asset_id', $market->asset_id)
                    ->where('station_local_date', $localDate)
                    ->max('temp_f')
                : DB::table('weather_readings')
                    ->where('asset_id', $market->asset_id)
                    ->where('station_local_date', $localDate)
                    ->max('running_daily_max_c');

            if ($dailyMax !== null) {
                // Prefer group_item_title (unambiguous) over bracket suffix (derived)
                $outcome = $market->group_item_title
                    ? $this->resolveWeatherByTitle($market->group_item_title, (float) $dailyMax)
                    : $this->resolveWeatherBracket(substr($market->id, strlen($market->gamma_slug) + 1), (float) $dailyMax);

                if ($outcome) {
                    DB::table('markets')
                        ->where('id', $market->id)
                        ->update(['outcome' => $outcome, 'recording_gap' => false]);
                    $resolvedSensor++;
                    Log::debug("[backfill] Weather {$market->id}: max={$dailyMax}°C title=\"{$market->group_item_title}\" → {$outcome}");
                }
                continue; // don't also hit Gamma for this market
            }

            // No sensor data for that date — fall back to Gamma
            $outcome = $this->fetchWeatherOutcomeFromGamma($market->id, $market->gamma_slug);
            if ($outcome) {
                DB::table('markets')
                    ->where('id', $market->id)
                    ->update(['outcome' => $outcome, 'recording_gap' => false]);
                $resolvedGamma++;
            }
        }

        if ($resolvedSensor > 0) {
            Log::info("[backfill] Resolved {$resolvedSensor} weather markets via sensor data.");
            $this->line("[backfill] outcomes: {$resolvedSensor} weather markets resolved via sensor data.");
        }
        if ($resolvedGamma > 0) {
            Log::info("[backfill] Resolved {$resolvedGamma} weather markets via Gamma fallback.");
            $this->line("[backfill] outcomes: {$resolvedGamma} weather markets resolved via Gamma fallback.");
        }
    }

    /**
     * Resolve using Polymarket's groupItemTitle — the unambiguous bracket label.
     * $dailyMax must already be in the correct unit (°C or °F) — caller decides.
     *
     * Observed formats:
     *   "11°C or below"   → YES if round(dailyMax) ≤ 11
     *   "21°C or higher"  → YES if round(dailyMax) ≥ 21
     *   "13°C"            → YES if round(dailyMax) == 13
     *   "39°F or below"   → YES if round(dailyMax) ≤ 39
     *   "58°F or higher"  → YES if round(dailyMax) ≥ 58
     *   "42-43°F"         → YES if 42 ≤ dailyMax < 43  (2°F-wide range bucket)
     */
    private function resolveWeatherByTitle(string $title, float $dailyMax): ?string
    {
        $rounded = (int) round($dailyMax);

        // "X°C/°F or below / or under / or less"
        if (preg_match('/(-?\d+)°?\s*[CF]\s+or\s+(?:below|under|less)/i', $title, $m)) {
            return $rounded <= (int) $m[1] ? 'YES' : 'NO';
        }

        // "X°C/°F or higher / or above / or more"
        if (preg_match('/(-?\d+)°?\s*[CF]\s+or\s+(?:higher|above|more)/i', $title, $m)) {
            return $rounded >= (int) $m[1] ? 'YES' : 'NO';
        }

        // "X-Y°F" or "X-Y°C" range (e.g. "42-43°F")
        if (preg_match('/^(-?\d+)-(-?\d+)°?\s*[CF]$/i', $title, $m)) {
            $lo = (int) $m[1];
            $hi = (int) $m[2];
            return ($dailyMax >= $lo && $dailyMax < $hi) ? 'YES' : 'NO';
        }

        // "X°C" / "X°F" exact bracket
        if (preg_match('/^(-?\d+)°?\s*[CF]$/i', $title, $m)) {
            return $rounded === (int) $m[1] ? 'YES' : 'NO';
        }

        return null;
    }

    /**
     * Resolve using the bracket suffix encoded in the market ID.
     * Used only for markets created before group_item_title was stored.
     *
     * Bracket formats:
     *   above-20   → YES if dailyMax >= 20
     *   below-11   → YES if dailyMax <= 11  ("or below" = inclusive)
     *   15-20      → YES if 15 <= dailyMax < 20
     *   13         → YES if round(dailyMax) == 13  (exact-degree)
     */
    private function resolveWeatherBracket(string $bracketSuffix, float $dailyMax): ?string
    {
        // above-X
        if (preg_match('/^above-(-?[\d_]+)$/i', $bracketSuffix, $m)) {
            $threshold = (float) str_replace('_', '.', $m[1]);
            return $dailyMax >= $threshold ? 'YES' : 'NO';
        }

        // below-X (inclusive — Polymarket uses "or below")
        if (preg_match('/^below-(-?[\d_]+)$/i', $bracketSuffix, $m)) {
            $threshold = (float) str_replace('_', '.', $m[1]);
            return $dailyMax <= $threshold ? 'YES' : 'NO';
        }

        // X-Y range (handles negatives: "-5-0")
        if (preg_match('/^(-?[\d_]+)-(-?[\d_]+)$/', $bracketSuffix, $m)) {
            $lo = (float) str_replace('_', '.', $m[1]);
            $hi = (float) str_replace('_', '.', $m[2]);
            return ($dailyMax >= $lo && $dailyMax < $hi) ? 'YES' : 'NO';
        }

        // Plain integer: exact-degree bracket (floor match)
        if (preg_match('/^-?\d+$/', $bracketSuffix)) {
            return ((int) round($dailyMax) === (int) $bracketSuffix) ? 'YES' : 'NO';
        }

        return null;
    }

    private function fetchWeatherOutcomeFromGamma(string $marketId, string $gammaSlug): ?string
    {
        try {
            $resp = Http::withUserAgent('Mozilla/5.0')
                ->timeout(8)
                ->get("{$this->gammaBase}/markets", ['slug' => $gammaSlug]);

            if (! $resp->successful()) {
                return null;
            }

            $data  = $resp->json();
            $entry = is_array($data) ? ($data[0] ?? null) : null;
            if (! $entry) {
                return null;
            }

            if (isset($entry['winner'])) {
                return strtoupper($entry['winner']) === 'YES' ? 'YES' : 'NO';
            }
            if (isset($entry['resolution'])) {
                $r = strtolower($entry['resolution']);
                if ($r === 'yes') return 'YES';
                if ($r === 'no')  return 'NO';
            }
        } catch (\Throwable $e) {
            Log::debug("[backfill] Gamma fallback failed for {$marketId}: " . $e->getMessage());
        }

        return null;
    }
}
