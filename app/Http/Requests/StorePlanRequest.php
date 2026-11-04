<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlanRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'active' => ['sometimes', 'boolean'],
            'windows' => ['required', 'array', 'min:1'],
            'windows.*.starts_at' => ['required', 'date_format:H:i'],
            'windows.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('windows') as $index => $window) {
                $errors = $validator->errors();

                if ($errors->has("windows.{$index}.starts_at") || $errors->has("windows.{$index}.ends_at")) {
                    continue;
                }

                if ($window['ends_at'] <= $window['starts_at']) {
                    $errors->add("windows.{$index}.ends_at", 'A hora de fim tem de ser depois da hora de início.');
                }
            }
        }];
    }
}
