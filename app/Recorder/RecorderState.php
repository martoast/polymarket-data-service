<?php

namespace App\Recorder;

use Illuminate\Support\Facades\Redis;

/**
 * Thin Redis wrapper for live recorder status.
 * Written by the recorder process every 5s; read by the admin dashboard.
 */
class RecorderState
{
    private const KEY         = 'recorder:status';
    private const WEATHER_KEY = 'recorder:weather:status';
    private const TTL         = 120; // seconds — expires if recorder dies

    public static function update(array $stats): void
    {
        Redis::setex(self::KEY, self::TTL, json_encode(array_merge($stats, [
            'last_updated' => time(),
        ])));
    }

    public static function updateWeather(array $stats): void
    {
        Redis::setex(self::WEATHER_KEY, self::TTL, json_encode(array_merge($stats, [
            'last_updated' => time(),
        ])));
    }

    public static function get(): array
    {
        $raw = Redis::get(self::KEY);
        if (!$raw) {
            return self::offline();
        }
        $data = json_decode($raw, true);
        return $data ?: self::offline();
    }

    public static function getWeather(): array
    {
        $raw = Redis::get(self::WEATHER_KEY);
        if (!$raw) {
            return self::offlineWeather();
        }
        $data = json_decode($raw, true);
        return $data ?: self::offlineWeather();
    }

    private static function offline(): array
    {
        return [
            'running'          => false,
            'started_at'       => null,
            'oracle'           => [],
            'clob'             => ['connected' => false, 'subscribed' => 0, 'snapshots_written' => 0],
            'markets'          => ['total' => 0, 'active' => 0],
            'candles_written'  => 0,
            'oracle_written'   => 0,
            'last_updated'     => null,
        ];
    }

    private static function offlineWeather(): array
    {
        return [
            'running'          => false,
            'started_at'       => null,
            'current'          => [],
            'clob'             => ['connected' => false, 'subscribed' => 0, 'snapshots_written' => 0],
            'markets'          => ['total' => 0, 'active' => 0],
            'readings_written' => 0,
            'last_updated'     => null,
        ];
    }
}
