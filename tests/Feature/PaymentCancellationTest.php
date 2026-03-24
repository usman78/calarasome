<?php

use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('voids auth hold when patient cancels more than 24 hours before', function () {
    Mail::fake();
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'patient@example.com']);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addHours(48),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_cancel_123',
    ]);

    $issued = AppointmentAccessToken::issue($appointment, $patient);

    Http::fake([
        'https://api.stripe.com/v1/payment_intents/pi_cancel_123/cancel' => Http::response(['id' => 'pi_cancel_123']),
    ]);

    $this->postJson("/api/public/appointments/{$appointment->id}/cancel", [
        'token' => $issued['token'],
    ])->assertOk();

    $payment->refresh();
    $appointment->refresh();

    expect($payment->status)->toBe('voided')
        ->and($payment->voided_at)->not->toBeNull();
    expect($appointment->status)->toBe('cancelled_by_patient');
});

it('captures deposit when patient cancels within 24 hours', function () {
    Mail::fake();
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'late@example.com']);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addHours(6),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_capture_123',
    ]);

    $issued = AppointmentAccessToken::issue($appointment, $patient);

    Http::fake([
        'https://api.stripe.com/v1/payment_intents/pi_capture_123/capture' => Http::response(['id' => 'pi_capture_123', 'status' => 'succeeded']),
    ]);

    $this->postJson("/api/public/appointments/{$appointment->id}/cancel", [
        'token' => $issued['token'],
    ])->assertOk();

    $payment->refresh();
    $appointment->refresh();

    expect($payment->status)->toBe('succeeded')
        ->and($payment->captured_at)->not->toBeNull();
    expect($appointment->status)->toBe('cancelled_by_patient');
});

it('cancels scheduled setup intent when patient cancels before T-7', function () {
    Mail::fake();
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'early@example.com']);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDays(14),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'setup_intent',
        'status' => 'pending_setup',
        'stripe_payment_intent_id' => null,
        'auth_scheduled_for' => now()->addDays(7),
    ]);

    $issued = AppointmentAccessToken::issue($appointment, $patient);

    Http::fake();

    $this->postJson("/api/public/appointments/{$appointment->id}/cancel", [
        'token' => $issued['token'],
    ])->assertOk();

    $payment->refresh();

    expect($payment->status)->toBe('voided')
        ->and($payment->auth_scheduled_for)->toBeNull();

    Http::assertNothingSent();
});

it('refunds captured deposits when clinic cancels', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'clinic@example.com']);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDay(),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'succeeded',
        'captured_at' => now()->subMinute(),
        'stripe_payment_intent_id' => 'pi_refund_123',
    ]);

    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_123']),
    ]);

    $this->actingAs($admin)->deleteJson("/api/admin/appointments/{$appointment->id}")
        ->assertOk();

    $payment->refresh();
    $appointment->refresh();

    expect($payment->status)->toBe('refunded')
        ->and($payment->refund_id)->toBe('re_123')
        ->and($payment->refunded_at)->not->toBeNull();
    expect($appointment->status)->toBe('cancelled_by_clinic');
});
