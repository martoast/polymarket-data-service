<?php

namespace App\Recorder\Weather;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovers Polymarket weather markets via the Gamma API and upserts
 * them into the `markets` table. Handles Tokyo temperature bracket markets
 * and other date-based weather events.
 */
class WeatherMarketDiscoveryService
{
    /** @var array<string, bool> event_slug → already-seen */
    private array $seen = [];

    /** @var array<string, int> asset symbol → asset_id cache */
    private array $assetIdCache = [];

    /**
     * Run one discovery pass for all active weather assets.
     *
     * @return array<int, array{market_id:string, yes_token_id:string, no_token_id:string}>
     */
    public function discover(): array
    {
        $newMarkets = [];
        $gammaBase  = rtrim(config('services.recorder.gamma_base', 'https://gamma-api.polymarket.com'), '/');

        $weatherAssets = DB::table('assets')
            ->join('categories', 'assets.category_id', '=', 'categories.id')
            ->where('categories.slug', 'weather')
            ->where('assets.is_active', true)
            ->get(['assets.id', 'assets.symbol', 'assets.source_config']);

        foreach ($weatherAssets as $asset) {
            $sourceConfig = json_decode($asset->source_config ?? '{}', true);
            $slugs        = $this->generateEventSlugs($asset->symbol, $sourceConfig);

            foreach ($slugs as $eventSlug) {
                if (isset($this->seen[$eventSlug])) {
                    continue;
                }

                $event = $this->fetchEvent($gammaBase, $eventSlug);
                if (!$event) {
                    continue;
                }

                $this->seen[$eventSlug] = true;

                foreach ($event['markets'] ?? [] as $market) {
                    $entries = $this->upsertMarket((int) $asset->id, $asset->symbol, $eventSlug, $event, $market);
                    foreach ($entries as $entry) {
                        $newMarkets[] = $entry;
                    }
                }
            }
        }

        return $newMarkets;
    }

