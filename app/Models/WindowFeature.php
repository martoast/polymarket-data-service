<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WindowFeature extends Model
{
    protected $table = 'window_features';

    protected $primaryKey = 'window_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'window_id',
        'asset',
        'duration_sec',
        'open_ts',
        'close_ts',
        'outcome',
        'oracle_dist_bp_at_5m',
        'oracle_dist_bp_at_4m',
        'oracle_dist_bp_at_3m',
        'oracle_dist_bp_at_2m',
        'oracle_dist_bp_at_90s',
        'oracle_dist_bp_at_1m',
        'oracle_dist_bp_at_45s',
        'oracle_dist_bp_at_30s',
        'oracle_dist_bp_at_15s',
        'oracle_dist_bp_final',
        'oracle_range_5m_bp',
        'oracle_range_10m_bp',
        'oracle_range_15m_bp',
        'oracle_range_5m_at_3m',
        'oracle_range_5m_at_2m',
        'oracle_trend_5m_bp',
        'oracle_trend_10m_bp',
        'oracle_tick_count',
        'oracle_tick_gap_max_ms',
        'oracle_crossings_total',
        'oracle_crossings_last_5m',
        'oracle_crossings_last_2m',
        'oracle_committed_since_ms',
        'clob_yes_ask_final',
        'clob_yes_ask_min_5m',
        'clob_yes_ask_max_5m',
        'clob_yes_ask_avg_5m',
        'clob_spread_final',
        'clob_snapshot_count',
        'clob_in_lock_range',
        'oracle_range_30m_prior_bp',
        'oracle_trend_30m_prior_bp',
        'oracle_crossings_30m_prior',
        'hour_utc',
        'day_of_week',
        'has_full_oracle_coverage',
        'has_clob_coverage',
        'recording_gap',
        'computed_at',
    ];

    public function window(): BelongsTo
    {
        return $this->belongsTo(Window::class, 'window_id');
    }
}
