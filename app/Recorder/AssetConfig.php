<?php

namespace App\Recorder;

class AssetConfig
{
    /**
     * Configured assets: symbol → slug prefix + chainlink channel symbol + question keyword.
     * Add a new entry here to record a new asset — nothing else needs changing.
     */
    public const ASSETS = [
        'BTC' => [
            'slug_prefix'       => 'btc',
            'chainlink_symbol'  => 'btc/usd',
            'question_keyword'  => 'Bitcoin',
        ],
        'ETH' => [
            'slug_prefix'       => 'eth',
            'chainlink_symbol'  => 'eth/usd',
            'question_keyword'  => 'Ethereum',
        ],
        'SOL' => [
            'slug_prefix'       => 'sol',
            'chainlink_symbol'  => 'sol/usd',
            'question_keyword'  => 'Solana',
        ],
    ];

    /** Window durations (seconds) to record for each asset. */
    public const DURATIONS = [300, 900]; // 5m, 15m

    /** Return the list of enabled asset symbols (from RECORDED_ASSETS env, default: all). */
    public static function enabledAssets(): array
    {
        $env = env('RECORDED_ASSETS');
        if (!$env) {
            return array_keys(self::ASSETS);
        }
        return array_filter(
            array_map('trim', explode(',', strtoupper($env))),
            fn ($s) => isset(self::ASSETS[$s])
        );
    }

    /** Return all Chainlink subscription symbols for enabled assets. */
    public static function chainlinkSymbols(): array
    {
        return array_map(
            fn ($asset) => self::ASSETS[$asset]['chainlink_symbol'],
            self::enabledAssets()
        );
    }

    /** Resolve asset symbol from a chainlink symbol string (e.g. "btc/usd" → "BTC"). */
    public static function assetFromChainlinkSymbol(string $symbol): ?string
    {
        foreach (self::enabledAssets() as $asset) {
            if (self::ASSETS[$asset]['chainlink_symbol'] === strtolower($symbol)) {
                return $asset;
            }
        }
        return null;
    }
}
