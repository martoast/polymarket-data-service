<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'symbol',
        'chain',
        'oracle_addr',
    ];

    public function windows(): HasMany
    {
        return $this->hasMany(Window::class);
    }

    public function oracleTicks(): HasMany
    {
        return $this->hasMany(OracleTick::class);
    }

    public function clobSnapshots(): HasMany
    {
        return $this->hasMany(ClobSnapshot::class);
    }

    public function candle1ms(): HasMany
    {
        return $this->hasMany(Candle1m::class);
    }

    public function oracleStats(): HasMany
    {
        return $this->hasMany(OracleStat::class);
    }
}
