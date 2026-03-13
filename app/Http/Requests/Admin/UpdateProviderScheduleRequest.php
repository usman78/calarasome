<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProviderScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i:s'],
            'schedules.*.end_time' => ['required', 'date_format:H:i:s'],
            'schedules.*.appointment_type_ids' => ['nullable', 'array'],
            'schedules.*.appointment_type_ids.*' => ['integer', 'exists:appointment_types,id'],
            'schedules.*.effective_from' => ['nullable', 'date'],
            'schedules.*.effective_until' => ['nullable', 'date', 'after_or_equal:schedules.*.effective_from'],
            'schedules.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schedules = $this->input('schedules', []);

            foreach ($schedules as $idx => $schedule) {
                if (($schedule['end_time'] ?? '') <= ($schedule['start_time'] ?? '')) {
                    $validator->errors()->add("schedules.{$idx}.end_time", 'End time must be after start time.');
                }
            }

            for ($i = 0; $i < count($schedules); $i++) {
                for ($j = $i + 1; $j < count($schedules); $j++) {
                    $a = $schedules[$i];
                    $b = $schedules[$j];

                    if (($a['day_of_week'] ?? null) !== ($b['day_of_week'] ?? null)) {
                        continue;
                    }

                    $overlap = ($a['start_time'] < $b['end_time']) && ($b['start_time'] < $a['end_time']);
                    if (! $overlap) {
                        continue;
                    }

                    if (! $this->effectiveRangesOverlap($a, $b)) {
                        continue;
                    }

                    $validator->errors()->add('schedules', 'Overlapping schedule windows are not allowed for the same provider/day.');
                }
            }
        });
    }

    private function effectiveRangesOverlap(array $a, array $b): bool
    {
        $aFrom = $a['effective_from'] ?? '0001-01-01';
        $aUntil = $a['effective_until'] ?? '9999-12-31';
        $bFrom = $b['effective_from'] ?? '0001-01-01';
        $bUntil = $b['effective_until'] ?? '9999-12-31';

        return $aFrom <= $bUntil && $bFrom <= $aUntil;
    }
}
