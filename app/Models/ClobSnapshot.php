<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClobSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'market_id', 'asset_id',
        'yes_ask', 'yes_bid', 'no_ask', 'no_bid', 'ts',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
