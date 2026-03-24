<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    protected $model = WaitlistEntry::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'provider_id' => Provider::factory(),
            'preferred_datetime' => now()->addDays(7),
            'triage_data' => [
                'urgency_flag' => false,
            ],
            'priority_score' => 0,
            'tier' => 'standard',
            'status' => 'active',
        ];
    }
}
