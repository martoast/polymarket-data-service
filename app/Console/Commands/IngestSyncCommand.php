<?php

namespace App\Console\Commands;

use App\Services\Ingest\SqliteSyncService;
use Illuminate\Console\Command;

class IngestSyncCommand extends Command
{
    protected $signature = 'ingest:sync
                            {--db= : Path to the SQLite file (overrides RECORDER_SQLITE_FILE env)}
                            {--once : Run once and exit (default behaviour)}
                            {--loop : Keep running, sleeping 30 seconds between passes}';

    protected $description = 'Sync new rows from the recorder SQLite file into PostgreSQL';

    public function handle(SqliteSyncService $service): int
    {
        $dbPath = $this->option('db') ?: null;
        $loop   = (bool) $this->option('loop');

        do {
            $stats = $service->sync($dbPath);

            $this->line(sprintf(
                '[%s] markets=%d  oracle_ticks=%d  clob_snapshots=%d  candles_1m=%d',
                now()->toDateTimeString(),
                $stats['markets'],
                $stats['oracle_ticks'],
                $stats['clob_snapshots'],
                $stats['candles_1m'],
            ));

            if ($loop) {
                sleep(30);
            }
        } while ($loop);

        return self::SUCCESS;
    }
}
