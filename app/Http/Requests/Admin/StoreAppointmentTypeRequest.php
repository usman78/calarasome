<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['required', 'exists:clinics,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'providerIds' => ['nullable', 'array'],
            'providerIds.*' => [
                'integer',
                'distinct',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('clinic_id', (int) $this->integer('clinic_id'))),
            ],
        ];
    }
}
