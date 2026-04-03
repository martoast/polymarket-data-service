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
        $updated = DB::table('markets')
            ->where('has_coverage', false)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('clob_snapshots')
                  ->whereColumn('clob_snapshots.market_id', 'markets.id')
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
}
