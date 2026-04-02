<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherMarketFeature extends Model
{
    protected $table = 'weather_market_features';

    protected $primaryKey = 'market_id';
    public $incrementing  = false;
    public $keyType       = 'string';
    public $timestamps    = false;

    protected $fillable = [
        'market_id', 'asset', 'station', 'open_ts', 'close_ts', 'outcome',
        'temp_at_open_c', 'break_value_c', 'running_max_at_close_c', 'final_max_c',
        'forecast_at_open_c', 'forecast_deviation_c', 'hours_above_threshold',
        'hour_utc', 'day_of_week', 'season',
        'clob_yes_ask_final', 'clob_spread_final', 'clob_snapshot_count',
        'has_reading_coverage', 'has_clob_coverage', 'computed_at',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'market_id');
    }
}
