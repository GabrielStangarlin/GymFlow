<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'nif' => ['required', 'digits:9', 'unique:clients,nif'],
            'birth_date' => ['required', 'date', 'before:today'],
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('active', 1)],
            'billing_period_id' => ['required', 'exists:billing_periods,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_now' => ['boolean'],
        ];
    }
}
