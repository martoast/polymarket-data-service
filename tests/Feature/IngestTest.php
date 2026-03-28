<?php

namespace Tests\Feature;

use App\Jobs\ResolveWindow;
use App\Models\Asset;
use App\Services\Ingest\SqliteSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PDO;
use Tests\TestCase;

class IngestTest extends TestCase
{
    use RefreshDatabase;

    private string $sqliteFile;
    private PDO    $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqliteFile = sys_get_temp_dir() . '/test_recorder_' . uniqid() . '.db';

        $this->pdo = new PDO(
            'sqlite:' . $this->sqliteFile,
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->createRecorderSchema();
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        unset($this->pdo);

        if (file_exists($this->sqliteFile)) {
            @unlink($this->sqliteFile);
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Schema helpers
    // -------------------------------------------------------------------------

    private function createRecorderSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS markets (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                condition_id  TEXT UNIQUE NOT NULL,
                slug          TEXT,
                question      TEXT,
                yes_token_id  TEXT,
                no_token_id   TEXT,
                open_ts       INTEGER,
                close_ts      INTEGER,
                break_price   REAL,
                winner        TEXT,
                resolved      INTEGER DEFAULT 0,
                created_at    INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS oracle_ticks (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                asset TEXT NOT NULL,
                price REAL NOT NULL,
                ts    INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS clob_snapshots (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                condition_id TEXT NOT NULL,
                yes_bid      REAL,
                yes_ask      REAL,
                no_bid       REAL,
                no_ask       REAL,
                ts           INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS candles_1m (
                id     INTEGER PRIMARY KEY AUTOINCREMENT,
                asset  TEXT NOT NULL,
                open   REAL NOT NULL,
                high   REAL NOT NULL,
                low    REAL NOT NULL,
                close  REAL NOT NULL,
                volume REAL,
                ts     INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS sync_cursors (
                table_name    TEXT PRIMARY KEY,
                last_id       INTEGER NOT NULL DEFAULT 0,
                last_synced_at INTEGER
            );

            INSERT OR IGNORE INTO sync_cursors (table_name, last_id) VALUES
                ('oracle_ticks', 0),
                ('clob_snapshots', 0),
                ('candles_1m', 0),
                ('markets', 0);
        ");
    }

    private function insertTestData(): void
    {
        // 1 market (open_ts in the past so ResolveWindow will be dispatched)
        $openTs  = (time() - 3600) * 1000;  // 1 hour ago
        $closeTs = (time() - 3300) * 1000;  // 55 min ago

        $slug = 'btc-updown-5m-' . intdiv($openTs, 1000);
        $this->pdo->exec("
            INSERT INTO markets
                (condition_id, slug, break_price, open_ts, close_ts, winner, created_at)
            VALUES
                ('0xABC123', '{$slug}', 87000.5, {$openTs}, {$closeTs}, 'YES', {$openTs});
        ");

        // 5 oracle ticks
        $baseTs = $openTs + 10000;
        for ($i = 0; $i < 5; $i++) {
            $ts    = $baseTs + ($i * 30000);
            $price = 87000 + ($i * 10);
            $this->pdo->exec("
                INSERT INTO oracle_ticks (asset, price, ts)
                VALUES ('BTC', {$price}, {$ts});
            ");
        }

        // 3 clob snapshots
        for ($i = 0; $i < 3; $i++) {
            $ts = $baseTs + ($i * 60000);
            $this->pdo->exec("
                INSERT INTO clob_snapshots (condition_id, yes_bid, yes_ask, no_bid, no_ask, ts)
                VALUES ('0xABC123', 0.88, 0.90, 0.10, 0.12, {$ts});
            ");
        }

        // 1 candle
        $this->pdo->exec("
            INSERT INTO candles_1m (asset, open, high, low, close, volume, ts)
            VALUES ('BTC', 87000, 87100, 86900, 87050, 1.23, {$openTs});
        ");
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_sync_imports_market_as_window(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();
        $stats   = $service->sync($this->sqliteFile);

        $this->assertEquals(1, $stats['markets']);
        $this->assertDatabaseCount('windows', 1);

        $window = \App\Models\Window::first();
        $this->assertNotNull($window);
        $this->assertEquals('BTC', $window->asset->symbol);
    }

    public function test_sync_imports_oracle_ticks(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();
        $stats   = $service->sync($this->sqliteFile);

        $this->assertEquals(5, $stats['oracle_ticks']);
        $this->assertDatabaseCount('oracle_ticks', 5);
    }

    public function test_sync_imports_clob_snapshots(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();

        // First sync markets so clob_snapshots can find the window
        $service->sync($this->sqliteFile);

        $this->assertEquals(3, $this->pdo
            ->query('SELECT COUNT(*) FROM clob_snapshots')
            ->fetchColumn());

        $this->assertDatabaseCount('clob_snapshots', 3);
    }

    public function test_sync_imports_candle(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();
        $stats   = $service->sync($this->sqliteFile);

        $this->assertEquals(1, $stats['candles_1m']);
        $this->assertDatabaseCount('candles_1m', 1);
    }

    public function test_second_sync_produces_no_duplicates(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();

        $service->sync($this->sqliteFile);
        $service->sync($this->sqliteFile); // second pass — should import nothing new

        $this->assertDatabaseCount('windows', 1);
        $this->assertDatabaseCount('oracle_ticks', 5);
        $this->assertDatabaseCount('clob_snapshots', 3);
        $this->assertDatabaseCount('candles_1m', 1);
    }

    public function test_winner_field_is_not_copied_to_outcome(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();
        $service->sync($this->sqliteFile);

        $window = \App\Models\Window::first();
        $this->assertNotNull($window, 'Window should have been created');
        $this->assertNull($window->outcome, 'outcome must remain null — set only by Gamma API');
    }

    public function test_resolve_window_job_dispatched_for_closed_window(): void
    {
        Queue::fake();

        $service = new SqliteSyncService();
        $service->sync($this->sqliteFile);

        Queue::assertPushed(ResolveWindow::class);
    }

    public function test_sync_returns_zero_stats_when_sqlite_file_missing(): void
    {
        $service = new SqliteSyncService();
        $stats   = $service->sync('/tmp/nonexistent_' . uniqid() . '.db');

        $this->assertEquals(0, $stats['markets']);
        $this->assertEquals(0, $stats['oracle_ticks']);
        $this->assertEquals(0, $stats['clob_snapshots']);
        $this->assertEquals(0, $stats['candles_1m']);
    }
}
