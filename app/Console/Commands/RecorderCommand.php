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
    protected $description = 'Start the Polymarket market recorder (long-running ReactPHP process)';

    /** token_id → ['window_id' => slug, 'is_yes' => bool] */
    private array $tokenMap = [];

    /** window_id → asset_id cache */
    private array $windowAssetCache = [];

    /** CLOB snapshot buffer for batch inserts */
    private array $clobBuffer = [];

    /** Shared mutable stats */
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

        // Subscribe only to ACTIVE tokens at startup (avoids sending 700+ stale tokens in one WS frame)
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
                $newTokenIds = [];
                foreach ($newMarkets as $entry) {
                    $this->tokenMap[$entry['yes_token_id']] = ['window_id' => $entry['window_id'], 'is_yes' => true];
                    $this->tokenMap[$entry['no_token_id']]  = ['window_id' => $entry['window_id'], 'is_yes' => false];
                    $newTokenIds[] = $entry['yes_token_id'];
                    $newTokenIds[] = $entry['no_token_id'];
                }
                $clob->subscribe($newTokenIds);
            }
            $nowMs = (int) (microtime(true) * 1000);
            $this->stats['markets']['total']  = DB::table('windows')->count();
            $this->stats['markets']['active'] = DB::table('windows')
                ->whereNull('outcome')
                ->where('close_ts', '>', $nowMs)
                ->count();
        };

        $runDiscovery();
        $loop->addPeriodicTimer(20, $runDiscovery);

        // ── CLOB buffer flush every 1s ────────────────────────────────────────
        $loop->addPeriodicTimer(1, function () use ($clob) {
            $this->flushClobBuffer();
            $this->stats['clob']['connected']         = ($clob->status === 'connected');
            $this->stats['clob']['subscribed']        = $clob->subscribedCount();
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
                $this->flushClobBuffer();
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

        DB::table('oracle_ticks')->insert([
            'asset_id'  => $assetId,
            'price_usd' => $price,
            'price_bp'  => (int) ($price * 100),
            'ts'        => $ts,
        ]);

        $this->stats['oracle_written']++;
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
        $tokenId = $msg['asset_id'] ?? null;
        $price   = isset($msg['price']) ? (float) $msg['price'] : null;

        if (!$tokenId || $price === null) {
            return;
        }

        $entry = $this->tokenMap[$tokenId] ?? null;
        if (!$entry) {
            return;
        }

        $windowId = $entry['window_id'];
        $isYes    = $entry['is_yes'];
        $assetId  = $this->getAssetIdByWindow($windowId);
        if (!$assetId) {
            return;
        }

        $this->clobBuffer[] = [
            'window_id' => $windowId,
            'asset_id'  => $assetId,
            'yes_ask'   => $isYes ? $price : null,
            'yes_bid'   => null,
            'no_ask'    => !$isYes ? $price : null,
            'no_bid'    => null,
            'ts'        => (int) (microtime(true) * 1000),
        ];

        if (count($this->clobBuffer) >= 100) {
            $this->flushClobBuffer();
        }
    }

    private function flushClobBuffer(): void
    {
        if (empty($this->clobBuffer)) {
            return;
        }
        $rows = $this->clobBuffer;
        $this->clobBuffer = [];
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
        $updated = DB::table('windows')
            ->where('condition_id', $conditionId)
            ->whereNull('outcome')
            ->update([
                'outcome'     => strtoupper($outcome),
                'resolved_ts' => (int) (microtime(true) * 1000),
            ]);
        if ($updated) {
            echo "[resolved] condition={$conditionId} outcome={$outcome}" . PHP_EOL;
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

    private function getAssetIdByWindow(string $windowId): ?int
    {
        if (!isset($this->windowAssetCache[$windowId])) {
            $id = DB::table('windows')->where('id', $windowId)->value('asset_id');
            if ($id !== null) {
                $this->windowAssetCache[$windowId] = (int) $id;
            }
        }
        return $this->windowAssetCache[$windowId] ?? null;
    }

    private function emptyStats(): array
    {
        return [
            'running'          => true,
            'started_at'       => time(),
            'oracle'           => [],
            'clob'             => ['connected' => false, 'subscribed' => 0, 'snapshots_written' => 0],
            'markets'          => ['total' => 0, 'active' => 0],
            'candles_written'  => 0,
            'oracle_written'   => 0,
        ];
    }
}
