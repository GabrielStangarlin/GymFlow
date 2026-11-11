<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->user_id)],
            'phone' => ['required', 'string', 'max:30'],
            'nif' => ['required', 'digits:9', Rule::unique('clients', 'nif')->ignore($client->id)],
            'birth_date' => ['required', 'date', 'before:today'],
        ];
    }
}
