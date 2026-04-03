<?php

namespace App\Console\Commands;

use App\Recorder\ClobFeedService;
use App\Recorder\RecorderState;
use App\Recorder\Weather\OpenMeteoService;
use App\Recorder\Weather\WeatherMarketDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use React\EventLoop\Loop;

class WeatherRecorderCommand extends Command
{
    protected $signature   = 'weather:start';
    protected $description = 'Start the Polymarket weather market recorder (long-running ReactPHP process)';

    /** token_id → ['market_id' => string, 'is_yes' => bool] */
    private array $tokenMap = [];

    /**
     * Per-market CLOB state — same pattern as the crypto recorder.
     *
     * market_id => [
     *   'asset_id' => int,
     *   'yes_bid'  => float|null,
     *   'yes_ask'  => float|null,
     *   'no_bid'   => float|null,
     *   'no_ask'   => float|null,
     *   'dirty'    => bool,
     *   'ts'       => int,
     * ]
     */
    private array $marketState = [];

    /** market_id → asset_id DB cache */
    private array $marketAssetCache = [];

    /**
     * In-memory running daily max per asset (symbol → float|null).
     * Reset at local midnight for each station.
     */
    private array $runningDailyMax = [];

    /** Last local date we recorded for each asset, used to detect midnight rollovers */
    private array $lastLocalDate = [];

    /** Dead-band filter: last written temp_c per asset symbol */
    private array $lastWrittenTemp = [];

    /** Dead-band filter: last write timestamp (ms) per asset symbol */
    private array $lastWrittenTs = [];

    private array $stats;

