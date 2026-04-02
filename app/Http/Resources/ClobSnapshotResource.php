<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClobSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'market_id' => $this->market_id,
            'asset'     => $this->asset->symbol ?? null,
            'yes_ask'   => $this->yes_ask,
            'yes_bid'   => $this->yes_bid,
            'no_ask'    => $this->no_ask,
            'no_bid'    => $this->no_bid,
            'ts'        => $this->ts,
        ];
    }
}
