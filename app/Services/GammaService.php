<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GammaService
{
    /**
     * Fetch resolution data for a market slug from the Gamma API.
     *
     * Returns ['outcome' => 'YES'|'NO', 'condition_id' => 'hex...'] or null if
     * the market is not yet closed or any error occurs.
     */
    public function fetchResolution(string $slug): ?array
    {
        try {
            $base = rtrim(config('services.recorder.gamma_base'), '/');

            $response = Http::withUserAgent('Mozilla/5.0')
                ->timeout(10)
                ->get("{$base}/events", ['slug' => $slug]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data) || !isset($data[0]['markets'][0])) {
                return null;
            }

            $market = $data[0]['markets'][0];

            // Market must be closed for resolution to be authoritative
            if (empty($market['closed'])) {
                return null;
            }

            $outcomes = json_decode($market['outcomes'] ?? '[]', true);
            $prices   = array_map('floatval', json_decode($market['outcomePrices'] ?? '[]', true));

            if (empty($outcomes) || empty($prices) || count($outcomes) !== count($prices)) {
                return null;
            }

            $maxPrice   = max($prices);
            $winnerIdx  = array_search($maxPrice, $prices);

            if ($winnerIdx === false) {
                return null;
            }

            $winnerLabel = $outcomes[$winnerIdx];
            $outcome     = ($winnerLabel === 'Up') ? 'YES' : 'NO';

            return [
                'outcome'      => $outcome,
                'condition_id' => $market['conditionId'] ?? null,
            ];

        } catch (\Throwable $e) {
            Log::warning('GammaService::fetchResolution failed', [
                'slug'      => $slug,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
