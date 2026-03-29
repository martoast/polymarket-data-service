<?php

namespace App\Recorder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovers Polymarket crypto binary markets via the Gamma API and upserts
 * them into the `windows` table. Returns newly-discovered token IDs so the
 * CLOB feed can subscribe to them.
 */
class MarketDiscoveryService
{
    /** @var array<string, bool> slug → already-discovered */
    private array $seen = [];

    /** @var array<string, int> asset symbol → asset_id cache */
    private array $assetIdCache = [];

    /**
     * Run one discovery pass for all enabled assets.
     *
     * Returns array of entries for newly-discovered markets:
     *   [['window_id' => slug, 'yes_token_id' => '0x...', 'no_token_id' => '0x...', 'is_yes' => bool], ...]
     *
     * @return array<int, array{window_id:string, yes_token_id:string, no_token_id:string}>
     */
    public function discover(): array
    {
        $newMarkets = [];
        $gammaBase  = rtrim(config('services.recorder.gamma_base', 'https://gamma-api.polymarket.com'), '/');

        foreach (AssetConfig::enabledAssets() as $asset) {
            $config = AssetConfig::ASSETS[$asset];

            foreach (AssetConfig::DURATIONS as $durationSec) {
                $slugs = $this->generateSlugs($config['slug_prefix'], $durationSec);

                foreach ($slugs as $slug) {
                    if (isset($this->seen[$slug])) {
                        continue;
                    }

                    $market = $this->fetchMarket($gammaBase, $slug);
                    if (!$market) {
                        continue;
                    }

                    $this->seen[$slug] = true;
                    $entry = $this->upsertWindow($asset, $slug, $market);
                    if ($entry) {
                        $newMarkets[] = $entry;
                    }
                }
            }
        }

        return $newMarkets;
    }

    /**
     * Load the full token→window map from DB for all known windows.
     * Call this on recorder start to pre-populate the cache.
     *
     * @return array<string, array{window_id:string, is_yes:bool}>  tokenId → {window_id, is_yes}
     */
    public function loadTokenMap(): array
    {
        $map  = [];
        $rows = DB::table('windows')
            ->whereNotNull('yes_token_id')
            ->select('id', 'yes_token_id', 'no_token_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->yes_token_id) {
                $map[$row->yes_token_id] = ['window_id' => $row->id, 'is_yes' => true];
                $this->seen[$row->id] = true;
            }
            if ($row->no_token_id) {
                $map[$row->no_token_id] = ['window_id' => $row->id, 'is_yes' => false];
            }
        }

