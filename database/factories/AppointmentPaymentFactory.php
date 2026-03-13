<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentPayment>
 */
class AppointmentPaymentFactory extends Factory
{
    protected $model = AppointmentPayment::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'strategy' => 'setup_intent',
            'status' => 'pending_setup',
            'amount_cents' => 5000,
            'currency' => 'usd',
            'auth_scheduled_for' => now()->subMinute(),
            'stripe_setup_intent_id' => 'seti_test_'.fake()->uuid(),
            'stripe_payment_method_id' => 'pm_test_'.fake()->uuid(),
            'stripe_payment_intent_id' => null,
        ];
    }
}
