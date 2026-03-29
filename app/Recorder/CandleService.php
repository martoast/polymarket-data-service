<?php

namespace App\Recorder;

/**
 * Builds 1-minute OHLCV candles from a stream of price ticks.
 * Holds in-memory state per asset. Returns a completed candle array
 * when the minute bucket rolls over.
 */
class CandleService
{
    /** @var array<string, array{open:float,high:float,low:float,close:float,volume:float,ts:int}> */
    private array $state = [];

    /**
     * Process one price tick.
     *
     * @return array|null Completed candle when the bucket closes, null otherwise.
     *                    Shape: ['asset'=>string, 'open'=>float, 'high'=>float,
     *                            'low'=>float, 'close'=>float, 'volume'=>float, 'ts'=>int]
     */
    public function tick(string $asset, float $price, int $timestampMs): ?array
    {
        $bucketMs = (int) (floor($timestampMs / 60_000) * 60_000);

        if (!isset($this->state[$asset])) {
            $this->state[$asset] = $this->newCandle($price, $bucketMs);
            return null;
        }

        $current = $this->state[$asset];

        // Same bucket — update
        if ($current['ts'] === $bucketMs) {
            $this->state[$asset]['high']   = max($current['high'], $price);
            $this->state[$asset]['low']    = min($current['low'], $price);
            $this->state[$asset]['close']  = $price;
            $this->state[$asset]['volume'] += abs($price - $current['close']);
            return null;
        }

        // Bucket rolled — close old candle, open new one
        $completed = array_merge(['asset' => $asset], $current);
        $this->state[$asset] = $this->newCandle($price, $bucketMs);
        return $completed;
    }

    private function newCandle(float $price, int $bucketMs): array
    {
        return [
            'open'   => $price,
            'high'   => $price,
            'low'    => $price,
            'close'  => $price,
            'volume' => 0.0,
            'ts'     => $bucketMs,
        ];
    }
}
