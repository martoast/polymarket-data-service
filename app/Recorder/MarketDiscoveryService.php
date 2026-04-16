<?php

namespace App\Recorder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovers Polymarket crypto binary markets via the Gamma API and upserts
 * them into the `markets` table. Returns newly-discovered token IDs so the
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
     * @return array<int, array{market_id:string, yes_token_id:string, no_token_id:string}>
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
                    $entry = $this->upsertMarket($asset, $slug, $market);
                    if ($entry) {
                        $newMarkets[] = $entry;
                    }
                }
            }
        }

        return $newMarkets;
    }

    /**
     * Load the full token→market map from DB for all known markets.
     *
     * @return array<string, array{market_id:string, is_yes:bool}>
     */
    public function loadTokenMap(): array
    {
        $map  = [];
        $rows = DB::table('markets')
            ->whereNotNull('yes_token_id')
            ->select('id', 'yes_token_id', 'no_token_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->yes_token_id) {
                $map[$row->yes_token_id] = ['market_id' => $row->id, 'is_yes' => true];
                $this->seen[$row->id]    = true;
            }
            if ($row->no_token_id) {
                $map[$row->no_token_id] = ['market_id' => $row->id, 'is_yes' => false];
            }
        }

        return $map;
    }

    /**
     * Load only ACTIVE (not yet resolved, not yet closed) token→market map.
     * Includes markets that closed within the last 60s so the subscription
     * stays live long enough to receive the market_result WS event.
     *
     * @return array<string, array{market_id:string, is_yes:bool}>
     */
    public function loadActiveTokenMap(): array
    {
        $map   = [];
        $nowMs = (int) (microtime(true) * 1000);
        $rows  = DB::table('markets')
            ->whereNotNull('yes_token_id')
            ->whereNull('outcome')
            ->where('close_ts', '>', $nowMs - 60_000)  // stay subscribed 60s past close
            ->select('id', 'yes_token_id', 'no_token_id')
            ->get();

        foreach ($rows as $row) {
            if ($row->yes_token_id) {
                $map[$row->yes_token_id] = ['market_id' => $row->id, 'is_yes' => true];
                $this->seen[$row->id]    = true;
            }
            if ($row->no_token_id) {
                $map[$row->no_token_id] = ['market_id' => $row->id, 'is_yes' => false];
            }
        }

        return $map;
    }

    /**
     * Generate slug candidates for a given asset prefix + duration.
     * Covers: previous market, current market, next 3 markets.
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
     * Upsert discovered market into `markets` table.
     *
     * @return array{market_id:string, yes_token_id:string, no_token_id:string}|null
     */
    private function upsertMarket(string $asset, string $slug, array $market): ?array
    {
        $assetId = $this->getAssetId($asset);
        if ($assetId === null) {
            Log::warning("[discovery] No asset_id for {$asset}");
            return null;
        }

        // Parse open_ts from slug: {prefix}-updown-{dur}-{open_ts_sec}
        $parts       = explode('-', $slug);
        $openTs      = ((int) end($parts)) * 1000; // ms
        $durLabel    = $parts[count($parts) - 2] ?? '5m';
        $durationSec = $durLabel === '15m' ? 900 : 300;
        $closeTs     = $openTs + ($durationSec * 1000);

        $breakValue  = $this->parseBreakValue($market);
        $conditionId = $market['conditionId'] ?? null;

        $tokens   = json_decode($market['clobTokenIds'] ?? '[]', true);
        $yesToken = is_array($tokens) && isset($tokens[0]) ? (string) $tokens[0] : null;
        $noToken  = is_array($tokens) && isset($tokens[1]) ? (string) $tokens[1] : null;

        $existing = DB::table('markets')->where('id', $slug)->first();
        if (!$existing) {
            // Use oracle tick closest to open_ts as break value (within 5 min)
            $oracleTick = DB::table('oracle_ticks')
                ->where('asset_id', $assetId)
                ->orderByRaw('ABS(ts - ?)', [$openTs])
                ->limit(1)
                ->first(['price_usd', 'ts']);

            if ($oracleTick && abs($oracleTick->ts - $openTs) <= 300_000) {
                $breakValue = (float) $oracleTick->price_usd;
            }

            DB::table('markets')->insertOrIgnore([
                'id'           => $slug,
                'category'     => 'crypto',
                'asset_id'     => $assetId,
                'duration_sec' => $durationSec,
                'duration_label' => $durLabel,
                'break_value'  => $breakValue,
                'value_unit'   => 'usd',
                'open_ts'      => $openTs,
                'close_ts'     => $closeTs,
                'condition_id' => $conditionId,
                'gamma_slug'   => $slug,
                'yes_token_id' => $yesToken,
                'no_token_id'  => $noToken,
            ]);

            echo "[discovery] Discovered: {$slug} yes={$yesToken} no={$noToken}" . PHP_EOL;

            if ($yesToken && $noToken) {
                return ['market_id' => $slug, 'yes_token_id' => $yesToken, 'no_token_id' => $noToken];
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
            DB::table('markets')->where('id', $slug)->update($updates);
        }

        if ($yesToken && $noToken) {
            return ['market_id' => $slug, 'yes_token_id' => $yesToken, 'no_token_id' => $noToken];
        }
        return null;
    }

    private function parseBreakValue(array $market): float
    {
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
        return $this->assetIdCache[$symbol] ?? null;
    }
}
