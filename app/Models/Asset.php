<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = ['category_id', 'symbol', 'name', 'unit', 'source_config', 'is_active'];

    protected $casts = [
        'source_config' => 'array',
        'is_active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }

    public function oracleTicks(): HasMany
    {
        return $this->hasMany(OracleTick::class);
    }

    public function clobSnapshots(): HasMany
    {
        return $this->hasMany(ClobSnapshot::class);
    }

    public function candles(): HasMany
    {
        return $this->hasMany(Candle1m::class);
    }

    public function weatherReadings(): HasMany
    {
        return $this->hasMany(WeatherReading::class);
    }
}
