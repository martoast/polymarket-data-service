<?php

namespace App\Services\Ingest;

use App\Jobs\ComputeWindowFeatures;
use App\Models\Asset;
use App\Models\Window;
use Illuminate\Support\Facades\DB;

class SessionImportService
{
    private const BATCH = 500;

    /** @var array<string,int> symbol → asset_id */
    private array $assetIds = [];

    /** @var array<string,array{id:string,asset_id:int}> conditionId → {id, asset_id} */
    private array $windowMap = [];

    /** @var array<string,bool> slugs of windows resolved in this session */
    private array $newlyResolved = [];

    public function import(string $filepath, bool $dryRun = false): array
    {
        $this->assetIds   = Asset::pluck('id', 'symbol')->all();
        $this->windowMap  = [];
        $this->newlyResolved = [];

        $stats = [
            'total_lines' => 0,
            'windows'     => 0,
            'oracle'      => 0,
            'clob'        => 0,
            'candles'     => 0,
            'resolutions' => 0,
            'skipped_clob'=> 0,
        ];

        // Pre-load any windows already in DB so CLOB events match
        foreach (Window::whereNotNull('condition_id')->get(['id', 'asset_id', 'condition_id']) as $w) {
            $this->windowMap[$w->condition_id] = ['id' => $w->id, 'asset_id' => $w->asset_id];
        }

        $oracleBatch = [];
        $clobBatch   = [];
        $candleBatch = [];

        $fh = fopen($filepath, 'r');
        if (! $fh) {
            throw new \RuntimeException("Cannot open: {$filepath}");
        }

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') continue;

            $row = json_decode($line, true);
            if (! $row || ! isset($row['e'])) continue;

            $stats['total_lines']++;

            switch ($row['e']) {
                case 'W':
                    $result = $this->handleWindow($row, $dryRun);
                    if ($result) $stats['windows']++;
                    break;

                case 'O':
                    $built = $this->buildOracleTick($row);
                    if ($built) {
                        $oracleBatch[] = $built;
                        if (count($oracleBatch) >= self::BATCH) {
                            if (! $dryRun) $this->flushOracle($oracleBatch);
                            $stats['oracle'] += count($oracleBatch);
                            $oracleBatch = [];
                        }
                    }
                    break;

                case 'C':
                    $built = $this->buildClob($row);
                    if ($built) {
                        $clobBatch[] = $built;
                        if (count($clobBatch) >= self::BATCH) {
                            if (! $dryRun) $this->flushClob($clobBatch);
                            $stats['clob'] += count($clobBatch);
                            $clobBatch = [];
                        }
                    } else {
                        $stats['skipped_clob']++;
                    }
                    break;

                case 'K':
                    if (($row['tf'] ?? '') === '1m') {
                        $built = $this->buildCandle($row);
                        if ($built) {
                            $candleBatch[] = $built;
                            if (count($candleBatch) >= self::BATCH) {
                                if (! $dryRun) $this->flushCandles($candleBatch);
                                $stats['candles'] += count($candleBatch);
                                $candleBatch = [];
                            }
                        }
                    }
                    break;

                case 'R':
                    $result = $this->handleResolution($row, $dryRun);
                    if ($result) $stats['resolutions']++;
                    break;

                // META and S (signals) are skipped
            }
        }

        fclose($fh);

        // Flush remaining batches
        if (! $dryRun) {
            if ($oracleBatch)   $this->flushOracle($oracleBatch);
            if ($clobBatch)     $this->flushClob($clobBatch);
            if ($candleBatch)   $this->flushCandles($candleBatch);
        }

        $stats['oracle']  += count($oracleBatch);
        $stats['clob']    += count($clobBatch);
        $stats['candles'] += count($candleBatch);

        // Dispatch feature computation for newly resolved windows
        if (! $dryRun) {
            foreach (array_keys($this->newlyResolved) as $windowId) {
                ComputeWindowFeatures::dispatch($windowId);
            }
        }

