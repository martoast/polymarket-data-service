<?php

namespace App\Console\Commands;

use App\Recorder\AssetConfig;
use App\Recorder\CandleService;
use App\Recorder\ClobFeedService;
use App\Recorder\MarketDiscoveryService;
use App\Recorder\OracleFeedService;
use App\Recorder\RecorderState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use React\EventLoop\Loop;

class RecorderCommand extends Command
{
    protected $signature   = 'recorder:start';
    protected $description = 'Start the Polymarket crypto market recorder (long-running ReactPHP process)';

    /** token_id → ['market_id' => slug, 'is_yes' => bool] */
    private array $tokenMap = [];

    /**
     * Per-market CLOB state — one row written per dirty market per second.
     *
     * Structure: market_id => [
     *   'asset_id' => int,
     *   'yes_bid'  => float|null,
     *   'yes_ask'  => float|null,
     *   'no_bid'   => float|null,
     *   'no_ask'   => float|null,
     *   'dirty'    => bool,   // true = received update since last flush
     *   'ts'       => int,    // ms timestamp of most recent tick
     * ]
     */
    private array $marketState = [];

    /** market_id → asset_id DB cache */
    private array $marketAssetCache = [];

    /**
     * Dead-band filter for oracle writes — last written price+ts per asset.
     * asset => ['price_usd' => float, 'ts' => int (ms)]
     */
    private array $lastOracleWrite = [];

    private const ORACLE_MIN_CHANGE_PCT = 0.01; // 0.01% minimum move to write
    private const ORACLE_HEARTBEAT_SEC  = 30;   // always write at least every 30s

    private array $stats;

    public function handle(): int
    {
        $this->stats = $this->emptyStats();

        echo '[recorder] Starting — assets: ' . implode(', ', AssetConfig::enabledAssets()) . PHP_EOL;

        $loop      = Loop::get();
        $candles   = new CandleService();
        $discovery = new MarketDiscoveryService();

        // Pre-populate full token map from DB (for lookup on price events)
        $this->tokenMap = $discovery->loadTokenMap();
        echo '[recorder] Pre-loaded ' . count($this->tokenMap) . ' token mappings from DB' . PHP_EOL;

        // ── CLOB feed ────────────────────────────────────────────────────────
        $clob = new ClobFeedService(
            $loop,
            fn (array $msg) => $this->onClobPrice($msg),
            fn (array $msg) => $this->onMarketResolved($msg)
        );

        // Subscribe only to ACTIVE tokens at startup
        $activeTokens = array_keys($discovery->loadActiveTokenMap());
        if (!empty($activeTokens)) {
            $clob->subscribe($activeTokens);
            echo '[recorder] Queued ' . count($activeTokens) . ' active token(s) for CLOB subscription' . PHP_EOL;
        }

        // ── Oracle feed ─────────────────────────────────────────────────────
        $oracle = new OracleFeedService($loop, function (string $asset, float $price, int $ts) use ($candles) {
            $this->onOracleTick($asset, $price, $ts, $candles);
        });

        // ── Gamma discovery: run immediately + every 20s ─────────────────────
        $runDiscovery = function () use ($discovery, $clob) {
            $newMarkets = $discovery->discover();
            if (!empty($newMarkets)) {
                foreach ($newMarkets as $entry) {
                    $this->tokenMap[$entry['yes_token_id']] = ['market_id' => $entry['market_id'], 'is_yes' => true];
                    $this->tokenMap[$entry['no_token_id']]  = ['market_id' => $entry['market_id'], 'is_yes' => false];
                }
            }

            // Rebuild subscription from only currently active tokens — prevents the
            // list from growing to thousands of expired tokens and silently breaking
            // the WS feed.
            $activeTokenMap = $discovery->loadActiveTokenMap();
            $clob->replaceSubscription(array_keys($activeTokenMap));

            $nowMs = (int) (microtime(true) * 1000);
            $this->stats['markets']['total']  = DB::table('markets')->count();
            $this->stats['markets']['active'] = DB::table('markets')
                ->whereNull('outcome')
                ->where('close_ts', '>', $nowMs)
                ->count();
        };

        $runDiscovery();
        $loop->addPeriodicTimer(20, $runDiscovery);

        // ── Sampled CLOB flush every 1s ───────────────────────────────────────
        $loop->addPeriodicTimer(1, function () use ($clob) {
            $this->flushMarketState();
            $this->stats['clob']['connected']  = ($clob->status === 'connected');
            $this->stats['clob']['subscribed'] = $clob->subscribedCount();
        });

        // ── Status update every 5s ────────────────────────────────────────────
        $loop->addPeriodicTimer(5, fn () => RecorderState::update($this->stats));

        // ── Start connections ─────────────────────────────────────────────────
        $oracle->connect();
        $clob->connect();

        // ── Graceful shutdown ─────────────────────────────────────────────────
        if (function_exists('pcntl_signal')) {
            $shutdown = function () use ($loop) {
                echo '[recorder] Shutting down...' . PHP_EOL;
                $this->flushMarketState();
                RecorderState::update(array_merge($this->stats, ['running' => false]));
                $loop->stop();
            };
            pcntl_signal(SIGTERM, $shutdown);
            pcntl_signal(SIGINT, $shutdown);
            $loop->addPeriodicTimer(1, fn () => pcntl_signal_dispatch());
        }

        echo '[recorder] Event loop running' . PHP_EOL;
        $loop->run();

        return self::SUCCESS;
    }