    public function handle(): int
    {
        $this->stats = $this->emptyStats();

        echo '[weather] Starting weather recorder' . PHP_EOL;

        $loop      = Loop::get();
        $openMeteo = new OpenMeteoService();
        $discovery = new WeatherMarketDiscoveryService();

        // Pre-populate token map from DB
        $this->tokenMap = $discovery->loadTokenMap();
        echo '[weather] Pre-loaded ' . count($this->tokenMap) . ' token mappings from DB' . PHP_EOL;

        // ── CLOB feed ────────────────────────────────────────────────────────
        $clob = new ClobFeedService(
            $loop,
            fn (array $msg) => $this->onClobPrice($msg),
            fn (array $msg) => $this->onMarketResolved($msg)
        );

        $activeTokens = array_keys($discovery->loadActiveTokenMap());
        if (!empty($activeTokens)) {
            $clob->subscribe($activeTokens);
            echo '[weather] Queued ' . count($activeTokens) . ' active token(s) for CLOB subscription' . PHP_EOL;
        }

        // ── Weather market discovery: run immediately + every 20s ─────────────
        $runDiscovery = function () use ($discovery, $clob) {
            $newMarkets = $discovery->discover();
            foreach ($newMarkets as $entry) {
                $this->tokenMap[$entry['yes_token_id']] = ['market_id' => $entry['market_id'], 'is_yes' => true];
                $this->tokenMap[$entry['no_token_id']]  = ['market_id' => $entry['market_id'], 'is_yes' => false];
            }

            $activeTokenMap = $discovery->loadActiveTokenMap();
            $clob->replaceSubscription(array_keys($activeTokenMap));

            $nowMs = (int) (microtime(true) * 1000);
            $this->stats['markets']['total']  = DB::table('markets')->where('category', 'weather')->count();
            $this->stats['markets']['active'] = DB::table('markets')
                ->where('category', 'weather')
                ->whereNull('outcome')
                ->where('close_ts', '>', $nowMs)
                ->count();
        };

        $runDiscovery();
        $loop->addPeriodicTimer(20, $runDiscovery);

        // ── Temperature polling: immediately + every 5 minutes ────────────────
        // Assets are re-queried each poll so new cities are picked up without restart.
        $pollTemperatures = function () use ($openMeteo) {
            $weatherAssets = DB::table('assets')
                ->join('categories', 'assets.category_id', '=', 'categories.id')
                ->where('categories.slug', 'weather')
                ->where('assets.is_active', true)
                ->get(['assets.id', 'assets.symbol', 'assets.unit', 'assets.source_config']);

            foreach ($weatherAssets as $asset) {
                $sourceConfig = json_decode($asset->source_config ?? '{}', true);
                $tz           = $sourceConfig['timezone'] ?? 'UTC';

                $reading = $openMeteo->currentTemp($sourceConfig);
                if (!$reading) {
                    continue;
                }

                $tempC    = $reading['temp_c'];
                $tempF    = $reading['temp_f'];
                $ts       = $reading['ts'];

                // Detect local date rollover → reset daily max
                $localDate = (new \DateTime('now', new \DateTimeZone($tz)))->format('Y-m-d');
                if (($this->lastLocalDate[$asset->symbol] ?? null) !== $localDate) {
                    $this->runningDailyMax[$asset->symbol] = $tempC;
                    $this->lastLocalDate[$asset->symbol]   = $localDate;
                    echo "[weather] {$asset->symbol} daily max reset for {$localDate}" . PHP_EOL;
                } else {
                    $this->runningDailyMax[$asset->symbol] = max(
                        $this->runningDailyMax[$asset->symbol] ?? $tempC,
                        $tempC
                    );
                }

                $runningMax = $this->runningDailyMax[$asset->symbol];

                // Dead-band filter: skip write if temp unchanged and < 30 min since last write
                $nowMs        = (int) (microtime(true) * 1000);
                $lastTemp     = $this->lastWrittenTemp[$asset->symbol] ?? null;
                $lastTs       = $this->lastWrittenTs[$asset->symbol] ?? 0;
                $sinceLastMs  = $nowMs - $lastTs;
                $tempChanged  = ($lastTemp === null || $tempC !== $lastTemp);
                $heartbeat    = ($sinceLastMs >= 1_800_000); // 30 min

                if (!$tempChanged && !$heartbeat) {
                    echo "[weather] {$asset->symbol} {$tempC}°C unchanged — skipping write" . PHP_EOL;
                    continue;
                }

                DB::table('weather_readings')->insert([
                    'asset_id'            => $asset->id,
                    'temp_c'              => $tempC,
                    'temp_f'              => $tempF,
                    'running_daily_max_c' => $runningMax,
                    'source'              => 'observed',
                    'station_local_date'  => $localDate,
                    'ts'                  => $ts,
                ]);

                $this->lastWrittenTemp[$asset->symbol] = $tempC;
                $this->lastWrittenTs[$asset->symbol]   = $nowMs;
                $this->stats['readings_written']++;
                $this->stats['current'][$asset->symbol] = [
                    'temp_c'             => $tempC,
                    'running_daily_max_c' => $runningMax,
                    'local_date'         => $localDate,
                    'last_ts'            => $ts,
                ];

                echo "[weather] {$asset->symbol} {$tempC}°C (daily max: {$runningMax}°C) — {$localDate}" . PHP_EOL;
            }
        };

        $pollTemperatures();
        $loop->addPeriodicTimer(300, $pollTemperatures); // every 5 minutes

        // ── Sampled CLOB flush every 1s ───────────────────────────────────────
        $loop->addPeriodicTimer(1, function () use ($clob) {
            $this->flushMarketState();
            $this->stats['clob']['connected']  = ($clob->status === 'connected');
            $this->stats['clob']['subscribed'] = $clob->subscribedCount();
        });

        // ── Status update every 5s ────────────────────────────────────────────
        $loop->addPeriodicTimer(5, function () {
            RecorderState::updateWeather($this->stats);
        });

        // ── Start CLOB connection ─────────────────────────────────────────────
        $clob->connect();

        // ── Graceful shutdown ─────────────────────────────────────────────────
        if (function_exists('pcntl_signal')) {
            $shutdown = function () use ($loop) {
                echo '[weather] Shutting down...' . PHP_EOL;
                $this->flushMarketState();
                $loop->stop();
            };
            pcntl_signal(SIGTERM, $shutdown);
            pcntl_signal(SIGINT, $shutdown);
            $loop->addPeriodicTimer(1, fn () => pcntl_signal_dispatch());
        }

        echo '[weather] Event loop running' . PHP_EOL;
        $loop->run();

        return self::SUCCESS;
    }

    // ── CLOB price handler ───────────────────────────────────────────────────

