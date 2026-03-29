<?php

namespace App\Services\Ingest;

use App\Jobs\ResolveWindow;
use App\Models\Asset;
use App\Models\Window;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class SqliteSyncService
{
    private PDO $readPdo;
    private PDO $writePdo;

    /** @var array<string, int> Asset symbol → asset_id cache */
    private array $assetIdCache = [];

    /** @var array<string, string|null> condition_id → window_id (slug) cache */
    private array $windowIdCache = [];

    public function sync(?string $sqliteFile = null): array
    {
        $stats = [
            'markets'         => 0,
            'oracle_ticks'    => 0,
            'clob_snapshots'  => 0,
            'candles_1m'      => 0,
        ];

        try {
            $file = $sqliteFile ?? config('services.recorder.sqlite_file');

            $this->readPdo = new PDO(
                'sqlite:' . $file,
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->writePdo = new PDO(
                'sqlite:' . $file,
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stats['markets']        = $this->syncMarkets();
            $stats['oracle_ticks']   = $this->syncOracleTicks();
            $stats['clob_snapshots'] = $this->syncClobSnapshots();
            $stats['candles_1m']     = $this->syncCandles();

        } catch (\Throwable $e) {
            Log::error('SqliteSyncService error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return $stats;
    }

    // -------------------------------------------------------------------------

    private function getCursor(string $tableName): int
    {
        $stmt = $this->readPdo->prepare(
            'SELECT last_id FROM sync_cursors WHERE table_name = ?'
        );
        $stmt->execute([$tableName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['last_id'] : 0;
    }

    private function updateCursor(string $tableName, int $lastId): void
    {
        $this->writePdo->exec(
            "UPDATE sync_cursors SET last_id = {$lastId}, last_synced_at = " .
            (time() * 1000) .
            " WHERE table_name = '{$tableName}'"
        );
    }

    private function getAssetId(string $symbol): ?int
    {
        if (isset($this->assetIdCache[$symbol])) {
            return $this->assetIdCache[$symbol];
        }

        $asset = Asset::where('symbol', $symbol)->first();
        if (!$asset) {
            return null;
        }

        $this->assetIdCache[$symbol] = $asset->id;
        return $asset->id;
    }

    private function getWindowIdByConditionId(string $conditionId): ?string
    {
        if (array_key_exists($conditionId, $this->windowIdCache)) {
            return $this->windowIdCache[$conditionId];
        }

        $window = DB::table('windows')
            ->where('condition_id', $conditionId)
            ->value('id');

        $this->windowIdCache[$conditionId] = $window ?: null;
        return $this->windowIdCache[$conditionId];
    }

    // -------------------------------------------------------------------------

    private function syncMarkets(): int
    {
        $lastId  = $this->getCursor('markets');
        $stmt    = $this->readPdo->prepare(
            'SELECT * FROM markets WHERE id > ? ORDER BY id LIMIT 1000'
        );
        $stmt->execute([$lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $count     = 0;
        $newLastId = $lastId;

        foreach ($rows as $row) {
            $rowId = (int) $row['id'];
            $slug  = $row['slug'] ?? '';

            // Asset is not stored directly — parse from slug prefix (e.g. "btc-updown-5m-…" → "BTC")
            $assetSymbol = strtoupper(explode('-', $slug)[0] ?? '');
            $assetId     = $this->getAssetId($assetSymbol);

            if ($assetId === null) {
                Log::warning("SqliteSyncService: cannot determine asset from slug '{$slug}'");
                $newLastId = max($newLastId, $rowId);
                continue;
            }
            $breakUsd = (float) $row['break_price'];
            $breakBp  = (int) ($breakUsd * 100);

            $durationSec = isset($row['duration_sec']) && $row['duration_sec']
                ? (int) $row['duration_sec']
                : (int) (((int) $row['close_ts'] - (int) $row['open_ts']) / 1000);

            DB::table('windows')->insertOrIgnore([
                'id'              => $slug,
                'asset_id'        => $assetId,
                'duration_sec'    => $durationSec,
                'break_price_usd' => $breakUsd,
                'break_price_bp'  => $breakBp,
                'open_ts'         => (int) $row['open_ts'],
                'close_ts'        => (int) $row['close_ts'],
                'resolved_ts'     => isset($row['resolved_ts']) && $row['resolved_ts']
                    ? (int) $row['resolved_ts']
                    : null,
                'condition_id'    => $row['condition_id'],
                'gamma_slug'      => $slug,
                // outcome intentionally NOT set — Gamma API's job
            ]);

            // Warm the condition_id → slug cache
            $this->windowIdCache[$row['condition_id']] = $slug;

            // Dispatch resolution job for closed windows that have no outcome yet
            if ((int) $row['close_ts'] < (time() * 1000)) {
                $window = Window::find($slug);
                if ($window && $window->outcome === null) {
                    ResolveWindow::dispatch($slug);
                }
            }

            $count++;
            $newLastId = max($newLastId, $rowId);
        }

        if ($newLastId > $lastId) {
            $this->updateCursor('markets', $newLastId);
        }

        return $count;
    }

    private function syncOracleTicks(): int
    {
        $lastId = $this->getCursor('oracle_ticks');
        $stmt   = $this->readPdo->prepare(
            'SELECT * FROM oracle_ticks WHERE id > ? ORDER BY id LIMIT 1000'
        );
        $stmt->execute([$lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $insert    = [];
        $newLastId = $lastId;

        foreach ($rows as $row) {
            $assetId = $this->getAssetId($row['asset']);
            if ($assetId === null) {
                $newLastId = max($newLastId, (int) $row['id']);
                continue;
            }

            $priceUsd = (float) $row['price'];
            $priceBp  = (int) ($priceUsd * 100);

            $insert[] = [
                'asset_id'  => $assetId,
                'price_usd' => $priceUsd,
                'price_bp'  => $priceBp,
                'ts'        => (int) $row['ts'],
            ];

            $newLastId = max($newLastId, (int) $row['id']);
        }

        if (!empty($insert)) {
            DB::table('oracle_ticks')->insert($insert);
        }

        if ($newLastId > $lastId) {
            $this->updateCursor('oracle_ticks', $newLastId);
        }

        return count($insert);
    }

    private function syncClobSnapshots(): int
    {
        $lastId = $this->getCursor('clob_snapshots');
        $stmt   = $this->readPdo->prepare(
            'SELECT * FROM clob_snapshots WHERE id > ? ORDER BY id LIMIT 1000'
        );
        $stmt->execute([$lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $insert    = [];
        $newLastId = $lastId;

        foreach ($rows as $row) {
            $newLastId = max($newLastId, (int) $row['id']);

            $windowId = $this->getWindowIdByConditionId($row['condition_id']);
            if ($windowId === null) {
                // Window not yet synced — skip
                continue;
            }

            // Get asset_id from the window
            $assetId = DB::table('windows')->where('id', $windowId)->value('asset_id');
            if ($assetId === null) {
                continue;
            }

            $insert[] = [
                'window_id' => $windowId,
                'asset_id'  => $assetId,
                'yes_bid'   => isset($row['yes_bid']) ? (float) $row['yes_bid'] : null,
                'yes_ask'   => isset($row['yes_ask']) ? (float) $row['yes_ask'] : null,
                'no_bid'    => isset($row['no_bid']) ? (float) $row['no_bid'] : null,
                'no_ask'    => isset($row['no_ask']) ? (float) $row['no_ask'] : null,
                'ts'        => (int) $row['ts'],
            ];
        }

        if (!empty($insert)) {
            DB::table('clob_snapshots')->insert($insert);
        }

        if ($newLastId > $lastId) {
            $this->updateCursor('clob_snapshots', $newLastId);
        }

        return count($insert);
    }

    private function syncCandles(): int
    {
        $lastId = $this->getCursor('candles_1m');
        $stmt   = $this->readPdo->prepare(
            'SELECT * FROM candles_1m WHERE id > ? ORDER BY id LIMIT 1000'
        );
        $stmt->execute([$lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $insert    = [];
        $newLastId = $lastId;

        foreach ($rows as $row) {
            $assetId = $this->getAssetId($row['asset']);
            if ($assetId === null) {
                $newLastId = max($newLastId, (int) $row['id']);
                continue;
            }

            $insert[] = [
                'asset_id'  => $assetId,
                'open_usd'  => (float) $row['open'],
                'high_usd'  => (float) $row['high'],
                'low_usd'   => (float) $row['low'],
                'close_usd' => (float) $row['close'],
                'volume'    => isset($row['volume']) ? (float) $row['volume'] : null,
                'ts'        => (int) $row['ts'],
            ];

            $newLastId = max($newLastId, (int) $row['id']);
        }

        if (!empty($insert)) {
            // insertOrIgnore respects the unique constraint on (asset_id, ts)
            DB::table('candles_1m')->insertOrIgnore($insert);
        }

        if ($newLastId > $lastId) {
            $this->updateCursor('candles_1m', $newLastId);
        }

        return count($insert);
    }
}
