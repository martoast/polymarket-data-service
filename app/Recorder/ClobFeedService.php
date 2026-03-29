<?php

namespace App\Recorder;

use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * Polymarket CLOB WebSocket feed.
 * Subscribes to market token IDs for real-time bid/ask price updates.
 * Calls $onPriceUpdate and $onMarketResolved callbacks on relevant events.
 */
class ClobFeedService
{
    private const DEFAULT_WS_URL    = 'wss://ws-subscriptions-clob.polymarket.com/ws/market';
    private const PING_INTERVAL_MS  = 10_000;
    private const PONG_TIMEOUT_MS   = 30_000;
    private const RECONNECT_BASE_MS = 1_000;
    private const RECONNECT_MAX_MS  = 30_000;

    private LoopInterface $loop;
    private string $wsUrl;
    /** @var callable */
    private $onPriceUpdate;
    /** @var callable */
    private $onMarketResolved;

    private ?WebSocket $conn = null;
    private bool $connected  = false;
    private int $lastPongMs  = 0;
    private int $reconnectDelay = self::RECONNECT_BASE_MS;

    /** @var string[] Token IDs currently subscribed */
    private array $subscribedTokens = [];

    /** @var string[] Token IDs waiting to be subscribed after connect */
    private array $pendingTokens = [];

    private ?TimerInterface $pingTimer      = null;
    private ?TimerInterface $reconnectTimer = null;

    // Stats
    public int $updatesReceived  = 0;
    public int $snapshotsWritten = 0;
    public string $status = 'disconnected';

    public function __construct(LoopInterface $loop, callable $onPriceUpdate, callable $onMarketResolved)
    {
        $this->loop             = $loop;
        $this->wsUrl            = env('CLOB_WS_URL', self::DEFAULT_WS_URL);
        $this->onPriceUpdate    = $onPriceUpdate;
        $this->onMarketResolved = $onMarketResolved;
    }

    public function connect(): void
    {
        $this->status = 'connecting';
        echo '[clob] Connecting to ' . $this->wsUrl . PHP_EOL;

        \Ratchet\Client\connect($this->wsUrl, [], [], $this->loop)
            ->then(
                fn (WebSocket $ws) => $this->onOpen($ws),
                function (\Throwable $e) {
                    echo '[clob] Connection failed: ' . $e->getMessage() . PHP_EOL;
                    $this->status = 'error';
                    $this->scheduleReconnect();
                }
            );
    }

    /** Subscribe to additional token IDs mid-connection (or queue for next connect). */
    public function subscribe(array $tokenIds): void
    {
        if (empty($tokenIds)) {
            return;
        }

        $new = array_diff($tokenIds, $this->subscribedTokens);
        if (empty($new)) {
            return;
        }

        if ($this->connected && $this->conn) {
            $this->conn->send(json_encode([
                'assets_ids' => array_values($new),
                'type'       => 'market',
            ]));
            array_push($this->subscribedTokens, ...$new);
            echo '[clob] Subscribed to ' . count($new) . ' new token(s) (total: ' . count($this->subscribedTokens) . ')' . PHP_EOL;
        } else {
            // Queue for when connection is established
            array_push($this->pendingTokens, ...$new);
        }
    }

    private function onOpen(WebSocket $ws): void
    {
        $this->conn           = $ws;
        $this->connected      = true;
        $this->reconnectDelay = self::RECONNECT_BASE_MS;
        $this->lastPongMs     = $this->nowMs();
        $this->status         = 'connected';
        echo '[clob] Connected' . PHP_EOL;

        // Initial subscription — send all pending tokens in ONE message (endpoint rejects multiple calls)
        $allTokens = array_values(array_unique(array_merge($this->subscribedTokens, $this->pendingTokens)));
        if (!empty($allTokens)) {
            $ws->send(json_encode([
                'assets_ids' => $allTokens,
                'type'       => 'market',
            ]));
            $this->subscribedTokens = $allTokens;
            $this->pendingTokens    = [];
            echo '[clob] Subscribed to ' . count($allTokens) . ' token(s)' . PHP_EOL;
        }

        $ws->on('message', fn ($msg) => $this->onMessage((string) $msg));
        $ws->on('close', fn ($code, $reason) => $this->onClose($code, $reason));
        $ws->on('error', fn ($e) => $this->onError($e));

        $this->startPingTimer();
    }

    private function onMessage(string $raw): void
    {
        // PONG keepalive
        if ($raw === 'PONG') {
            $this->lastPongMs = $this->nowMs();
            return;
        }

        $msg = json_decode($raw, true);
        if (!is_array($msg)) {
            return;
        }

        $eventType = $msg['event_type'] ?? '';

        if ($eventType === 'price_change') {
            $this->updatesReceived++;
            ($this->onPriceUpdate)($msg);
            return;
        }

        if ($eventType === 'market_result') {
            ($this->onMarketResolved)($msg);
            return;
        }
    }

    private function onClose(int $code, string $reason): void
    {
        echo "[clob] Disconnected (code={$code}): {$reason}" . PHP_EOL;
        $this->connected = false;
        $this->conn      = null;
        $this->status    = 'disconnected';
        $this->cancelPingTimer();
        $this->scheduleReconnect();
    }

    private function onError(\Throwable $e): void
    {
        echo '[clob] Error: ' . $e->getMessage() . PHP_EOL;
        $this->status = 'error';
    }

    private function startPingTimer(): void
    {
        $this->cancelPingTimer();
        $this->pingTimer = $this->loop->addPeriodicTimer(self::PING_INTERVAL_MS / 1000, function () {
            if ($this->conn && $this->connected) {
                $this->conn->send('PING');

                // Check pong timeout
                if (($this->nowMs() - $this->lastPongMs) > self::PONG_TIMEOUT_MS) {
                    echo '[clob] PONG timeout — reconnecting' . PHP_EOL;
                    $this->conn->close();
                }
            }
        });
    }

    private function cancelPingTimer(): void
    {
        if ($this->pingTimer) {
            $this->loop->cancelTimer($this->pingTimer);
            $this->pingTimer = null;
        }
    }

    private function scheduleReconnect(): void
    {
        if ($this->reconnectTimer) {
            return;
        }
        $delay = $this->reconnectDelay / 1000;
        $this->reconnectDelay = min(self::RECONNECT_MAX_MS, $this->reconnectDelay * 2);
        echo "[clob] Reconnecting in {$delay}s..." . PHP_EOL;

        $this->reconnectTimer = $this->loop->addTimer($delay, function () {
            $this->reconnectTimer = null;
            $this->connect();
        });
    }

    public function subscribedCount(): int
    {
        return count($this->subscribedTokens);
    }

    private function nowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
