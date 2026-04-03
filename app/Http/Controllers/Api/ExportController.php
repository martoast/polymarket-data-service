<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoMarketFeature;
use App\Models\WeatherMarketFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function csv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'category' => ['nullable', 'string', 'in:crypto,weather'],
        ]);

        $category = $request->input('category', 'crypto');

        [$model, $cursorCol, $filename] = match ($category) {
            'weather' => [new WeatherMarketFeature(), 'market_id', 'polymarket-weather-features-' . now()->format('Y-m-d') . '.csv'],
            default   => [new CryptoMarketFeature(),  'market_id', 'polymarket-crypto-features-'  . now()->format('Y-m-d') . '.csv'],
        };

        $columns = $model->getFillable();

        return response()->streamDownload(function () use ($model, $columns, $cursorCol) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $model::query()
                ->select($columns)
                ->lazyById(500, $cursorCol)
                ->each(function ($feature) use ($out, $columns) {
                    $row = array_map(fn ($col) => $feature->{$col} ?? '', $columns);
                    fputcsv($out, $row);
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function sqlite(): JsonResponse
    {
        return response()->json([
            'error' => 'SQLite export coming soon — use CSV export for now',
            'code'  => 'NOT_IMPLEMENTED',
        ], 501);
    }
}
