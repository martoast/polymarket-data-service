<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherReading extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id', 'temp_c', 'temp_f', 'running_daily_max_c',
        'source', 'station_local_date', 'ts',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
