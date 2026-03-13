<?php

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Services\AppointmentPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('skips deposits for medical appointment types', function () {
    Http::fake();
    config(['services.stripe.secret' => 'sk_test_dummy']);

    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'is_medical' => true,
        'deposit_amount_cents' => 5000,
    ]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDays(2),
    ]);

    $service = app(AppointmentPaymentService::class);
    $result = $service->initializePayment($appointment, $appointmentType, $patient);

    expect($result['strategy'])->toBe('skip');
    expect($result['status'])->toBe('skipped');

    $payment = AppointmentPayment::query()->where('appointment_id', $appointment->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment?->strategy)->toBe('skip');
    Http::assertNothingSent();
});

it('creates manual-capture payment intent when appointment is within 7 days', function () {
    config(['services.stripe.secret' => 'sk_test_dummy']);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'payment_intents')) {
            return Http::response([
                'id' => 'pi_test_123',
                'client_secret' => 'pi_secret_123',
                'status' => 'requires_payment_method',
            ], 200);
        }

        return Http::response([], 500);
    });

    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'is_medical' => false,
        'deposit_amount_cents' => 7500,
        'deposit_currency' => 'usd',
    ]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDays(3),
    ]);

    $service = app(AppointmentPaymentService::class);
    $result = $service->initializePayment($appointment, $appointmentType, $patient);

    expect($result['strategy'])->toBe('payment_intent');
    expect($result['client_secret'])->toBe('pi_secret_123');

    $payment = AppointmentPayment::query()->where('appointment_id', $appointment->id)->first();
    expect($payment?->stripe_payment_intent_id)->toBe('pi_test_123');
    expect($payment?->status)->toBe('requires_payment_method');
});

it('creates setup intent when appointment is beyond 7 days', function () {
    config(['services.stripe.secret' => 'sk_test_dummy']);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'setup_intents')) {
            return Http::response([
                'id' => 'seti_test_123',
                'client_secret' => 'seti_secret_123',
                'status' => 'requires_payment_method',
            ], 200);
        }

        return Http::response([], 500);
    });

    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'deposit_amount_cents' => 10000,
        'deposit_currency' => 'usd',
    ]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDays(12),
    ]);

    $service = app(AppointmentPaymentService::class);
    $result = $service->initializePayment($appointment, $appointmentType, $patient);

    expect($result['strategy'])->toBe('setup_intent');
    expect($result['client_secret'])->toBe('seti_secret_123');

    $payment = AppointmentPayment::query()->where('appointment_id', $appointment->id)->first();
    expect($payment?->stripe_setup_intent_id)->toBe('seti_test_123');
    expect($payment?->auth_scheduled_for)->not->toBeNull();
});

it('authorizes scheduled holds using stored setup intent payment method', function () {
    config(['services.stripe.secret' => 'sk_test_dummy']);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'payment_intents')) {
            return Http::response([
                'id' => 'pi_auth_123',
                'status' => 'requires_capture',
            ], 200);
        }

        return Http::response([], 500);
    });

    $payment = AppointmentPayment::factory()->create([
        'strategy' => 'setup_intent',
        'status' => 'pending_setup',
        'stripe_payment_method_id' => 'pm_123',
        'stripe_payment_intent_id' => null,
        'auth_scheduled_for' => now()->subMinute(),
    ]);

    $service = app(AppointmentPaymentService::class);
    $count = $service->authorizeScheduledHolds();

    expect($count)->toBe(1);

    $payment->refresh();
    expect($payment->stripe_payment_intent_id)->toBe('pi_auth_123');
    expect($payment->authorized_at)->not->toBeNull();
});
