<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkNoShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('chargeDeposit') && ! $this->has('charge_deposit')) {
            $this->merge([
                'charge_deposit' => $this->input('chargeDeposit'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'charge_deposit' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
