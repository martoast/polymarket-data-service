<?php

namespace App\Http\Requests;

use App\Models\WindowFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BacktestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conditions'         => ['required', 'array', 'min:1'],
            'conditions.*.field' => ['required', 'string'],
            'conditions.*.op'    => ['required', 'string', 'in:>,<,>=,<=,='],
            'conditions.*.value' => ['required', 'numeric'],
            'asset'              => ['sometimes', 'string'],
            'duration'           => ['sometimes', 'integer', 'in:300,900'],
            'from'               => ['sometimes', 'integer'],
            'to'                 => ['sometimes', 'integer'],
            'quality'            => ['sometimes', 'string', 'in:strict'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $allowedFields = $this->allowedBacktestFields();
            $conditions    = $this->input('conditions', []);

            foreach ($conditions as $i => $condition) {
                $field = $condition['field'] ?? null;
                if ($field !== null && ! in_array($field, $allowedFields, true)) {
                    $v->errors()->add(
                        "conditions.{$i}.field",
                        "Field '{$field}' is not allowed for backtest conditions."
                    );
                }
            }
        });
    }

    public static function allowedBacktestFields(): array
    {
        $exclude = [
            'window_id', 'asset', 'open_ts', 'close_ts', 'outcome',
            'computed_at', 'duration_sec', 'hour_utc', 'day_of_week',
            'has_full_oracle_coverage', 'has_clob_coverage', 'recording_gap',
        ];

        return array_values(array_diff((new WindowFeature())->getFillable(), $exclude));
    }
}
