<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OracleStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'ts',
        'bucket_sec',
        'high_bp',
        'low_bp',
        'open_bp',
        'close_bp',
        'tick_count',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
