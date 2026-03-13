<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppointmentAccessToken>
 */
class AppointmentAccessTokenFactory extends Factory
{
    protected $model = AppointmentAccessToken::class;

    public function definition(): array
    {
        $rawToken = Str::random(64);

        return [
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'email' => fake()->safeEmail(),
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addHours(24),
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_sent_at' => now(),
        ];
    }
}