        return $map;
    }

    /**
     * Load only ACTIVE (not yet resolved, not yet closed) token→window map.
     * Used for the initial CLOB subscription so we don't send 700+ stale tokens.
     *
     * @return array<string, array{window_id:string, is_yes:bool}>
     */
    public function loadActiveTokenMap(): array
    {
        $map   = [];
        $nowMs = (int) (microtime(true) * 1000);
        $rows  = DB::table('windows')
            ->whereNotNull('yes_token_id')
            ->whereNull('outcome')
            ->where('close_ts', '>', $nowMs)
            ->select('id', 'yes_token_id', 'no_token_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->yes_token_id) {
                $map[$row->yes_token_id] = ['window_id' => $row->id, 'is_yes' => true];
                $this->seen[$row->id] = true;
            }
            if ($row->no_token_id) {
                $map[$row->no_token_id] = ['window_id' => $row->id, 'is_yes' => false];
            }
        }

        return $map;
    }

    /**
     * Generate slug candidates for a given asset prefix + duration.
     * Covers: previous window, current window, next 3 windows.
     */
    private function generateSlugs(string $prefix, int $durationSec): array
    {
        $now   = time();
        $base  = (int) (floor($now / $durationSec) * $durationSec);
        $slugs = [];

        for ($i = -1; $i <= 3; $i++) {
            $openTs  = $base + ($i * $durationSec);
            $label   = $durationSec === 300 ? '5m' : '15m';
            $slugs[] = "{$prefix}-updown-{$label}-{$openTs}";
        }

        return $slugs;
    }

    /** Fetch a single market from Gamma API by slug. Returns raw market array or null. */
    private function fetchMarket(string $gammaBase, string $slug): ?array
    {
        try {
            $response = Http::withUserAgent('Mozilla/5.0')
                ->timeout(8)
                ->get("{$gammaBase}/events", ['slug' => $slug]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (empty($data[0]['markets'][0])) {
                return null;
            }

            return $data[0]['markets'][0];
        } catch (\Throwable $e) {
            Log::debug("[discovery] Failed to fetch {$slug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upsert discovered market into `windows` table.
     * Returns entry with token IDs if newly inserted, null if already known.
     *
     * @return array{window_id:string,yes_token_id:string,no_token_id:string}|null
     */
    private function upsertWindow(string $asset, string $slug, array $market): ?array
    {
        $assetId = $this->getAssetId($asset);
        if ($assetId === null) {
            Log::warning("[discovery] No asset_id for {$asset}");
            return null;
        }

        // Parse open_ts from slug: {prefix}-updown-{dur}-{open_ts_sec}
        $parts      = explode('-', $slug);
        $openTs     = ((int) end($parts)) * 1000; // ms
        $durLabel   = $parts[count($parts) - 2] ?? '5m';
        $durationSec = $durLabel === '15m' ? 900 : 300;
        $closeTs    = $openTs + ($durationSec * 1000);

        $breakPriceUsd = $this->parseBreakPrice($market);
        $conditionId   = $market['conditionId'] ?? null;

        $tokens    = json_decode($market['clobTokenIds'] ?? '[]', true);
        $yesToken  = is_array($tokens) && isset($tokens[0]) ? (string) $tokens[0] : null;
        $noToken   = is_array($tokens) && isset($tokens[1]) ? (string) $tokens[1] : null;

        $existing = DB::table('windows')->where('id', $slug)->first();
        if (!$existing) {
            // Use oracle tick closest to open_ts as break price (within 5 min)
            $oracleTick = DB::table('oracle_ticks')
                ->where('asset_id', $assetId)
                ->orderByRaw('ABS(ts - ?)', [$openTs])
                ->limit(1)
                ->first(['price_usd', 'ts']);

            if ($oracleTick && abs($oracleTick->ts - $openTs) <= 300_000) {
                $breakPriceUsd = (float) $oracleTick->price_usd;
            }

            DB::table('windows')->insertOrIgnore([
                'id'              => $slug,
                'asset_id'        => $assetId,
                'duration_sec'    => $durationSec,
                'break_price_usd' => $breakPriceUsd,
                'break_price_bp'  => (int) ($breakPriceUsd * 100),
                'open_ts'         => $openTs,
                'close_ts'        => $closeTs,
                'condition_id'    => $conditionId,
                'gamma_slug'      => $slug,
                'yes_token_id'    => $yesToken,
                'no_token_id'     => $noToken,
            ]);

            echo "[discovery] Discovered: {$slug} yes={$yesToken} no={$noToken}" . PHP_EOL;

            if ($yesToken && $noToken) {
                return ['window_id' => $slug, 'yes_token_id' => $yesToken, 'no_token_id' => $noToken];
            }
            return null;
        }

        // Update condition_id + token IDs if they were null
        $updates = [];
        if (!$existing->condition_id && $conditionId) {
            $updates['condition_id'] = $conditionId;
        }
        if (!$existing->yes_token_id && $yesToken) {
            $updates['yes_token_id'] = $yesToken;
            $updates['no_token_id']  = $noToken;
        }
        if ($updates) {
            DB::table('windows')->where('id', $slug)->update($updates);
        }

        // Return token entry even for existing windows if we now have token IDs
        if ($yesToken && $noToken) {
            return ['window_id' => $slug, 'yes_token_id' => $yesToken, 'no_token_id' => $noToken];
        }
        return null;
    }

    private function parseBreakPrice(array $market): float
    {
        // Break price is stored in "description" or can be inferred from question
        // For crypto binary markets the break price is the oracle price at window open.
        // Gamma often exposes it in the question text: "Will BTC be above $84,500..."
        // We store 0 if we can't parse it — it can be backfilled later.
        $question = $market['question'] ?? '';
        if (preg_match('/\$([0-9,]+(?:\.[0-9]+)?)/', $question, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }
        return 0.0;
    }

    private function getAssetId(string $symbol): ?int
    {
        if (isset($this->assetIdCache[$symbol])) {
            return $this->assetIdCache[$symbol];
        }
        $id = DB::table('assets')->where('symbol', $symbol)->value('id');
        if ($id !== null) {
            $this->assetIdCache[$symbol] = (int) $id;
        }
        return isset($this->assetIdCache[$symbol]) ? $this->assetIdCache[$symbol] : null;
    }
}
