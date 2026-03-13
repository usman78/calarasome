<?php

namespace App\Http\Requests\Admin;

use App\Models\AppointmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var AppointmentType|null $appointmentType */
        $appointmentType = $this->route('appointmentType');
        $clinicId = (int) ($appointmentType?->clinic_id ?? 0);

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
            'providerIds' => ['nullable', 'array'],
            'providerIds.*' => [
                'integer',
                'distinct',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('clinic_id', $clinicId)),
            ],
        ];
    }
}