    // ── Oracle tick handler ──────────────────────────────────────────────────

    private function onOracleTick(string $asset, float $price, int $ts, CandleService $candles): void
    {
        $assetId = $this->getAssetId($asset);
        if (!$assetId) {
            return;
        }

        // Dead-band filter: skip DB write if price hasn't moved enough AND
        // heartbeat hasn't expired. Candle service still gets every tick.
        $last        = $this->lastOracleWrite[$asset] ?? null;
        $shouldWrite = $last === null
            || (abs($price - $last['price_usd']) / $last['price_usd'] * 100) >= self::ORACLE_MIN_CHANGE_PCT
            || ($ts - $last['ts']) >= self::ORACLE_HEARTBEAT_SEC * 1000;

        if ($shouldWrite) {
            DB::table('oracle_ticks')->insert([
                'asset_id'  => $assetId,
                'price_usd' => $price,
                'price_bp'  => (int) ($price * 100),
                'ts'        => $ts,
            ]);
            $this->lastOracleWrite[$asset] = ['price_usd' => $price, 'ts' => $ts];
            $this->stats['oracle_written']++;
        }

        $this->stats['oracle'][$asset] = ['price' => $price, 'last_tick' => $ts];

        $completed = $candles->tick($asset, $price, $ts);
        if ($completed) {
            DB::table('candles_1m')->insertOrIgnore([
                'asset_id'  => $assetId,
                'open_usd'  => $completed['open'],
                'high_usd'  => $completed['high'],
                'low_usd'   => $completed['low'],
                'close_usd' => $completed['close'],
                'volume'    => $completed['volume'],
                'ts'        => $completed['ts'],
            ]);
            $this->stats['candles_written']++;
            echo "[candles] {$asset} 1m closed @ {$completed['close']}" . PHP_EOL;
        }
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

            // Initialise state for new markets (one DB lookup, then cached)
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

    /**
     * Write one row per dirty market — called every second by the event loop.
     */
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
            echo '[clob] Flush error: ' . $e->getMessage() . PHP_EOL;
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
            ->where('condition_id', $conditionId)
            ->whereNull('outcome')
            ->update([
                'outcome'     => strtoupper($outcome),
                'resolved_ts' => (int) (microtime(true) * 1000),
            ]);

        if ($updated) {
            echo "[resolved] condition={$conditionId} outcome={$outcome}" . PHP_EOL;
        }

        // Free memory for resolved markets — they won't receive more ticks
        foreach ($this->marketState as $marketId => $state) {
            $row = DB::table('markets')->where('id', $marketId)->where('condition_id', $conditionId)->first();
            if ($row) {
                unset($this->marketState[$marketId]);
            }
        }
    }

    // ── Lookup helpers ───────────────────────────────────────────────────────

    private function getAssetId(string $symbol): ?int
    {
        static $cache = [];
        if (!isset($cache[$symbol])) {
            $id = DB::table('assets')->where('symbol', $symbol)->value('id');
            if ($id !== null) {
                $cache[$symbol] = (int) $id;
            }
        }
        return $cache[$symbol] ?? null;
    }

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
            'running'         => true,
            'started_at'      => time(),
            'oracle'          => [],
            'clob'            => ['connected' => false, 'subscribed' => 0, 'snapshots_written' => 0],
            'markets'         => ['total' => 0, 'active' => 0],
            'candles_written' => 0,
            'oracle_written'  => 0,
        ];
    }
}
