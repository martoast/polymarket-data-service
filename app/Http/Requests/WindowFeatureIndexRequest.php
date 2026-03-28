<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WindowFeatureIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset'    => ['sometimes', 'string'],
            'duration' => ['sometimes', 'integer', 'in:300,900'],
            'outcome'  => ['sometimes', 'string', 'in:YES,NO'],
            'quality'  => ['sometimes', 'string', 'in:strict'],
            'from'     => ['sometimes', 'integer'],
            'to'       => ['sometimes', 'integer'],
            'cursor'   => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'format'   => ['sometimes', 'string', 'in:json,csv'],
            'columns'  => ['sometimes', 'array'],
            'columns.*' => ['string'],
        ];
    }
}
