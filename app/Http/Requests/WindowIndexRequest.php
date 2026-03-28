<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WindowIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset'        => ['sometimes', 'string'],
            'duration'     => ['sometimes', 'integer', 'in:300,900'],
            'outcome'      => ['sometimes', 'string', 'in:YES,NO'],
            'has_coverage' => ['sometimes', 'boolean'],
            'from'         => ['sometimes', 'integer', 'min:0'],
            'to'           => ['sometimes', 'integer', 'min:0'],
            'cursor'       => ['sometimes', 'string'],
            'per_page'     => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