    private function onClobPrice(array $msg): void
    {
        $changes = $msg['price_changes'] ?? [];
        if (empty($changes)) {
            return;
        }

        $ts = isset($msg['timestamp']) ? (int) $msg['timestamp'] : (int) (microtime(true) * 1000);

        foreach ($changes as $change) {
            $tokenId = $change['asset_id'] ?? null;
            $price   = isset($change['price']) ? (float) $change['price'] : null;
            $side    = strtoupper($change['side'] ?? '');

            if (!$tokenId || $price === null) {
                continue;
            }

            $entry = $this->tokenMap[$tokenId] ?? null;
            if (!$entry) {
                continue;
            }

            $marketId = $entry['market_id'];
            $isYes    = $entry['is_yes'];
            $isBid    = ($side === 'BUY');

            if (!isset($this->marketState[$marketId])) {
                $assetId = $this->getAssetIdByMarket($marketId);
                if (!$assetId) {
                    continue;
                }
                $this->marketState[$marketId] = [
                    'asset_id' => $assetId,
                    'yes_bid'  => null,
                    'yes_ask'  => null,
                    'no_bid'   => null,
                    'no_ask'   => null,
                    'dirty'    => false,
                    'ts'       => $ts,
                ];
            }

            $s = &$this->marketState[$marketId];
            if      ($isYes && $isBid)   { $s['yes_bid'] = $price; }
            elseif  ($isYes && !$isBid)  { $s['yes_ask'] = $price; }
            elseif  (!$isYes && $isBid)  { $s['no_bid']  = $price; }
            else                         { $s['no_ask']  = $price; }
            $s['dirty'] = true;
            $s['ts']    = $ts;
            unset($s);
        }
    }

    private function flushMarketState(): void
    {
        $rows = [];
        foreach ($this->marketState as $marketId => &$state) {
            if (!$state['dirty']) {
                continue;
            }
            $rows[] = [
                'market_id' => $marketId,
                'asset_id'  => $state['asset_id'],
                'yes_bid'   => $state['yes_bid'],
                'yes_ask'   => $state['yes_ask'],
                'no_bid'    => $state['no_bid'],
                'no_ask'    => $state['no_ask'],
                'ts'        => $state['ts'],
            ];
            $state['dirty'] = false;
        }
        unset($state);

        if (empty($rows)) {
            return;
        }

        try {
            DB::table('clob_snapshots')->insert($rows);
            $this->stats['clob']['snapshots_written'] += count($rows);
        } catch (\Throwable $e) {
            echo '[weather-clob] Flush error: ' . $e->getMessage() . PHP_EOL;
        }
    }

    // ── Market resolved ──────────────────────────────────────────────────────

    private function onMarketResolved(array $msg): void
    {
        $conditionId = $msg['market'] ?? null;
        $outcome     = $msg['outcome'] ?? null;
        if (!$conditionId || !$outcome) {
            return;
        }

        $updated = DB::table('markets')
            ->where('category', 'weather')
            ->where('condition_id', $conditionId)
            ->whereNull('outcome')
            ->update([
                'outcome'     => strtoupper($outcome),
                'resolved_ts' => (int) (microtime(true) * 1000),
            ]);

        if ($updated) {
            echo "[weather-resolved] condition={$conditionId} outcome={$outcome}" . PHP_EOL;
        }

        foreach ($this->marketState as $marketId => $state) {
            $row = DB::table('markets')->where('id', $marketId)->where('condition_id', $conditionId)->first();
            if ($row) {
                unset($this->marketState[$marketId]);
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getAssetIdByMarket(string $marketId): ?int
    {
        if (!isset($this->marketAssetCache[$marketId])) {
            $id = DB::table('markets')->where('id', $marketId)->value('asset_id');
            if ($id !== null) {
                $this->marketAssetCache[$marketId] = (int) $id;
            }
        }
        return $this->marketAssetCache[$marketId] ?? null;
    }

    private function emptyStats(): array
    {
        return [
            'running'          => true,
            'started_at'       => time(),
            'current'          => [],
            'clob'             => ['connected' => false, 'subscribed' => 0, 'snapshots_written' => 0],
            'markets'          => ['total' => 0, 'active' => 0],
            'readings_written' => 0,
        ];
    }
}
