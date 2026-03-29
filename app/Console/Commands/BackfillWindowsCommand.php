<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Backfills two missing fields on the `windows` table:
 *
 *   break_price_usd — oracle price at open_ts, looked up from oracle_ticks
 *   outcome         — YES/NO for expired windows, fetched from Gamma outcomePrices
 */
class BackfillWindowsCommand extends Command
{
    protected $signature   = 'recorder:backfill-windows';
    protected $description = 'Backfill break_price_usd and outcome on windows from oracle_ticks + Gamma API';

    public function handle(): int
    {
        $this->backfillBreakPrices();
        $this->backfillOutcomes();

        return self::SUCCESS;
    }

    // ── Break prices ──────────────────────────────────────────────────────────

    private function backfillBreakPrices(): void
    {
        $windows = DB::table('windows')
            ->where(fn ($q) => $q->where('break_price_usd', 0)->orWhereNull('break_price_usd'))
            ->get(['id', 'asset_id', 'open_ts', 'gamma_slug']);

        $this->info("Backfilling break_price_usd for {$windows->count()} windows...");

        $updated = 0;
        foreach ($windows as $w) {
            // Find the oracle tick closest to open_ts for this asset
            $tick = DB::table('oracle_ticks')
                ->where('asset_id', $w->asset_id)
                ->orderByRaw('ABS(ts - ?)', [$w->open_ts])
                ->limit(1)
                ->first(['price_usd', 'ts']);

            if (!$tick) {
                continue;
            }

            // Only use ticks within 5 minutes of open_ts to avoid garbage data
            if (abs($tick->ts - $w->open_ts) > 300_000) {
                continue;
            }

            $priceUsd = (float) $tick->price_usd;
            DB::table('windows')->where('id', $w->id)->update([
                'break_price_usd' => $priceUsd,
                'break_price_bp'  => (int) ($priceUsd * 100),
            ]);
            $updated++;
        }

        $this->info("  → Updated {$updated} windows with break_price_usd");
    }

    // ── Outcomes ──────────────────────────────────────────────────────────────

    private function backfillOutcomes(): void
    {
        $nowMs = (int) (microtime(true) * 1000);

        $windows = DB::table('windows')
            ->whereNull('outcome')
            ->where('close_ts', '<', $nowMs)
            ->get(['id', 'gamma_slug', 'condition_id']);

        $this->info("Backfilling outcomes for {$windows->count()} expired windows...");

        $yes = 0;
        $no  = 0;
        $skip = 0;

        foreach ($windows as $w) {
            $outcome = $this->fetchOutcome($w->gamma_slug);
            if (!$outcome) {
                $skip++;
                continue;
            }

            DB::table('windows')->where('id', $w->id)->update([
                'outcome'     => $outcome,
                'resolved_ts' => $nowMs,
            ]);

            if ($outcome === 'YES') $yes++;
            else $no++;

            usleep(100_000); // 100ms between Gamma calls to avoid rate limiting
        }

        $this->info("  → YES: {$yes}  NO: {$no}  Skipped: {$skip}");
    }

    private function fetchOutcome(string $slug): ?string
    {
        try {
            $r = Http::withUserAgent('Mozilla/5.0')
                ->timeout(8)
                ->get('https://gamma-api.polymarket.com/events', ['slug' => $slug]);

            if (!$r->successful()) {
                return null;
            }

            $market = $r->json()[0]['markets'][0] ?? null;
            if (!$market) {
                return null;
            }

            // Only resolve closed markets
            if (empty($market['closed'])) {
                return null;
            }

            $prices = json_decode($market['outcomePrices'] ?? '[]', true);
            if (!is_array($prices) || count($prices) < 2) {
                return null;
            }

            // outcomePrices[0] = YES price, outcomePrices[1] = NO price
            // Resolved: one is 1.0, the other is 0.0
            $yesPrice = (float) $prices[0];
            $noPrice  = (float) $prices[1];

            if ($yesPrice >= 0.99) return 'YES';
            if ($noPrice  >= 0.99) return 'NO';

            return null; // Still pending or ambiguous
        } catch (\Throwable) {
            return null;
        }
    }
}
