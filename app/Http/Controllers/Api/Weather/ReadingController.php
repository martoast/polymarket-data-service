<?php

namespace App\Http\Controllers\Api\Weather;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weather\ReadingIndexRequest;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReadingController extends Controller
{
    /**
     * GET /v1/weather/stations
     * List all weather station assets.
     */
    public function stations(): JsonResponse
    {
        $stations = Asset::with('category')
            ->whereHas('category', fn ($q) => $q->where('slug', 'weather'))
            ->where('is_active', true)
            ->get()
            ->map(fn ($a) => [
                'symbol'        => $a->symbol,
                'name'          => $a->name,
                'unit'          => $a->unit,
                'source_config' => collect($a->source_config)->only(['icao', 'city', 'country', 'timezone', 'latitude', 'longitude']),
            ]);

        return response()->json(['data' => $stations]);
    }

    /**
     * GET /v1/weather/readings
     * Temperature readings for a station, filterable by date or ts range.
     */
    public function index(ReadingIndexRequest $request): JsonResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $query = DB::table('weather_readings')
            ->join('assets', 'weather_readings.asset_id', '=', 'assets.id')
            ->select('assets.symbol', 'weather_readings.temp_c', 'weather_readings.temp_f',
                     'weather_readings.running_daily_max_c', 'weather_readings.source',
                     'weather_readings.station_local_date', 'weather_readings.ts');

        if ($request->filled('asset')) {
            $query->where('assets.symbol', strtoupper($request->asset));
        }

        if ($request->filled('date')) {
            $query->where('weather_readings.station_local_date', $request->date);
        }

        if ($request->filled('from')) {
            $query->where('weather_readings.ts', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('weather_readings.ts', '<=', $request->to);
        }

        if ($limitDays) {
            $query->where('weather_readings.ts', '>=', now()->subDays($limitDays)->timestamp * 1000);
        }

        $query->orderBy('weather_readings.ts', 'asc');

        $paginated = $query->cursorPaginate($request->integer('per_page', 500));

        return response()->json([
            'data'        => $paginated->items(),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }

    /**
     * GET /v1/weather/daily-max
     * Running daily maximum temperature for a station on a given date (or today).
     */
    public function dailyMax(ReadingIndexRequest $request): JsonResponse
    {
        $request->validate([
            'asset' => ['required', 'string', 'exists:assets,symbol'],
        ]);

        $asset = Asset::where('symbol', strtoupper($request->asset))->firstOrFail();
        $sourceConfig = $asset->source_config ?? [];
        $tz           = $sourceConfig['timezone'] ?? 'UTC';
        $date         = $request->date ?? (new \DateTime('now', new \DateTimeZone($tz)))->format('Y-m-d');

        $row = DB::table('weather_readings')
            ->where('asset_id', $asset->id)
            ->where('station_local_date', $date)
            ->orderByDesc('ts')
            ->first(['temp_c', 'temp_f', 'running_daily_max_c', 'station_local_date', 'ts']);

        if (!$row) {
            return response()->json([
                'data' => [
                    'asset'               => $asset->symbol,
                    'station_local_date'  => $date,
                    'current_temp_c'      => null,
                    'running_daily_max_c' => null,
                    'reading_count'       => 0,
                    'ts'                  => null,
                ],
            ]);
        }

        // Count readings today and determine % of day elapsed
        $readingCount = DB::table('weather_readings')
            ->where('asset_id', $asset->id)
            ->where('station_local_date', $date)
            ->count();

        $now          = new \DateTime('now', new \DateTimeZone($tz));
        $midnight     = new \DateTime($date . ' 00:00:00', new \DateTimeZone($tz));
        $secondsInDay = 86400;
        $elapsed      = min(1.0, ($now->getTimestamp() - $midnight->getTimestamp()) / $secondsInDay);

        $maxC = (float) $row->running_daily_max_c;
        $maxF = round($maxC * 9 / 5 + 32, 1);

        return response()->json([
            'data' => [
                'asset'               => $asset->symbol,
                'unit'                => $asset->unit,
                'station_local_date'  => $row->station_local_date,
                'current_temp_c'      => (float) $row->temp_c,
                'current_temp_f'      => (float) $row->temp_f,
                'running_daily_max_c' => $maxC,
                'running_daily_max_f' => $maxF,
                'day_elapsed_pct'     => round($elapsed * 100, 1),
                'reading_count'       => $readingCount,
                'ts'                  => (int) $row->ts,
            ],
        ]);
    }
}
