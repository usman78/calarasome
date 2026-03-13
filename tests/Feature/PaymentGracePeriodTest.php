<?php

use App\Mail\PaymentGracePeriodAdminEmail;
use App\Mail\PaymentGracePeriodPatientEmail;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Services\AppointmentPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('starts grace period and sends notifications on failed t7 auth', function () {
    Mail::fake();
    config(['services.payment_alerts.admin_email' => 'admin@example.com']);

    $clinic = Clinic::factory()->create(['timezone' => 'America/New_York']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'patient@example.com']);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDays(10),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'setup_intent',
        'status' => 'pending_setup',
        'stripe_payment_intent_id' => 'pi_fail_123',
        'grace_started_at' => null,
        'grace_expires_at' => null,
    ]);

    $service = app(AppointmentPaymentService::class);
    $service->recordPaymentIntentStatus('pi_fail_123', 'failed');

    $payment->refresh();
    expect($payment->grace_started_at)->not->toBeNull();
    expect($payment->grace_expires_at)->not->toBeNull();
    expect($payment->status)->toBe('failed');

    Mail::assertSent(PaymentGracePeriodPatientEmail::class);
    Mail::assertSent(PaymentGracePeriodAdminEmail::class);
});

it('cancels appointments when grace period expires', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'setup_intent',
        'status' => 'failed',
        'grace_started_at' => now()->subHours(50),
        'grace_expires_at' => now()->subHour(),
    ]);

    Artisan::call('payments:cancel-expired-grace');

    $appointment->refresh();
    $payment->refresh();

    expect($appointment->status)->toBe('cancelled_by_clinic');
    expect($payment->status)->toBe('grace_expired');
});
