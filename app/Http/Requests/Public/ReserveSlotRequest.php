<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class ReserveSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['required'],
            'appointment_type_id' => ['required', 'integer', 'exists:appointment_types,id'],
            'slot_local_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
