<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClobSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'window_id',
        'asset_id',
        'yes_ask',
        'yes_bid',
        'no_ask',
        'no_bid',
        'ts',
    ];

    public function window(): BelongsTo
    {
        return $this->belongsTo(Window::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
