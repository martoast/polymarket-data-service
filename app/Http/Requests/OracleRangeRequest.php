<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OracleRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset' => ['required', 'string'],
            'from'  => ['required', 'integer', 'min:0'],
            'to'    => ['required', 'integer', 'min:0'],
        ];
    }
}
