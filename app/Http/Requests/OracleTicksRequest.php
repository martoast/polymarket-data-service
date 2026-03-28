<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OracleTicksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset'    => ['required', 'string', 'exists:assets,symbol'],
            'from'     => ['sometimes', 'integer', 'min:0'],
            'to'       => ['sometimes', 'integer', 'min:0'],
            'cursor'   => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
