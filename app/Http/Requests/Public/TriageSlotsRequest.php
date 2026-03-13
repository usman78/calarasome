<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class TriageSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'appointment_type_id' => ['required', 'integer', 'exists:appointment_types,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
