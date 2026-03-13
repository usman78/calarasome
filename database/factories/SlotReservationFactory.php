<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\SlotReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SlotReservation>
 */
class SlotReservationFactory extends Factory
{
    protected $model = SlotReservation::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'provider_id' => Provider::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'slot_datetime' => now()->addDay()->setMinute(0)->setSecond(0),
            'session_token' => Str::random(64),
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'converted_to_appointment_id' => null,
            'released_at' => null,
        ];
    }
}
