<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('active', 1)],
            'billing_period_id' => ['required', 'exists:billing_periods,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_now' => ['boolean'],
        ];
    }
}
