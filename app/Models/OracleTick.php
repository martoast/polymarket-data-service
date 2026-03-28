<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OracleTick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'price_usd',
        'price_bp',
        'ts',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