        return $stats;
    }

    // -------------------------------------------------------------------------

    private function handleWindow(array $row, bool $dryRun): bool
    {
        $symbol   = strtoupper($row['a'] ?? '');
        $assetId  = $this->assetIds[$symbol] ?? null;
        if (! $assetId) return false;

        $condId  = $row['m'] ?? null;
        $ws      = (int) ($row['ws'] ?? 0);
        $we      = (int) ($row['we'] ?? 0);
        $bp      = (float) ($row['bp'] ?? 0);
        if (! $condId || ! $ws || ! $we) return false;

        $slug = Window::buildSlug($symbol, $ws, $we);

        $this->windowMap[$condId] = ['id' => $slug, 'asset_id' => $assetId];

        if ($dryRun) return true;

        Window::updateOrCreate(
            ['id' => $slug],
            [
                'asset_id'         => $assetId,
                'duration_sec'     => (int) (($we - $ws) / 1000),
                'break_price_usd'  => $bp,
                'break_price_bp'   => (int) round($bp * 100),
                'open_ts'          => $ws,
                'close_ts'         => $we,
                'condition_id'     => $condId,
                'gamma_slug'       => $row['s'] ?? null,
            ]
        );

        return true;
    }

    private function handleResolution(array $row, bool $dryRun): bool
    {
        $condId = $row['m'] ?? null;
        $winner = $row['w'] ?? null;
        if (! $condId || ! $winner) return false;

        if ($dryRun) return true;

        $updated = Window::where('condition_id', $condId)
            ->whereNull('outcome')
            ->update([
                'outcome'     => $winner,
                'resolved_ts' => (int) ($row['t'] ?? now()->timestamp * 1000),
            ]);

        if ($updated) {
            $window = $this->windowMap[$condId] ?? null;
            if ($window) {
                $this->newlyResolved[$window['id']] = true;
            }
        }

        return (bool) $updated;
    }

    private function buildOracleTick(array $row): ?array
    {
        $symbol  = strtoupper($row['a'] ?? '');
        $assetId = $this->assetIds[$symbol] ?? null;
        $price   = (float) ($row['p'] ?? 0);
        $ts      = (int) ($row['t'] ?? 0);

        if (! $assetId || ! $price || ! $ts) return null;

        return [
            'asset_id'  => $assetId,
            'price_usd' => $price,
            'price_bp'  => (int) round($price * 100),
            'ts'        => $ts,
        ];
    }

    private function buildClob(array $row): ?array
    {
        $condId = $row['m'] ?? null;
        $ts     = (int) ($row['t'] ?? 0);
        if (! $condId || ! $ts) return null;

        $window = $this->windowMap[$condId] ?? null;
        if (! $window) return null;

        return [
            'window_id' => $window['id'],
            'asset_id'  => $window['asset_id'],
            'yes_ask'   => isset($row['yA']) ? (float) $row['yA'] : null,
            'yes_bid'   => isset($row['yB']) ? (float) $row['yB'] : null,
            'no_ask'    => isset($row['nA']) ? (float) $row['nA'] : null,
            'no_bid'    => isset($row['nB']) ? (float) $row['nB'] : null,
            'ts'        => $ts,
        ];
    }

    private function buildCandle(array $row): ?array
    {
        $symbol  = strtoupper($row['a'] ?? '');
        $assetId = $this->assetIds[$symbol] ?? null;
        $ts      = (int) ($row['t'] ?? 0);
        if (! $assetId || ! $ts) return null;

        return [
            'asset_id'  => $assetId,
            'open_usd'  => (float) ($row['o'] ?? 0),
            'high_usd'  => (float) ($row['h'] ?? 0),
            'low_usd'   => (float) ($row['l'] ?? 0),
            'close_usd' => (float) ($row['c'] ?? 0),
            'volume'    => (float) ($row['v'] ?? 0),
            'ts'        => $ts,
        ];
    }

    // -------------------------------------------------------------------------

    private function flushOracle(array $rows): void
    {
        DB::table('oracle_ticks')->insert($rows);
    }

    private function flushClob(array $rows): void
    {
        DB::table('clob_snapshots')->insert($rows);
    }

    private function flushCandles(array $rows): void
    {
        DB::table('candles_1m')->upsert(
            $rows,
            ['asset_id', 'ts'],
            ['open_usd', 'high_usd', 'low_usd', 'close_usd', 'volume']
        );
    }
}
