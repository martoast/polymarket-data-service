<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClobSnapshotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['sometimes', 'string'],
            'asset'     => ['sometimes', 'string'],
            'from'      => ['sometimes', 'integer'],
            'to'        => ['sometimes', 'integer'],
            'cursor'    => ['sometimes', 'string'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $hasMarketId = $this->filled('market_id');
            $hasAsset    = $this->filled('asset');
            $hasFrom     = $this->filled('from');
            $hasTo       = $this->filled('to');

            if (! $hasMarketId && ! ($hasAsset && $hasFrom && $hasTo)) {
                $v->errors()->add(
                    'market_id',
                    'Either market_id or all of asset, from, and to are required.'
                );
            }
        });
    }
}
