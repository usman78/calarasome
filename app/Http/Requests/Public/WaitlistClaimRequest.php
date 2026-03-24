<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class WaitlistClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_of_birth' => ['required', 'date'],
        ];
    }
}
