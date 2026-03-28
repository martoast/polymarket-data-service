<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WindowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'asset'          => $this->asset->symbol ?? null,
            'duration_sec'   => $this->duration_sec,
            'duration_label' => $this->duration_label,
            'break_price_usd' => $this->break_price_usd,
            'open_ts'        => $this->open_ts,
            'close_ts'       => $this->close_ts,
            'outcome'        => $this->outcome,
            'resolved_ts'    => $this->resolved_ts,
            'has_coverage'   => $this->has_oracle_coverage && $this->has_clob_coverage && ! $this->recording_gap,
        ];
    }
}
