<?php

namespace App\Recorder\Weather;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Polls the Open-Meteo API for current temperature readings.
 * Free, no API key required, provides airport-station-level data.
 */
class OpenMeteoService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Fetch current temperature for a station defined in its source_config.
     *
     * @param array $sourceConfig  Asset's source_config (has latitude, longitude, timezone)
     * @return array{temp_c: float, temp_f: float, ts: int}|null
     */
    public function currentTemp(array $sourceConfig): ?array
    {
        $lat = $sourceConfig['latitude']  ?? null;
        $lng = $sourceConfig['longitude'] ?? null;
        $tz  = $sourceConfig['timezone']  ?? 'UTC';

        if ($lat === null || $lng === null) {
            Log::warning('[weather] Missing lat/lng in source_config');
            return null;
        }

        try {
            $response = Http::timeout(10)->get(self::BASE_URL, [
                'latitude'       => $lat,
                'longitude'      => $lng,
                'current'        => 'temperature_2m',
                'timezone'       => $tz,
                'forecast_days'  => 1,
            ]);

            if (!$response->successful()) {
                Log::warning('[weather] Open-Meteo non-200: ' . $response->status());
                return null;
            }

            $data    = $response->json();
            $tempC   = $data['current']['temperature_2m'] ?? null;

            if ($tempC === null) {
                return null;
            }

            $tempC = (float) $tempC;
            $tempF = round($tempC * 9 / 5 + 32, 2);
            $ts    = (int) (microtime(true) * 1000);

            return ['temp_c' => $tempC, 'temp_f' => $tempF, 'ts' => $ts];
        } catch (\Throwable $e) {
            Log::warning('[weather] Open-Meteo error: ' . $e->getMessage());
            return null;
        }
    }
}
