<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentType>
 */
class AppointmentTypeFactory extends Factory
{
    protected $model = AppointmentType::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'name' => fake()->randomElement(['Consultation', 'Acne Follow-up', 'Laser Session']),
            'duration_minutes' => fake()->randomElement([15, 20, 30]),
            'is_active' => true,
            'is_medical' => false,
            'deposit_amount_cents' => 0,
            'deposit_currency' => 'usd',
        ];
    }
}
