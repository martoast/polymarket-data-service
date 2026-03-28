<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OracleTickResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'asset'     => $this->asset->symbol,
            'price_usd' => $this->price_usd,
            'price_bp'  => $this->price_bp,
            'ts'        => $this->ts,
        ];
    }
}
