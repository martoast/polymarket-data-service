<?php

namespace App\Console\Commands;

use App\Services\Ingest\SessionImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillRecordingsCommand extends Command
{
    protected $signature = 'backfill:recordings
        {path? : Path to recordings directory (defaults to RECORDINGS_PATH env var)}
        {--from= : Only import sessions on or after this date (YYYY-MM-DD)}
        {--to= : Only import sessions on or before this date (YYYY-MM-DD)}
        {--force : Re-process files that have already been imported}
        {--dry-run : Parse files and show counts without writing to the database}';

    protected $description = 'Import historical session JSONL recordings into the database';

    public function handle(SessionImportService $importer): int
    {
        $path = $this->argument('path') ?? env('RECORDINGS_PATH');

        if (! $path) {
            $this->error('No path given. Pass a path argument or set RECORDINGS_PATH in .env');
            return self::FAILURE;
        }

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $force  = $this->option('force');
        $from   = $this->option('from');
        $to     = $this->option('to');

        // Discover session files
        $files = collect(glob("{$path}/session_*.jsonl"))
            ->sortBy(fn($f) => basename($f))
            ->filter(fn($f) => $this->withinDateRange(basename($f), $from, $to))
            ->values();

        if ($files->isEmpty()) {
            $this->warn('No session files found matching the given criteria.');
            return self::SUCCESS;
        }

        // Determine which are already processed
        $processed = DB::table('backfill_sessions')
            ->whereIn('filename', $files->map(fn($f) => basename($f)))
            ->pluck('filename')
            ->flip()
            ->all();

        $toProcess = $files->filter(fn($f) => $force || ! isset($processed[basename($f)]));
        $skipCount = $files->count() - $toProcess->count();

        // Summary
        $this->line('');
        $this->info("Recordings path : {$path}");
        $this->info("Files found     : {$files->count()}");
        $this->info("Already imported: {$skipCount}" . ($force ? ' (--force: will re-process)' : ''));
        $this->info("Will process    : {$toProcess->count()}");
        if ($dryRun) $this->warn('DRY RUN — no data will be written');
        $this->line('');

        if ($toProcess->isEmpty()) {
            $this->info('Nothing to import. Use --force to re-process.');
            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->confirm("Import {$toProcess->count()} file(s)?", true)) {
            return self::SUCCESS;
        }

        // Process each file
        $totals = ['windows' => 0, 'oracle' => 0, 'clob' => 0, 'candles' => 0, 'resolutions' => 0, 'skipped_clob' => 0];
        $rows   = [];

        foreach ($toProcess as $filepath) {
            $filename = basename($filepath);
            $this->line("  → {$filename}");

            try {
                $stats = $importer->import($filepath, $dryRun);

                $this->line(sprintf(
                    '    windows:%d oracle:%d clob:%d candles:%d resolved:%d skipped:%d',
                    $stats['windows'],
                    $stats['oracle'],
                    $stats['clob'],
                    $stats['candles'],
                    $stats['resolutions'],
                    $stats['skipped_clob'],
                ));

                foreach (['windows', 'oracle', 'clob', 'candles', 'resolutions', 'skipped_clob'] as $k) {
                    $totals[$k] += $stats[$k];
                }

                $rows[] = [
                    'filename'     => $filename,
                    'processed_at' => now(),
                    'total_lines'  => $stats['total_lines'],
                    'windows'      => $stats['windows'],
                    'oracle'       => $stats['oracle'],
                    'clob'         => $stats['clob'],
                    'candles'      => $stats['candles'],
                    'resolutions'  => $stats['resolutions'],
                    'skipped_clob' => $stats['skipped_clob'],
                ];

                if (! $dryRun) {
                    DB::table('backfill_sessions')->upsert(
                        end($rows),
                        ['filename'],
                        ['processed_at', 'total_lines', 'windows', 'oracle', 'clob', 'candles', 'resolutions', 'skipped_clob']
                    );
                }

            } catch (\Throwable $e) {
                $this->error("    FAILED: {$e->getMessage()}");
            }
        }

        // Final summary
        $this->line('');
        $this->info('Done' . ($dryRun ? ' (dry run)' : '') . ':');
        $this->table(
            ['Windows', 'Oracle Ticks', 'CLOB Snapshots', 'Candles', 'Resolutions', 'CLOB Skipped'],
            [[$totals['windows'], $totals['oracle'], $totals['clob'], $totals['candles'], $totals['resolutions'], $totals['skipped_clob']]]
        );

        return self::SUCCESS;
    }

    private function withinDateRange(string $filename, ?string $from, ?string $to): bool
    {
        // filename: session_YYYY-MM-DD_HHMM.jsonl
        if (! preg_match('/session_(\d{4}-\d{2}-\d{2})_/', $filename, $m)) {
            return false;
        }

        $date = $m[1];

        if ($from && $date < $from) return false;
        if ($to   && $date > $to)   return false;

        return true;
    }
}
