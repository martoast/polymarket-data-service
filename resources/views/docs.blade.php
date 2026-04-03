<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>API Reference — Polymarket Data API</title>
    <meta name="description" content="Complete API reference for the Polymarket Data API. Multi-market: crypto oracle ticks, weather temperature feeds, CLOB order book snapshots, and ML features.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        .mono { font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #17181c; }
        ::-webkit-scrollbar-thumb { background: #2e3841; border-radius: 4px; }
        [x-cloak] { display: none !important; }
        .nav-link.active { color: #0093fd !important; background: rgba(0,147,253,0.08); }
        html { scroll-behavior: smooth; }
    </style>
</head>

@php
$base = config('app.url');

// Sidebar groups — used to render section headers in the nav
$sidebarGroups = [
    'getting-started' => 'Getting Started',
    'discovery'       => 'Discovery',
    'markets'         => 'Markets',
    'crypto'          => 'Crypto',
    'weather'         => 'Weather',
    'clob'            => 'CLOB',
    'features'        => 'Features',
    'pro'             => 'Pro',
];

$sections = [

    // ── Discovery ────────────────────────────────────────────────────────
    [
        'id'    => 'categories',
        'label' => 'Categories',
        'group' => 'discovery',
        'desc'  => 'List all active market categories available on this API.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/categories',
                'title'   => 'List Categories',
                'desc'    => 'Returns all active market categories. Use the slug values as the `category` filter on other endpoints.',
                'params'  => [],
                'example' => "{\n  \"data\": [\n    {\n      \"id\": 1,\n      \"slug\": \"crypto\",\n      \"name\": \"Crypto\",\n      \"description\": \"Cryptocurrency price prediction markets (BTC, ETH, SOL)\"\n    },\n    {\n      \"id\": 2,\n      \"slug\": \"weather\",\n      \"name\": \"Weather\",\n      \"description\": \"Daily weather prediction markets (temperature highs, etc.)\"\n    }\n  ]\n}",
            ],
        ],
    ],

    // ── Markets ──────────────────────────────────────────────────────────
    [
        'id'    => 'markets',
        'label' => 'Markets',
        'group' => 'markets',
        'desc'  => 'Unified market index across all categories. Each market is a single Polymarket binary event with timestamps, break value, outcome, and coverage metadata.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/markets',
                'title'   => 'List Markets',
                'desc'    => 'Cursor-paginated list of markets. Filter by category to get crypto or weather markets. Only returns markets where `break_value > 0` (a valid signal exists). Markets with an active `close_ts` in the future or a non-null `outcome` are included.',
                'params'  => [
                    ['name'=>'category', 'type'=>'select', 'options'=>['','crypto','weather'], 'required'=>false],
                    ['name'=>'outcome',  'type'=>'select', 'options'=>['','YES','NO'],          'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'50',                'required'=>false],
                    ['name'=>'cursor',   'type'=>'text',   'placeholder'=>'eyJpZCI6Li4ufQ==', 'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"id\": \"btc-updown-5m-1774770300\",\n      \"category\": \"crypto\",\n      \"asset\": \"BTC\",\n      \"duration_sec\": 300,\n      \"duration_label\": \"5m\",\n      \"break_value\": 84231.50,\n      \"value_unit\": \"usd\",\n      \"open_ts\": 1774770300000,\n      \"close_ts\": 1774770600000,\n      \"outcome\": \"YES\",\n      \"resolved_ts\": 1774770601234,\n      \"has_coverage\": true\n    },\n    {\n      \"id\": \"highest-temperature-in-tokyo-on-april-6-2026-15\",\n      \"category\": \"weather\",\n      \"asset\": \"RJTT\",\n      \"duration_sec\": 86400,\n      \"duration_label\": \"1d\",\n      \"break_value\": 15,\n      \"value_unit\": \"celsius\",\n      \"open_ts\": 1775088000000,\n      \"close_ts\": 1775174400000,\n      \"outcome\": null,\n      \"resolved_ts\": null,\n      \"has_coverage\": true\n    }\n  ],\n  \"next_cursor\": \"eyJpZCI6ImJ0Yy11cGRvd24ifQ==\",\n  \"has_more\": true\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/markets/active',
                'title'   => 'Active Markets',
                'desc'    => 'All markets currently open (close_ts in the future) being recorded by the live feed. Useful for populating a live dashboard or deciding which markets to subscribe your bot to.',
                'params'  => [
                    ['name'=>'category', 'type'=>'select', 'options'=>['','crypto','weather'], 'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"id\": \"btc-updown-5m-1775180100\",\n      \"category\": \"crypto\",\n      \"asset\": \"BTC\",\n      \"break_value\": 84000.00,\n      \"value_unit\": \"usd\",\n      \"duration_label\": \"5m\",\n      \"open_ts\": 1775180100000,\n      \"close_ts\": 1775180400000,\n      \"outcome\": null,\n      \"has_coverage\": true\n    }\n  ]\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/markets/{id}',
                'title'   => 'Get Market',
                'desc'    => 'Full detail for a single market by ID. Market IDs are stable slugs (crypto) or Gamma-style slugs (weather). The `condition_id`, `yes_token_id`, and `no_token_id` fields are Polymarket\'s own identifiers.',
                'params'  => [
                    ['name'=>'id', 'type'=>'text', 'placeholder'=>'btc-updown-5m-1774770300', 'required'=>true, 'pathParam'=>true],
                ],
                'example' => "{\n  \"data\": {\n    \"id\": \"btc-updown-5m-1774770300\",\n    \"category\": \"crypto\",\n    \"asset\": \"BTC\",\n    \"duration_sec\": 300,\n    \"duration_label\": \"5m\",\n    \"break_value\": 84231.50,\n    \"value_unit\": \"usd\",\n    \"open_ts\": 1774770300000,\n    \"close_ts\": 1774770600000,\n    \"outcome\": \"YES\",\n    \"resolved_ts\": 1774770601234,\n    \"has_coverage\": true,\n    \"condition_id\": \"0xabc...\",\n    \"yes_token_id\": \"123...\",\n    \"no_token_id\": \"456...\"\n  }\n}",
            ],
        ],
    ],

    // ── Crypto ───────────────────────────────────────────────────────────
    [
        'id'    => 'oracle-ticks',
        'label' => 'Oracle Ticks',
        'group' => 'crypto',
        'desc'  => 'Raw Chainlink RTDS price recordings for BTC, ETH, and SOL. These are the exact prices Polymarket uses to settle crypto markets. Captured via WebSocket with millisecond timestamps.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/crypto/oracle/ticks',
                'title'   => 'Oracle Ticks',
                'desc'    => 'Cursor-paginated raw oracle price recordings. Filter by asset and/or timestamp range. `ts` is Unix milliseconds. Dead-band filtered — only ticks where price moved ≥ 0.01% or a 30s heartbeat fired are stored.',
                'params'  => [
                    ['name'=>'asset',    'type'=>'select', 'options'=>['','BTC','ETH','SOL'], 'required'=>false],
                    ['name'=>'from',     'type'=>'text',   'placeholder'=>'1774770300000',   'required'=>false],
                    ['name'=>'to',       'type'=>'text',   'placeholder'=>'1774770600000',   'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'500',             'required'=>false],
                    ['name'=>'cursor',   'type'=>'text',   'placeholder'=>'eyJ...',          'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    { \"asset\": \"BTC\", \"price_usd\": 84231.50, \"price_bp\": 8423150, \"ts\": 1774770301234 },\n    { \"asset\": \"BTC\", \"price_usd\": 84245.10, \"price_bp\": 8424510, \"ts\": 1774770304567 },\n    { \"asset\": \"BTC\", \"price_usd\": 84239.80, \"price_bp\": 8423980, \"ts\": 1774770307890 }\n  ],\n  \"next_cursor\": \"eyJ0cyI6MTc3NDc3MDMwNzg5MH0=\",\n  \"has_more\": true\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/crypto/oracle/range',
                'title'   => 'Oracle Range',
                'desc'    => 'OHLC summary statistics for oracle ticks within a time range or scoped to a specific market. Useful for reconstructing candles or computing price movement at the market level.',
                'params'  => [
                    ['name'=>'asset',     'type'=>'select', 'options'=>['','BTC','ETH','SOL'], 'required'=>true],
                    ['name'=>'market_id', 'type'=>'text',   'placeholder'=>'btc-updown-5m-1774770300', 'required'=>false],
                    ['name'=>'from',      'type'=>'text',   'placeholder'=>'1774770300000',   'required'=>false],
                    ['name'=>'to',        'type'=>'text',   'placeholder'=>'1774770600000',   'required'=>false],
                ],
                'example' => "{\n  \"data\": {\n    \"asset\": \"BTC\",\n    \"count\": 47,\n    \"open\": 84231.50,\n    \"close\": 84312.00,\n    \"high\": 84380.00,\n    \"low\": 84198.20,\n    \"from\": 1774770300000,\n    \"to\": 1774770600000\n  }\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/crypto/oracle/aligned',
                'title'   => 'Oracle Aligned',
                'desc'    => 'Oracle ticks joined with CLOB snapshots for a specific market. Each row is one oracle tick with the contemporaneous Yes/No bid-ask depth. Ideal for time-series model input — everything pre-joined, ordered by `ts`.',
                'params'  => [
                    ['name'=>'market_id', 'type'=>'text', 'placeholder'=>'btc-updown-5m-1774770300', 'required'=>true],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"ts\": 1774770301234,\n      \"price_usd\": 84231.50,\n      \"price_bp\": 8423150,\n      \"yes_bid\": 0.58,\n      \"yes_ask\": 0.60,\n      \"no_bid\": 0.40,\n      \"no_ask\": 0.42\n    },\n    {\n      \"ts\": 1774770304567,\n      \"price_usd\": 84245.10,\n      \"price_bp\": 8424510,\n      \"yes_bid\": 0.59,\n      \"yes_ask\": 0.61,\n      \"no_bid\": 0.39,\n      \"no_ask\": 0.41\n    }\n  ]\n}",
            ],
        ],
    ],

    // ── Weather ──────────────────────────────────────────────────────────
    [
        'id'    => 'weather-stations',
        'label' => 'Stations',
        'group' => 'weather',
        'desc'  => '10 weather station assets tracked globally. Each station polls Open-Meteo every 5 minutes and maps to a set of daily high temperature markets on Polymarket. International stations use °C markets; US stations use °F markets.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/weather/stations',
                'title'   => 'List Stations',
                'desc'    => 'All active weather station assets. `unit` is `celsius` or `fahrenheit` — matches the unit Polymarket uses for that city\'s markets. Use `symbol` as the `asset` parameter in weather reading endpoints.',
                'params'  => [],
                'example' => "{\n  \"data\": [\n    { \"symbol\": \"RJTT\", \"name\": \"Tokyo High Temperature\",          \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"RJTT\", \"city\": \"Tokyo\",       \"country\": \"JP\", \"latitude\": 35.5494,  \"longitude\": 139.7798,  \"timezone\": \"Asia/Tokyo\" } },\n    { \"symbol\": \"EGLL\", \"name\": \"London High Temperature\",         \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"EGLL\", \"city\": \"London\",      \"country\": \"GB\", \"latitude\": 51.4775,  \"longitude\": -0.4614,   \"timezone\": \"Europe/London\" } },\n    { \"symbol\": \"LFPG\", \"name\": \"Paris High Temperature\",          \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"LFPG\", \"city\": \"Paris\",       \"country\": \"FR\", \"latitude\": 49.0097,  \"longitude\": 2.5479,    \"timezone\": \"Europe/Paris\" } },\n    { \"symbol\": \"WSSS\", \"name\": \"Singapore High Temperature\",      \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"WSSS\", \"city\": \"Singapore\",   \"country\": \"SG\", \"latitude\": 1.3644,   \"longitude\": 103.9915,  \"timezone\": \"Asia/Singapore\" } },\n    { \"symbol\": \"RKSI\", \"name\": \"Seoul High Temperature\",          \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"RKSI\", \"city\": \"Seoul\",       \"country\": \"KR\", \"latitude\": 37.4693,  \"longitude\": 126.4503,  \"timezone\": \"Asia/Seoul\" } },\n    { \"symbol\": \"ZBAA\", \"name\": \"Beijing High Temperature\",        \"unit\": \"celsius\",    \"source_config\": { \"icao\": \"ZBAA\", \"city\": \"Beijing\",     \"country\": \"CN\", \"latitude\": 40.0799,  \"longitude\": 116.5857,  \"timezone\": \"Asia/Shanghai\" } },\n    { \"symbol\": \"KORD\", \"name\": \"Chicago High Temperature\",        \"unit\": \"fahrenheit\", \"source_config\": { \"icao\": \"KORD\", \"city\": \"Chicago\",     \"country\": \"US\", \"latitude\": 41.9742,  \"longitude\": -87.9073,  \"timezone\": \"America/Chicago\" } },\n    { \"symbol\": \"KJFK\", \"name\": \"New York City High Temperature\",  \"unit\": \"fahrenheit\", \"source_config\": { \"icao\": \"KJFK\", \"city\": \"nyc\",         \"country\": \"US\", \"latitude\": 40.6413,  \"longitude\": -73.7781,  \"timezone\": \"America/New_York\" } },\n    { \"symbol\": \"KLAX\", \"name\": \"Los Angeles High Temperature\",    \"unit\": \"fahrenheit\", \"source_config\": { \"icao\": \"KLAX\", \"city\": \"Los Angeles\", \"country\": \"US\", \"latitude\": 33.9425,  \"longitude\": -118.4081, \"timezone\": \"America/Los_Angeles\" } },\n    { \"symbol\": \"KMIA\", \"name\": \"Miami High Temperature\",          \"unit\": \"fahrenheit\", \"source_config\": { \"icao\": \"KMIA\", \"city\": \"Miami\",       \"country\": \"US\", \"latitude\": 25.7959,  \"longitude\": -80.2870,  \"timezone\": \"America/New_York\" } }\n  ]\n}",
            ],
        ],
    ],
    [
        'id'    => 'weather-readings',
        'label' => 'Readings',
        'group' => 'weather',
        'desc'  => 'Live temperature readings polled from Open-Meteo every 5 minutes per station. Includes running daily maximum in both °C and °F, keyed to the station\'s local timezone. `running_daily_max_c` resets at local midnight.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/weather/readings',
                'title'   => 'Temperature Readings',
                'desc'    => 'Cursor-paginated temperature readings. Filter by station (`asset`), local date, or timestamp range. `ts` is Unix milliseconds.',
                'params'  => [
                    ['name'=>'asset',    'type'=>'select', 'options'=>['','RJTT','EGLL','LFPG','WSSS','RKSI','ZBAA','KORD','KJFK','KLAX','KMIA'], 'required'=>false],
                    ['name'=>'date',     'type'=>'text',   'placeholder'=>'2026-04-03', 'required'=>false],
                    ['name'=>'from',     'type'=>'text',   'placeholder'=>'1775088000000', 'required'=>false],
                    ['name'=>'to',       'type'=>'text',   'placeholder'=>'1775174400000', 'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'500',        'required'=>false],
                    ['name'=>'cursor',   'type'=>'text',   'placeholder'=>'eyJ...',     'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"symbol\": \"KJFK\",\n      \"temp_c\": 4.8,\n      \"temp_f\": 40.64,\n      \"running_daily_max_c\": 4.8,\n      \"source\": \"observed\",\n      \"station_local_date\": \"2026-04-02\",\n      \"ts\": 1775174459773\n    },\n    {\n      \"symbol\": \"RJTT\",\n      \"temp_c\": 16.4,\n      \"temp_f\": 61.52,\n      \"running_daily_max_c\": 16.4,\n      \"source\": \"observed\",\n      \"station_local_date\": \"2026-04-03\",\n      \"ts\": 1775185305842\n    }\n  ],\n  \"next_cursor\": \"eyJ0cyI6MTc3NTE4NTMwNTg0Mn0=\",\n  \"has_more\": false\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/weather/daily-max',
                'title'   => 'Daily Maximum',
                'desc'    => 'Running daily maximum temperature for a station on a given date (defaults to today in the station\'s local timezone). Returns both °C and °F. `day_elapsed_pct` is how far through the local day we are — useful for building resolution confidence signals. US city markets resolve against `running_daily_max_f`; international markets against `running_daily_max_c`.',
                'params'  => [
                    ['name'=>'asset', 'type'=>'select', 'options'=>['RJTT','EGLL','LFPG','WSSS','RKSI','ZBAA','KORD','KJFK','KLAX','KMIA'], 'required'=>true],
                    ['name'=>'date',  'type'=>'text',   'placeholder'=>'2026-04-03 (default: today local)', 'required'=>false],
                ],
                'example' => "{\n  \"data\": {\n    \"asset\": \"KJFK\",\n    \"unit\": \"fahrenheit\",\n    \"station_local_date\": \"2026-04-02\",\n    \"current_temp_c\": 4.8,\n    \"current_temp_f\": 40.64,\n    \"running_daily_max_c\": 4.8,\n    \"running_daily_max_f\": 40.6,\n    \"day_elapsed_pct\": 83.2,\n    \"reading_count\": 10,\n    \"ts\": 1775174459773\n  }\n}",
            ],
        ],
    ],

    // ── CLOB ─────────────────────────────────────────────────────────────
    [
        'id'    => 'clob',
        'label' => 'CLOB Snapshots',
        'group' => 'clob',
        'desc'  => 'Yes/No bid-ask order book depth captured from the Polymarket CLOB WebSocket. Shared across all market categories — one row per market per second (flushed on a 1-second interval).',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/clob/snapshots',
                'title'   => 'CLOB Snapshots',
                'desc'    => 'Cursor-paginated bid-ask snapshots for a market. Requires `market_id`. Optionally filter by timestamp range. Works for both crypto and weather markets — the field names are the same regardless of category.',
                'params'  => [
                    ['name'=>'market_id', 'type'=>'text',   'placeholder'=>'btc-updown-5m-1774770300', 'required'=>true],
                    ['name'=>'from',      'type'=>'text',   'placeholder'=>'1774770300000', 'required'=>false],
                    ['name'=>'to',        'type'=>'text',   'placeholder'=>'1774770600000', 'required'=>false],
                    ['name'=>'per_page',  'type'=>'number', 'placeholder'=>'500',           'required'=>false],
                    ['name'=>'cursor',    'type'=>'text',   'placeholder'=>'eyJ...',        'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"market_id\": \"btc-updown-5m-1774770300\",\n      \"yes_bid\": 0.58,\n      \"yes_ask\": 0.60,\n      \"no_bid\":  0.40,\n      \"no_ask\":  0.42,\n      \"ts\": 1774770301000\n    },\n    {\n      \"market_id\": \"btc-updown-5m-1774770300\",\n      \"yes_bid\": 0.59,\n      \"yes_ask\": 0.61,\n      \"no_bid\":  0.39,\n      \"no_ask\":  0.41,\n      \"ts\": 1774770302000\n    }\n  ],\n  \"next_cursor\": \"eyJ0cyI6MTc3NDc3MDMwMjAwMH0=\",\n  \"has_more\": true\n}",
            ],
        ],
    ],

    // ── Features ─────────────────────────────────────────────────────────
    [
        'id'    => 'features',
        'label' => 'Features',
        'group' => 'features',
        'desc'  => 'Pre-computed ML feature vectors for resolved markets. Each category has its own feature schema — crypto features include oracle-based signals, weather features include temperature and forecast-based signals.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/features',
                'title'   => 'List Features',
                'desc'    => 'Cursor-paginated flat feature vectors for resolved markets. The `category` parameter is required — it determines which feature schema is returned. Ideal for loading directly into a Pandas DataFrame or NumPy array.',
                'params'  => [
                    ['name'=>'category', 'type'=>'select', 'options'=>['crypto','weather'],  'required'=>true],
                    ['name'=>'market_id','type'=>'text',   'placeholder'=>'(optional) filter to one market', 'required'=>false],
                    ['name'=>'outcome',  'type'=>'select', 'options'=>['','YES','NO'],        'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'200',             'required'=>false],
                    ['name'=>'cursor',   'type'=>'text',   'placeholder'=>'eyJ...',          'required'=>false],
                ],
                'example' => "// category=crypto\n{\n  \"data\": [\n    {\n      \"market_id\": \"btc-updown-5m-1774770300\",\n      \"outcome\": \"YES\",\n      \"oracle_dist_bp_at_open\": 0,\n      \"oracle_dist_bp_at_1m\": 142,\n      \"oracle_dist_bp_at_close\": 803,\n      \"yes_bid_open\": 0.58,\n      \"yes_ask_open\": 0.60,\n      \"clob_imbalance_open\": 0.062,\n      \"twap_1m_usd\": 84198.22,\n      \"vol_1m\": 89.3,\n      \"momentum_3m\": 0.0041,\n      \"spread_mean\": 0.021\n    }\n  ],\n  \"next_cursor\": \"eyJtYXJrZXRfaWQiOiJidGMtdXBkb3duIn0=\",\n  \"has_more\": true\n}\n\n// category=weather\n{\n  \"data\": [\n    {\n      \"market_id\": \"highest-temperature-in-tokyo-on-april-3-2026-14\",\n      \"asset\": \"RJTT\",\n      \"outcome\": \"NO\",\n      \"break_value_c\": 14,\n      \"temp_at_open_c\": 10.2,\n      \"running_max_at_close_c\": 13.8,\n      \"final_max_c\": 13.8,\n      \"day_elapsed_at_open_pct\": 14.5,\n      \"season\": \"spring\"\n    }\n  ],\n  \"has_more\": false\n}",
            ],
        ],
    ],

    // ── Pro ──────────────────────────────────────────────────────────────
    [
        'id'    => 'export',
        'label' => 'Export',
        'group' => 'pro',
        'desc'  => 'Bulk downloads for offline model training. Pro tier only.',
        'pro'   => true,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/export/csv',
                'title'   => 'Export CSV',
                'desc'    => 'Download all market features as a CSV file. Streams directly — no pagination required. Filter by category to get crypto or weather features. Returns a file download with `Content-Disposition: attachment`.',
                'params'  => [
                    ['name'=>'category', 'type'=>'select', 'options'=>['','crypto','weather'], 'required'=>false],
                ],
                'example' => "// category=crypto (response headers)\nContent-Type: text/csv\nContent-Disposition: attachment; filename=\"features_crypto.csv\"\n\n// CSV rows\nmarket_id,outcome,oracle_dist_bp_at_1m,clob_imbalance_open,twap_1m_usd,...\nbtc-updown-5m-1774770300,YES,142,0.062,84198.22,...\nbtc-updown-5m-1774770600,NO,-68,-0.031,84245.10,...",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/export/sqlite',
                'title'   => 'Export SQLite',
                'desc'    => 'Download a complete SQLite database containing all tables: markets, oracle_ticks, clob_snapshots, weather_readings, crypto_market_features, weather_market_features. Useful for local analysis with DuckDB, pandas, or any SQL tool.',
                'params'  => [],
                'example' => "// Response headers\nContent-Type: application/x-sqlite3\nContent-Disposition: attachment; filename=\"polymarket_export.db\"\n\n// Tables included\n→ markets\n→ oracle_ticks\n→ clob_snapshots\n→ weather_readings\n→ crypto_market_features\n→ weather_market_features",
            ],
        ],
    ],
    [
        'id'    => 'backtest',
        'label' => 'Backtest',
        'group' => 'pro',
        'desc'  => 'Run a parameter sweep against the full resolved market history. Pro tier only.',
        'pro'   => true,
        'endpoints' => [
            [
                'method'  => 'POST',
                'path'    => '/api/v1/backtest',
                'title'   => 'Run Backtest',
                'desc'    => 'Submit a strategy definition and receive performance metrics synchronously. Runs against all resolved markets in the specified date range and category.',
                'params'  => [
                    ['name'=>'strategy', 'type'=>'text',   'placeholder'=>'momentum',      'required'=>true],
                    ['name'=>'category', 'type'=>'select', 'options'=>['crypto','weather'], 'required'=>true],
                    ['name'=>'from',     'type'=>'text',   'placeholder'=>'2026-01-01',    'required'=>true],
                    ['name'=>'to',       'type'=>'text',   'placeholder'=>'2026-04-01',    'required'=>true],
                ],
                'example' => "{\n  \"data\": {\n    \"strategy\": \"momentum\",\n    \"category\": \"crypto\",\n    \"total_trades\": 1240,\n    \"win_rate\": 0.573,\n    \"sharpe\": 1.42,\n    \"total_return\": 0.183,\n    \"max_drawdown\": -0.072\n  }\n}",
            ],
        ],
    ],
];
@endphp

<body class="bg-[#0a0b10] text-[#e5e5e5] antialiased" x-data="docsApp()" x-init="init()">

{{-- ── Top Nav ──────────────────────────────────────────────────────────── --}}
<nav class="fixed top-0 left-0 right-0 z-50 h-14 border-b border-[#1f2937] bg-[#0a0b10]/95 backdrop-blur-sm flex items-center px-5 gap-4">
    <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
        <div class="w-6 h-6 rounded-md bg-[#0093fd] flex items-center justify-center">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1L13 4.5V9.5L7 13L1 9.5V4.5L7 1Z" fill="white" fill-opacity="0.9"/></svg>
        </div>
        <span class="text-white font-semibold text-sm hidden sm:block">Polymarket Data API</span>
    </a>
    <span class="text-[#2e3841] hidden sm:block">/</span>
    <span class="text-[#697d91] text-sm font-medium hidden sm:block">API Reference</span>

    <div class="flex-1"></div>

    {{-- Inline key input --}}
    <div class="flex items-center gap-2">
        <label class="text-[#697d91] text-xs hidden md:block flex-shrink-0">API Key</label>
        <div class="relative">
            <input
                type="password"
                x-model="apiKey"
                @input="saveKey()"
                placeholder="Paste key to test live"
                class="bg-[#17181c] border border-[#1f2937] focus:border-[#0093fd] rounded-lg px-3 py-1.5 text-xs text-[#e5e5e5] placeholder-[#2e3841] w-44 sm:w-56 outline-none transition-colors mono"
            />
            <div x-show="apiKey" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-[#0093fd]"></div>
        </div>
    </div>
    <a href="{{ route('dashboard') }}" class="text-[#697d91] hover:text-[#e5e5e5] text-sm font-medium transition-colors hidden sm:block">Dashboard</a>
</nav>

{{-- ── Layout ───────────────────────────────────────────────────────────── --}}
<div class="flex pt-14">

    {{-- Sidebar --}}
    <aside class="fixed top-14 left-0 bottom-0 w-56 border-r border-[#1f2937] overflow-y-auto hidden lg:block py-6 px-3">
        <div class="space-y-0.5">

            <p class="text-[10px] font-semibold uppercase tracking-widest text-[#2e3841] px-3 py-2">Getting Started</p>
            <a href="#introduction"   class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">Introduction</a>
            <a href="#authentication" class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">Authentication</a>
            <a href="#rate-limits"    class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">Rate Limits</a>
            <a href="#errors"         class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">Errors</a>

            @php $lastGroup = null; @endphp
            @foreach($sections as $s)
                @if($s['group'] !== $lastGroup)
                    @php $lastGroup = $s['group']; @endphp
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-[#2e3841] px-3 py-2 mt-4">{{ $sidebarGroups[$s['group']] }}</p>
                @endif
                <a href="#{{ $s['id'] }}" class="nav-link flex items-center justify-between px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">
                    {{ $s['label'] }}
                    @if($s['pro'])
                        <span class="text-[10px] font-semibold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full border border-purple-500/20 leading-none">Pro</span>
                    @endif
                </a>
            @endforeach

        </div>
    </aside>

    {{-- Content --}}
    <main class="lg:ml-56 flex-1 min-w-0">
        <div class="max-w-3xl mx-auto px-5 sm:px-8 lg:px-12 py-12 space-y-20">

        {{-- ── Introduction ─────────────────────────────────────────── --}}
        <section id="introduction" class="scroll-mt-20">
            <span class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest">Getting Started</span>
            <h1 class="text-3xl font-bold text-white mt-2 mb-3">API Reference</h1>
            <p class="text-[#697d91] leading-relaxed mb-4">
                Programmatic access to Polymarket market data across all categories. One API key covers everything — crypto oracle prices, live weather temperature feeds, CLOB order book depth, and pre-computed ML features.
            </p>
            <p class="text-[#697d91] leading-relaxed mb-6">
                All endpoints return <code class="mono text-xs text-[#e5e5e5]">application/json</code>. List endpoints use cursor-based pagination. Timestamps are Unix milliseconds throughout.
            </p>

            {{-- Category overview --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <div class="bg-[#17181c] border border-[#0093fd]/20 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 rounded-md bg-[#0093fd]/15 flex items-center justify-center">
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M5 1l4 2.5v3L5 9 1 6.5v-3L5 1Z" stroke="#0093fd" stroke-width="1" stroke-linejoin="round"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Crypto</span>
                        <code class="mono text-[10px] text-[#697d91] ml-auto">category=crypto</code>
                    </div>
                    <p class="text-xs text-[#697d91] leading-relaxed">BTC, ETH, SOL Up/Down markets. Oracle prices from Chainlink RTDS. 5m duration markets settled every cycle.</p>
                </div>
                <div class="bg-[#17181c] border border-[#26a05e]/20 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 rounded-md bg-[#26a05e]/15 flex items-center justify-center">
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M5 1v1.5M5 7.5V9M1 5h1.5M7.5 5H9M2.6 2.6l1.1 1.1M6.3 6.3l1.1 1.1M2.6 7.4l1.1-1.1M6.3 3.7l1.1-1.1" stroke="#26a05e" stroke-width="1" stroke-linecap="round"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-white">Weather</span>
                        <code class="mono text-[10px] text-[#697d91] ml-auto">category=weather</code>
                    </div>
                    <p class="text-xs text-[#697d91] leading-relaxed">Daily highest temperature markets across 10 cities (Tokyo, London, Paris, Singapore, Seoul, Beijing, Chicago, NYC, LA, Miami). Open-Meteo polled every 5 min. °C and °F markets supported.</p>
                </div>
            </div>

            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5 mb-4">
                <p class="text-xs text-[#697d91] mb-1.5">Base URL</p>
                <div class="flex items-center gap-3">
                    <code class="mono text-sm text-[#0093fd] flex-1 break-all">{{ $base }}/api</code>
                    <button onclick="navigator.clipboard.writeText('{{ $base }}/api')"
                        class="text-xs text-[#697d91] hover:text-[#e5e5e5] border border-[#2e3841] px-2.5 py-1 rounded-lg transition-colors flex-shrink-0">Copy</button>
                </div>
            </div>

            <div class="bg-[#0093fd]/5 border border-[#0093fd]/15 rounded-2xl p-4 flex items-start gap-3">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="mt-0.5 flex-shrink-0"><circle cx="8" cy="8" r="7" stroke="#0093fd" stroke-width="1.3"/><path d="M8 7v5M8 5h.01" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round"/></svg>
                <p class="text-sm text-[#697d91]">
                    Paste your API key in the nav bar above to make every endpoint on this page testable with one click.
                    @guest <a href="{{ route('register') }}" class="text-[#0093fd] hover:underline">Create a free account</a> to get your key. @endguest
                </p>
            </div>
        </section>

        {{-- ── Authentication ───────────────────────────────────────── --}}
        <section id="authentication" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-2">Authentication</h2>
            <p class="text-[#697d91] text-sm mb-4">Every data request requires a <code class="mono text-xs text-[#0093fd]">Bearer</code> token in the <code class="mono text-xs text-[#0093fd]">Authorization</code> header. Get yours from the <a href="{{ route('dashboard') }}" class="text-[#0093fd] hover:underline">dashboard</a>.</p>
            <p class="text-[#697d91] text-sm mb-5">Email verification is required before the API key will work. If you receive a <code class="mono text-xs text-[#e5e5e5]">403 EMAIL_NOT_VERIFIED</code>, check your inbox.</p>

            <div x-data="codeBlock()" class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-[#1f2937]">
                    <div class="flex gap-1">
                        @foreach(['curl','python','js'] as $lang)
                        <button @click="tab='{{ $lang }}'" :class="tab==='{{ $lang }}' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                            class="mono text-xs px-3 py-1 rounded-lg transition-colors">{{ $lang }}</button>
                        @endforeach
                    </div>
                    <button @click="copy(tab)" :class="copied ? 'text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'" class="mono text-xs transition-colors" x-text="copied ? 'Copied!' : 'Copy'"></button>
                </div>
                <div class="p-5 bg-[#0a0b10]">
                    <pre x-show="tab==='curl'"   class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'curl &quot;{{ $base }}/api/v1/markets?category=crypto&quot; \\\n  -H &quot;Authorization: Bearer ' + (apiKey || 'YOUR_API_KEY') + '&quot;'"></pre>
                    <pre x-show="tab==='python'" class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'import requests\n\nheaders = {&quot;Authorization&quot;: &quot;Bearer ' + (apiKey || 'YOUR_API_KEY') + '&quot;}\nres = requests.get(&quot;{{ $base }}/api/v1/markets&quot;, params={&quot;category&quot;: &quot;crypto&quot;}, headers=headers)\nprint(res.json())'"></pre>
                    <pre x-show="tab==='js'"     class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'const res = await fetch(\'{{ $base }}/api/v1/markets?category=crypto\', {\n  headers: { \'Authorization\': `Bearer ' + (apiKey || 'YOUR_API_KEY') + '` }\n});\nconsole.log(await res.json());'"></pre>
                </div>
            </div>
        </section>

        {{-- ── Pagination ────────────────────────────────────────────── --}}
        <section id="rate-limits" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-4">Rate Limits & Pagination</h2>

            <h3 class="text-sm font-semibold text-white mb-2">Rate limits</h3>
            <p class="text-[#697d91] text-sm mb-4">Limits reset at midnight UTC. Responses include <code class="mono text-xs text-[#0093fd]">X-RateLimit-Remaining</code> and <code class="mono text-xs text-[#0093fd]">Retry-After</code> headers when a limit is hit.</p>
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden mb-8">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[#1f2937]">
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Tier</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Requests / day</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">History</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Export / Backtest</th>
                    </tr></thead>
                    <tbody class="divide-y divide-[#1f2937]">
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-[#e5e5e5] font-medium">Free</td><td class="px-5 py-3 text-[#697d91] mono text-xs">100</td><td class="px-5 py-3 text-[#697d91]">7 days</td><td class="px-5 py-3 text-[#2e3841]">—</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-[#0093fd] font-medium">Builder</td><td class="px-5 py-3 text-[#697d91] mono text-xs">10,000</td><td class="px-5 py-3 text-[#697d91]">90 days</td><td class="px-5 py-3 text-[#2e3841]">—</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-purple-300 font-medium">Pro</td><td class="px-5 py-3 text-[#697d91] mono text-xs">100,000</td><td class="px-5 py-3 text-[#697d91]">Unlimited</td><td class="px-5 py-3 text-[#26a05e] text-xs">CSV · SQLite · Backtest</td></tr>
                    </tbody>
                </table>
            </div>

            <h3 class="text-sm font-semibold text-white mb-2">Cursor pagination</h3>
            <p class="text-[#697d91] text-sm mb-4">All list endpoints use cursor-based pagination. Pass the <code class="mono text-xs text-[#0093fd]">next_cursor</code> value from the previous response as the <code class="mono text-xs text-[#0093fd]">cursor</code> query parameter to get the next page. When <code class="mono text-xs text-[#0093fd]">has_more</code> is <code class="mono text-xs text-[#e5e5e5]">false</code>, you've reached the end.</p>
            <div class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-4 mono text-xs leading-6">
<span class="text-[#697d91]">{
  </span><span class="text-[#9cdcfe]">"data"</span><span class="text-[#697d91]">: [...],
  </span><span class="text-[#9cdcfe]">"next_cursor"</span><span class="text-[#697d91]">: </span><span class="text-[#ce9178]">"eyJpZCI6ImJ0Yy11cGRvd24ifQ=="</span><span class="text-[#697d91]">,
  </span><span class="text-[#9cdcfe]">"has_more"</span><span class="text-[#697d91]">: </span><span class="text-[#569cd6]">true</span><span class="text-[#697d91]">
}</span>
            </div>
        </section>

        {{-- ── Errors ────────────────────────────────────────────────── --}}
        <section id="errors" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-2">Errors</h2>
            <p class="text-[#697d91] text-sm mb-5">All errors use the same JSON envelope:</p>
            <div class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-4 mono text-xs leading-6 mb-5">
<span class="text-[#697d91]">{
  </span><span class="text-[#9cdcfe]">"error"</span><span class="text-[#697d91]">: </span><span class="text-[#ce9178]">"Rate limit exceeded."</span><span class="text-[#697d91]">,
  </span><span class="text-[#9cdcfe]">"code"</span><span class="text-[#697d91]">: </span><span class="text-[#ce9178]">"RATE_LIMIT_EXCEEDED"</span><span class="text-[#697d91]">
}</span>
            </div>
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead><tr class="border-b border-[#1f2937]">
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">HTTP</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">code</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Meaning</th>
                    </tr></thead>
                    <tbody class="divide-y divide-[#1f2937] text-xs">
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">401</td><td class="px-5 py-3 text-[#e5e5e5] mono">UNAUTHENTICATED</td><td class="px-5 py-3 text-[#697d91]">Missing or invalid API key</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">403</td><td class="px-5 py-3 text-[#e5e5e5] mono">FORBIDDEN</td><td class="px-5 py-3 text-[#697d91]">Tier does not include this endpoint (Pro required)</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">403</td><td class="px-5 py-3 text-[#e5e5e5] mono">EMAIL_NOT_VERIFIED</td><td class="px-5 py-3 text-[#697d91]">Email address not yet verified</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-amber-400 mono font-bold">422</td><td class="px-5 py-3 text-[#e5e5e5] mono">VALIDATION_ERROR</td><td class="px-5 py-3 text-[#697d91]">Invalid or missing required parameters</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-amber-400 mono font-bold">429</td><td class="px-5 py-3 text-[#e5e5e5] mono">RATE_LIMIT_EXCEEDED</td><td class="px-5 py-3 text-[#697d91]">Daily quota exhausted — check Retry-After header</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">404</td><td class="px-5 py-3 text-[#e5e5e5] mono">NOT_FOUND</td><td class="px-5 py-3 text-[#697d91]">Resource does not exist</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── Endpoint Sections ─────────────────────────────────────── --}}
        @foreach($sections as $section)
        <section id="{{ $section['id'] }}" class="scroll-mt-20">
            <div class="flex items-center gap-3 mb-2">
                <h2 class="text-xl font-bold text-white">{{ $section['label'] }}</h2>
                @if($section['pro'])
                    <span class="text-xs font-semibold text-purple-300 bg-purple-500/10 px-2.5 py-1 rounded-full border border-purple-500/20">Pro only</span>
                @endif
                @php
                $groupColors = [
                    'discovery' => ['border'=>'#0093fd', 'bg'=>'#0093fd', 'text'=>'#0093fd'],
                    'markets'   => ['border'=>'#0093fd', 'bg'=>'#0093fd', 'text'=>'#0093fd'],
                    'crypto'    => ['border'=>'#0093fd', 'bg'=>'#0093fd', 'text'=>'#0093fd'],
                    'weather'   => ['border'=>'#26a05e', 'bg'=>'#26a05e', 'text'=>'#26a05e'],
                    'clob'      => ['border'=>'#a78bfa', 'bg'=>'#a78bfa', 'text'=>'#a78bfa'],
                    'features'  => ['border'=>'#f97316', 'bg'=>'#f97316', 'text'=>'#f97316'],
                    'pro'       => ['border'=>'#a855f7', 'bg'=>'#a855f7', 'text'=>'#a855f7'],
                ];
                $gc = $groupColors[$section['group']] ?? $groupColors['markets'];
                $groupLabel = $sidebarGroups[$section['group']];
                @endphp
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                      style="color:{{ $gc['text'] }};border-color:{{ $gc['border'] }}33;background:{{ $gc['bg'] }}11">
                    {{ $groupLabel }}
                </span>
            </div>
            <p class="text-[#697d91] text-sm mb-6">{{ $section['desc'] }}</p>

            <div class="space-y-4">
            @foreach($section['endpoints'] as $ep)
            {{-- Each endpoint is a self-contained Alpine component --}}
            <div x-data="endpt({{ Js::from($ep) }}, {{ Js::from($base) }})"
                 class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden hover:border-[#2e3841] transition-colors">

                {{-- Header (click to expand) --}}
                <div class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none" @click="open = !open">
                    <span class="mono text-[11px] font-bold px-2 py-0.5 rounded-md flex-shrink-0"
                          :class="cfg.method === 'GET' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'bg-orange-500/15 text-orange-300'"
                          x-text="cfg.method"></span>
                    <code class="mono text-sm text-[#e5e5e5] flex-1 min-w-0 truncate" x-text="cfg.path"></code>
                    @if($section['pro'])
                        <span class="text-[10px] font-semibold text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded-full border border-purple-500/20 flex-shrink-0 hidden sm:block">Pro</span>
                    @endif
                    <span class="text-sm text-[#697d91] hidden md:block flex-shrink-0">{{ $ep['title'] }}</span>
                    <svg class="w-4 h-4 text-[#697d91] transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>

                {{-- Body --}}
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     class="border-t border-[#1f2937] p-5 space-y-5">

                    <p class="text-sm text-[#697d91]">{{ $ep['desc'] }}</p>

                    {{-- Parameters --}}
                    @if(count($ep['params']) > 0)
                    <div>
                        <p class="text-xs font-semibold text-[#697d91] uppercase tracking-widest mb-3">Parameters</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($ep['params'] as $p)
                            <div>
                                <label class="block text-xs text-[#697d91] mb-1 mono">
                                    {{ $p['name'] }}@if(!empty($p['required'])) <span class="text-red-400">*</span>@endif
                                    @if(!empty($p['pathParam'])) <span class="text-[#2e3841]">(path)</span>@endif
                                </label>
                                @if(isset($p['options']))
                                <select x-model="vals.{{ $p['name'] }}"
                                    class="w-full bg-[#0a0b10] border border-[#1f2937] focus:border-[#0093fd] rounded-lg px-3 py-2 text-sm text-[#e5e5e5] outline-none transition-colors mono">
                                    @foreach($p['options'] as $opt)
                                    <option value="{{ $opt }}">{{ $opt ?: '— any —' }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="{{ $p['type'] ?? 'text' }}"
                                    x-model="vals.{{ $p['name'] }}"
                                    placeholder="{{ $p['placeholder'] ?? '' }}"
                                    class="w-full bg-[#0a0b10] border border-[#1f2937] focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/20 rounded-lg px-3 py-2 text-sm text-[#e5e5e5] placeholder-[#2e3841] outline-none transition-colors mono" />
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Code tabs --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex gap-1">
                                @foreach(['curl','python','js'] as $lang)
                                <button @click="tab='{{ $lang }}'" :class="tab==='{{ $lang }}' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                                    class="mono text-xs px-3 py-1 rounded-lg border border-transparent transition-colors">{{ $lang }}</button>
                                @endforeach
                            </div>
                            <button @click="copy()" :class="copiedCode ? 'text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                                class="mono text-xs transition-colors" x-text="copiedCode ? 'Copied!' : 'Copy'"></button>
                        </div>
                        <div class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-4 overflow-x-auto">
                            <pre class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-text="codeExample()"></pre>
                        </div>
                    </div>

                    {{-- Send button --}}
                    <div class="flex items-center gap-3">
                        <button @click="run()" :disabled="loading"
                            class="flex items-center gap-2 bg-[#0093fd] hover:bg-[#0080e0] disabled:opacity-50 text-white font-semibold text-sm px-5 py-2 rounded-xl transition-colors">
                            <span x-show="!loading">Send request</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                Sending…
                            </span>
                        </button>
                        <span x-show="elapsed && !loading" class="text-xs text-[#697d91] mono" x-text="elapsed + 'ms'"></span>
                        <span x-show="!apiKey && !loading" class="text-xs text-amber-400/80">← paste your API key in the nav to test live</span>
                    </div>

                    {{-- Response / Example --}}
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <p class="text-xs font-semibold text-[#697d91] uppercase tracking-widest" x-text="response !== null ? 'Response' : 'Example Response'"></p>
                            <span x-show="status !== null" class="mono text-xs font-bold px-2 py-0.5 rounded-md"
                                :class="status >= 200 && status < 300 ? 'bg-[#26a05e]/15 text-[#26a05e]' : 'bg-red-500/15 text-red-400'"
                                x-text="'HTTP ' + status"></span>
                        </div>
                        <div class="bg-[#0a0b10] rounded-xl overflow-hidden border"
                             :class="status !== null ? (status >= 200 && status < 300 ? 'border-[#26a05e]/20' : 'border-red-500/20') : 'border-[#1f2937]'">
                            <pre class="p-4 mono text-xs text-[#697d91] overflow-x-auto max-h-72 leading-6 whitespace-pre-wrap"
                                 x-text="response !== null ? response : cfg.example"></pre>
                        </div>
                    </div>

                </div>
            </div>
            @endforeach
            </div>
        </section>
        @endforeach

        {{-- ── Footer spacer ─────────────────────────────────────────── --}}
        <div class="border-t border-[#1f2937] pt-10 pb-4 flex items-center justify-between text-xs text-[#2e3841]">
            <span>Polymarket Data API — independent third-party service</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="hover:text-[#697d91] transition-colors">Home</a>
                <a href="{{ route('terms') }}" class="hover:text-[#697d91] transition-colors">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-[#697d91] transition-colors">Privacy</a>
            </div>
        </div>

        </div>{{-- /content --}}
    </main>
</div>

<script>
function docsApp() {
    return {
        apiKey: '',
        init() {
            this.apiKey = localStorage.getItem('pm_api_key') || '';
            // Sidebar active link on scroll
            const links = document.querySelectorAll('.nav-link');
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        links.forEach(l => l.classList.remove('active'));
                        const match = document.querySelector(`.nav-link[href="#${e.target.id}"]`);
                        if (match) match.classList.add('active');
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            document.querySelectorAll('section[id]').forEach(s => obs.observe(s));
        },
        saveKey() {
            localStorage.setItem('pm_api_key', this.apiKey);
        },
    }
}

function codeBlock() {
    return {
        tab: 'curl',
        copied: false,
        copy(tab) {
            const pre = this.$el.querySelectorAll('pre');
            const idx = ['curl','python','js'].indexOf(tab);
            navigator.clipboard.writeText(pre[idx]?.innerText || '');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
    }
}

function endpt(cfg, base) {
    // Build initial vals from params
    const vals = {};
    (cfg.params || []).forEach(p => vals[p.name] = p.options ? (p.options[0] || '') : '');

    return {
        cfg, base,
        vals,
        tab: 'curl',
        open: false,
        loading: false,
        response: null,
        status: null,
        elapsed: null,
        copiedCode: false,

        get apiKey() {
            return document.querySelector('[x-model="apiKey"]')?.value || '';
        },

        buildUrl() {
            let url = base + cfg.path;
            const qs = new URLSearchParams();
            (cfg.params || []).forEach(p => {
                const v = this.vals[p.name];
                if (url.includes('{' + p.name + '}')) {
                    url = url.replace('{' + p.name + '}', v ? encodeURIComponent(v) : ('<' + p.name + '>'));
                } else if (v) {
                    qs.append(p.name, v);
                }
            });
            return url + (qs.toString() ? '?' + qs.toString() : '');
        },

        codeExample() {
            const k = this.apiKey || 'YOUR_API_KEY';
            const url = this.buildUrl();
            if (this.tab === 'curl') {
                const method = cfg.method === 'POST' ? " \\\n  -X POST" : '';
                return `curl "${url}"${method} \\\n  -H "Authorization: Bearer ${k}"`;
            }
            if (this.tab === 'python') {
                const qp = (cfg.params||[]).filter(p => !cfg.path.includes('{'+p.name+'}') && this.vals[p.name]);
                const ps = qp.length ? `params = {${qp.map(p => `"${p.name}": "${this.vals[p.name]}"`).join(', ')}}\n` : '';
                return `import requests\n\nheaders = {"Authorization": "Bearer ${k}"}\n${ps}res = requests.${cfg.method.toLowerCase()}("${base + cfg.path.replace(/\{[^}]+\}/g, m => { const n=m.slice(1,-1); return this.vals[n]||m; })}"${ps?', params=params':''}, headers=headers)\nprint(res.json())`;
            }
            // js
            return `const res = await fetch('${url}', {\n  headers: { 'Authorization': \`Bearer ${k}\` }\n});\nconsole.log(await res.json());`;
        },

        copy() {
            navigator.clipboard.writeText(this.codeExample());
            this.copiedCode = true;
            setTimeout(() => this.copiedCode = false, 2000);
        },

        async run() {
            const k = this.apiKey;
            if (!k) { alert('Paste your API key in the nav bar first.'); return; }
            this.loading = true; this.response = null; this.status = null;
            const t0 = performance.now();
            try {
                const res = await fetch(this.buildUrl(), {
                    method: cfg.method,
                    headers: { 'Authorization': 'Bearer ' + k, 'Accept': 'application/json' },
                });
                this.elapsed = Math.round(performance.now() - t0);
                this.status = res.status;
                const ct = res.headers.get('content-type') || '';
                this.response = ct.includes('json')
                    ? JSON.stringify(await res.json(), null, 2)
                    : await res.text();
            } catch(e) {
                this.status = 0;
                this.response = 'Network error: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>

</body>
</html>
