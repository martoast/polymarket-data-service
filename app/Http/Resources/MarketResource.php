<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'category'       => $this->category,
            'asset'          => $this->asset->symbol ?? null,
            'duration_sec'   => $this->duration_sec,
            'duration_label' => $this->duration_label,
            'break_value'    => (float) $this->break_value,
            'value_unit'     => $this->value_unit,
            'open_ts'        => $this->open_ts,
            'close_ts'       => $this->close_ts,
            'outcome'        => $this->outcome,
            'resolved_ts'    => $this->resolved_ts,
            'has_coverage'   => (bool) $this->has_coverage,
        ];
    }
}