    /**
     * Load the full token→market map for all weather markets.
     *
     * @return array<string, array{market_id:string, is_yes:bool}>
     */
    public function loadTokenMap(): array
    {
        $map  = [];
        $rows = DB::table('markets')
            ->where('category', 'weather')
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
     * Load only ACTIVE weather market tokens for CLOB subscription.
     *
     * @return array<string, array{market_id:string, is_yes:bool}>
     */
    public function loadActiveTokenMap(): array
    {
        $map   = [];
        $nowMs = (int) (microtime(true) * 1000);

        $rows = DB::table('markets')
            ->where('category', 'weather')
            ->whereNotNull('yes_token_id')
            ->whereNull('outcome')
            ->where('close_ts', '>', $nowMs)
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
     * Generate Gamma event slug candidates for a weather asset.
     * Covers today + next 3 days in the station's local timezone.
     *
     * Example: "highest-temperature-in-tokyo-on-april-3-2026"
     */
    private function generateEventSlugs(string $symbol, array $sourceConfig): array
    {
        $tz   = $sourceConfig['timezone'] ?? 'UTC';
        $city = strtolower($sourceConfig['city'] ?? $symbol);

        $slugs = [];
        $now   = new \DateTime('now', new \DateTimeZone($tz));

        for ($i = 0; $i <= 3; $i++) {
            $date   = clone $now;
            $date->modify("+{$i} days");
            // Format: "april-3-2026" (no zero-padding on day)
            $month  = strtolower($date->format('F'));
            $day    = (int) $date->format('j');
            $year   = $date->format('Y');
            $slugs[] = "highest-temperature-in-{$city}-on-{$month}-{$day}-{$year}";
        }

        return $slugs;
    }

    private function fetchEvent(string $gammaBase, string $slug): ?array
    {
        try {
            $response = Http::withUserAgent('Mozilla/5.0')
                ->timeout(10)
                ->get("{$gammaBase}/events", ['slug' => $slug]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return $data[0] ?? null;
        } catch (\Throwable $e) {
            Log::debug("[weather-discovery] Failed to fetch {$slug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upsert a single market within a weather event.
     *
     * @return array<int, array{market_id:string, yes_token_id:string, no_token_id:string}>
     */
    private function upsertMarket(int $assetId, string $symbol, string $eventSlug, array $event, array $market): array
    {
        $conditionId = $market['conditionId'] ?? null;
        $question    = $market['question'] ?? '';
        $tokens      = json_decode($market['clobTokenIds'] ?? '[]', true);
        $yesToken    = is_array($tokens) && isset($tokens[0]) ? (string) $tokens[0] : null;
        $noToken     = is_array($tokens) && isset($tokens[1]) ? (string) $tokens[1] : null;

        // Parse temperature bracket from question
        [$breakValue, $bracketSlug] = $this->parseTemperature($question);

        // Build a stable market ID from event slug + bracket
        $marketId = $eventSlug . ($bracketSlug ? '-' . $bracketSlug : '');

        // Parse open/close timestamps from event
        [$openTs, $closeTs] = $this->parseEventTimestamps($event);

        $existing = DB::table('markets')->where('id', $marketId)->first();
        if (!$existing) {
            DB::table('markets')->insertOrIgnore([
                'id'             => $marketId,
                'category'       => 'weather',
                'asset_id'       => $assetId,
                'duration_sec'   => max(1, (int) (($closeTs - $openTs) / 1000)),
                'duration_label' => '1d',
                'break_value'    => $breakValue,
                'value_unit'     => 'celsius',
                'open_ts'        => $openTs,
                'close_ts'       => $closeTs,
                'condition_id'   => $conditionId,
                'gamma_slug'     => $eventSlug,
                'yes_token_id'   => $yesToken,
                'no_token_id'    => $noToken,
            ]);

            echo "[weather-discovery] Discovered: {$marketId} ({$question})" . PHP_EOL;
        } else {
            $updates = [];
            if (!$existing->condition_id && $conditionId) {
                $updates['condition_id'] = $conditionId;
            }
            if (!$existing->yes_token_id && $yesToken) {
                $updates['yes_token_id'] = $yesToken;
                $updates['no_token_id']  = $noToken;
            }
            if ($updates) {
                DB::table('markets')->where('id', $marketId)->update($updates);
            }
        }

        if ($yesToken && $noToken) {
            return [['market_id' => $marketId, 'yes_token_id' => $yesToken, 'no_token_id' => $noToken]];
        }
        return [];
    }

    /**
     * Parse temperature info from question string.
     * "Will the high temperature in Tokyo ... be 15-20°C?" → [15.0, '15-20']
     * "Will ... be above 20°C?" → [20.0, 'above-20']
     * "Will ... be below 5°C?"  → [5.0, 'below-5']
     */
    private function parseTemperature(string $question): array
    {
        // Range: "15-20°C" or "15 to 20°C"
        if (preg_match('/(-?\d+(?:\.\d+)?)\s*(?:-|to)\s*(-?\d+(?:\.\d+)?)°?C/i', $question, $m)) {
            $lo = (float) $m[1];
            $hi = (float) $m[2];
            return [$lo, str_replace('.', '_', "{$lo}-{$hi}")];
        }

        // Above/over: "above 20°C"
        if (preg_match('/above\s+(-?\d+(?:\.\d+)?)°?C/i', $question, $m)) {
            $val = (float) $m[1];
            return [$val, 'above-' . str_replace('.', '_', (string) $val)];
        }

        // Below/under: "below 5°C"
        if (preg_match('/below\s+(-?\d+(?:\.\d+)?)°?C/i', $question, $m)) {
            $val = (float) $m[1];
            return [$val, 'below-' . str_replace('.', '_', (string) $val)];
        }

        // Single value fallback
        if (preg_match('/(-?\d+(?:\.\d+)?)°?C/i', $question, $m)) {
            $val = (float) $m[1];
            return [$val, str_replace('.', '_', (string) $val)];
        }

        return [0.0, md5($question)];
    }

    /**
     * Parse open/close timestamps from Gamma event.
     * Falls back to midnight today → midnight tomorrow (UTC) if not available.
     */
    private function parseEventTimestamps(array $event): array
    {
        // Gamma events often have startDate / endDate as ISO strings
        $start = $event['startDate'] ?? $event['startTime'] ?? null;
        $end   = $event['endDate']   ?? $event['endTime']   ?? null;

        if ($start && $end) {
            try {
                $openTs  = (int) (new \DateTime($start))->format('Uv'); // ms
                $closeTs = (int) (new \DateTime($end))->format('Uv');
                return [$openTs, $closeTs];
            } catch (\Throwable) {
                // fall through
            }
        }

        // Fallback: today's UTC midnight + 24 hours
        $todayMidnight = strtotime('today 00:00:00 UTC') * 1000;
        return [$todayMidnight, $todayMidnight + 86_400_000];
    }
}
