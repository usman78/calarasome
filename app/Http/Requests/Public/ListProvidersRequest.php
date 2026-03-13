<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class ListProvidersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_type_id' => ['required', 'integer', 'exists:appointment_types,id'],
            'is_new_patient' => ['nullable', 'boolean'],
        ];
    }
}
