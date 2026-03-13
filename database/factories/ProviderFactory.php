<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'full_name' => fake()->name(),
            'title' => 'Dr.',
            'specialization' => fake()->randomElement(['Dermatology', 'Cosmetic Dermatology']),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'default_appointment_types' => [],
            'booking_buffer_minutes' => 0,
            'is_active' => true,
            'is_accepting_new_patients' => true,
            'display_order' => 0,
        ];
    }
}
