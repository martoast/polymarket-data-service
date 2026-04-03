<?php

namespace App\Http\Requests\Weather;

use Illuminate\Foundation\Http\FormRequest;

class ReadingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset'    => ['sometimes', 'string', 'exists:assets,symbol'],
            'date'     => ['sometimes', 'date_format:Y-m-d'],   // local station date
            'from'     => ['sometimes', 'integer', 'min:0'],
            'to'       => ['sometimes', 'integer', 'min:0'],
            'cursor'   => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
