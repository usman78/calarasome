<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderBlockedTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderBlockedTime>
 */
class ProviderBlockedTimeFactory extends Factory
{
    protected $model = ProviderBlockedTime::class;

    public function definition(): array
    {
        $start = now()->addDays(2)->setHour(12)->setMinute(0)->setSecond(0);

        return [
            'provider_id' => Provider::factory(),
            'start_datetime' => $start,
            'end_datetime' => (clone $start)->addHours(2),
            'reason' => 'Vacation',
        ];
    }
}
