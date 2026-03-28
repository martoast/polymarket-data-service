<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAudit extends Model
{
    protected $table = 'sessions_audit';

    public $timestamps = false;

    protected $fillable = [
        'filename',
        'started_at',
        'ended_at',
        'event_count',
        'oracle_count',
        'clob_count',
        'file_size_bytes',
        'ingest_status',
        'ingest_error',
        'ingested_at',
    ];
}
