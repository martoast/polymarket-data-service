<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WindowFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function csv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $columns = (new WindowFeature())->getFillable();

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            WindowFeature::query()
                ->select($columns)
                ->lazyById(500, 'window_id')
                ->each(function ($feature) use ($out, $columns) {
                    $row = array_map(fn ($col) => $feature->{$col} ?? '', $columns);
                    fputcsv($out, $row);
                });

            fclose($out);
        }, 'polymarket-features-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function sqlite(): JsonResponse
    {
        return response()->json([
            'error'   => 'SQLite export coming soon — use CSV export for now',
            'code'    => 'NOT_IMPLEMENTED',
        ], 501);
    }
}
