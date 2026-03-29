<?php

namespace App\Recorder;

use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * Chainlink RTDS WebSocket feed.
 * Single shared connection for all assets — avoids 429 rate limits.
 * Calls $onTick(string $asset, float $price, int $timestampMs) on each price event.
 */
class OracleFeedService
{
    private const DEFAULT_WS_URL = 'wss://ws-live-data.polymarket.com';
    private const STALE_THRESHOLD_MS = 30_000;
    private const RECONNECT_BASE_MS  = 1_000;
    private const RECONNECT_MAX_MS   = 30_000;

    private LoopInterface $loop;
    private string $wsUrl;
    /** @var callable */
    private $onTick;

    private ?WebSocket $conn = null;
    private bool $connected  = false;
    private int $lastTickMs  = 0;
    private int $reconnectDelay = self::RECONNECT_BASE_MS;

    private ?TimerInterface $staleTimer    = null;
    private ?TimerInterface $reconnectTimer = null;

    // Stats
    public int $ticksReceived = 0;
    public string $status = 'disconnected';

    public function __construct(LoopInterface $loop, callable $onTick)
    {
        $this->loop   = $loop;
        $this->wsUrl  = env('RTDS_WS_URL', self::DEFAULT_WS_URL);
        $this->onTick = $onTick;
    }

    public function connect(): void
    {
        $this->status = 'connecting';
        echo '[oracle] Connecting to ' . $this->wsUrl . PHP_EOL;

        \Ratchet\Client\connect($this->wsUrl, [], [], $this->loop)
            ->then(
                fn (WebSocket $ws) => $this->onOpen($ws),
                function (\Throwable $e) {
                    echo '[oracle] Connection failed: ' . $e->getMessage() . PHP_EOL;
                    $this->status = 'error';
                    $this->scheduleReconnect();
                }
            );
    }

    private function onOpen(WebSocket $ws): void
    {
        $this->conn            = $ws;
        $this->connected       = true;
        $this->reconnectDelay  = self::RECONNECT_BASE_MS;
        $this->lastTickMs      = $this->nowMs();
        $this->status          = 'connected';
        echo '[oracle] Connected' . PHP_EOL;

        // Subscribe to all crypto_prices_chainlink symbols in one message
        $ws->send(json_encode([
            'action'        => 'subscribe',
            'subscriptions' => [[
                'topic'   => 'crypto_prices_chainlink',
                'type'    => '*',
                'filters' => '',
            ]],
        ]));

        $ws->on('message', fn ($msg) => $this->onMessage((string) $msg));
        $ws->on('close', fn ($code, $reason) => $this->onClose($code, $reason));
        $ws->on('error', fn ($e) => $this->onError($e));

        $this->startStaleTimer();
    }

    private function onMessage(string $raw): void
    {
        // Update staleness timestamp on every frame (ping responses, confirmations, etc.)
        $this->lastTickMs = $this->nowMs();

        // Only price messages contain 'payload' — skip everything else
        if (!str_contains($raw, 'payload')) {
            return;
        }

        $msg = json_decode($raw, true);
        if (!is_array($msg)) {
            return;
        }

        $topic = $msg['topic'] ?? '';
        $type  = $msg['type']  ?? '';

        if ($topic !== 'crypto_prices_chainlink') {
            return;
        }
        if ($type !== 'update' && $type !== 'snapshot') {
            return;
        }

        $payload = $msg['payload'] ?? [];
        $symbol  = $payload['symbol'] ?? '';
        $price   = isset($payload['value']) ? (float) $payload['value'] : null;
        $ts      = isset($payload['timestamp']) ? (int) $payload['timestamp'] : $this->nowMs();

        if (!$symbol || $price === null || $price <= 0) {
            return;
        }

        $asset = AssetConfig::assetFromChainlinkSymbol($symbol);
        if (!$asset) {
            return;
        }

        $this->ticksReceived++;

        ($this->onTick)($asset, $price, $ts);
    }

    private function onClose(int $code, string $reason): void
    {
        echo "[oracle] Disconnected (code={$code}): {$reason}" . PHP_EOL;
        $this->connected = false;
        $this->conn      = null;
        $this->status    = 'disconnected';
        $this->cancelStaleTimer();
        $this->scheduleReconnect();
    }

    private function onError(\Throwable $e): void
    {
        echo '[oracle] Error: ' . $e->getMessage() . PHP_EOL;
        $this->status = 'error';
    }

    private function startStaleTimer(): void
    {
        $this->cancelStaleTimer();
        $this->staleTimer = $this->loop->addPeriodicTimer(10, function () {
            $age = $this->nowMs() - $this->lastTickMs;
            if ($age > self::STALE_THRESHOLD_MS) {
                echo '[oracle] Feed stale (' . round($age / 1000) . 's) — reconnecting' . PHP_EOL;
                $this->conn?->close();
            }
        });
    }

    private function cancelStaleTimer(): void
    {
        if ($this->staleTimer) {
            $this->loop->cancelTimer($this->staleTimer);
            $this->staleTimer = null;
        }
    }

    private function scheduleReconnect(): void
    {
        if ($this->reconnectTimer) {
            return;
        }
        $delay = $this->reconnectDelay / 1000;
        $this->reconnectDelay = min(self::RECONNECT_MAX_MS, $this->reconnectDelay * 2);
        echo "[oracle] Reconnecting in {$delay}s..." . PHP_EOL;

        $this->reconnectTimer = $this->loop->addTimer($delay, function () {
            $this->reconnectTimer = null;
            $this->connect();
        });
    }

    private function nowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
