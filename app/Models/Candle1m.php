<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candle1m extends Model
{
    protected $table = 'candles_1m';

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'open_usd',
        'high_usd',
        'low_usd',
        'close_usd',
        'volume',
        'ts',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
