<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'size:64'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'email_consent' => ['required', 'accepted'],
            'email_phi' => ['required', 'boolean'],
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'insurance_member_id' => ['nullable', 'string', 'max:255'],
            'insurance_group_id' => ['nullable', 'string', 'max:255'],
            'insurance_plan' => ['nullable', 'string', 'max:255'],
            'insurance_subscriber_name' => ['nullable', 'string', 'max:255'],
            'insurance_subscriber_dob' => ['nullable', 'date'],
            'insurance_relationship' => ['nullable', 'string', 'max:50'],
            'insurance_phone' => ['nullable', 'string', 'max:255'],
            'insurance_urgency' => ['nullable', 'in:standard,high,critical'],
        ];
    }
}
