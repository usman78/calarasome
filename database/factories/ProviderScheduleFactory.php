<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderSchedule>
 */
class ProviderScheduleFactory extends Factory
{
    protected $model = ProviderSchedule::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'provider_id' => Provider::factory(),
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'appointment_type_ids' => null,
            'effective_from' => null,
            'effective_until' => null,
            'is_active' => true,
        ];
    }
}
