<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'provider_id' => Provider::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'patient_id' => Patient::factory(),
            'slot_datetime' => now()->addDay(),
            'status' => 'confirmed',
            'triage_data' => null,
        ];
    }
}
