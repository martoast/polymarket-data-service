<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OracleTick;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $db           = $this->checkDb();
        $lastOracleTs = $db ? OracleTick::max('ts') : null;
        $failedJobs   = $db ? DB::table('failed_jobs')->count() : null;

        $oracleStale = $lastOracleTs !== null
            && (now()->timestamp * 1000 - $lastOracleTs) > 600_000;

        $status = ($db && ! $oracleStale) ? 'ok' : 'degraded';

        return response()->json([
            'status'         => $status,
            'db'             => $db ? 'ok' : 'error',
            'failed_jobs'    => $failedJobs,
            'last_oracle_ts' => $lastOracleTs,
            'oracle_stale'   => $oracleStale,
        ], $status === 'ok' ? 200 : 503);
    }

    private function checkDb(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
