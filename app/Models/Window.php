<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Window extends Model
{
    protected $table = 'windows';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'asset_id',
        'duration_sec',
        'break_price_usd',
        'break_price_bp',
        'open_ts',
        'close_ts',
        'resolved_ts',
        'outcome',
        'condition_id',
        'gamma_slug',
        'has_oracle_coverage',
        'has_clob_coverage',
        'recording_gap',
    ];

    protected function casts(): array
    {
        return [
            'has_oracle_coverage' => 'boolean',
            'has_clob_coverage'   => 'boolean',
            'recording_gap'       => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function clobSnapshots(): HasMany
    {
        return $this->hasMany(ClobSnapshot::class, 'window_id');
    }

    public function windowFeature(): HasOne
    {
        return $this->hasOne(WindowFeature::class, 'window_id');
    }

    // ---------- Scopes ----------

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('outcome');
    }

    public function scopeWithCoverage(Builder $query): Builder
    {
        return $query->where('has_oracle_coverage', true)
                     ->where('has_clob_coverage', true)
                     ->where('recording_gap', false);
    }

    // ---------- Accessors ----------

    public function getDurationLabelAttribute(): string
    {
        return match ((int) $this->duration_sec) {
            900     => '15m',
            default => '5m',
        };
    }

    // ---------- Static helpers ----------

    /**
     * Build the canonical window slug.
     * e.g. btc-updown-5m-1773136800
     *
     * @param string $asset  e.g. "btc"
     * @param int    $wsMs   window start timestamp in milliseconds
     * @param int    $weMs   window end timestamp in milliseconds
     */
    public static function buildSlug(string $asset, int $wsMs, int $weMs): string
    {
        $durationSec = (int) (($weMs - $wsMs) / 1000);
        $label       = $durationSec >= 900 ? '15m' : '5m';
        $openSec     = (int) ($wsMs / 1000);

        return strtolower($asset) . '-updown-' . $label . '-' . $openSec;
    }
}
