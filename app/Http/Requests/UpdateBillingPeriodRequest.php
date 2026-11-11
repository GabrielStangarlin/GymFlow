<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingPeriodRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'discounts' => ['required', 'array'],
            'discounts.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
