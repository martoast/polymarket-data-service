<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Market extends Model
{
    public $incrementing = false;
    public $keyType      = 'string';
    public $timestamps   = false;

    protected $fillable = [
        'id', 'category', 'asset_id', 'duration_sec', 'duration_label',
        'break_value', 'value_unit', 'open_ts', 'close_ts', 'resolved_ts',
        'outcome', 'condition_id', 'gamma_slug', 'yes_token_id', 'no_token_id',
        'has_coverage', 'recording_gap',
    ];

    protected $casts = [
        'has_coverage'  => 'boolean',
        'recording_gap' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function clobSnapshots(): HasMany
    {
        return $this->hasMany(ClobSnapshot::class, 'market_id');
    }

    public function cryptoFeatures(): HasOne
    {
        return $this->hasOne(CryptoMarketFeature::class, 'market_id');
    }

    public function weatherFeatures(): HasOne
    {
        return $this->hasOne(WeatherMarketFeature::class, 'market_id');
    }

    // Scopes
    public function scopeResolved($query)
    {
        return $query->whereNotNull('outcome');
    }

    public function scopeActive($query)
    {
        $nowMs = (int) (microtime(true) * 1000);
        return $query->whereNull('outcome')->where('close_ts', '>', $nowMs);
    }

    public function scopeWithCoverage($query)
    {
        return $query->where('has_coverage', true)->where('recording_gap', false);
    }
}
