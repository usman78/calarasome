<?php

use App\Models\Appointment;
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

it('captures deposit and increments no-show count when admin marks no-show', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'noshow@example.com']);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->subHour(),
        'status' => 'confirmed',
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_noshow_123',
    ]);

    Http::fake([
        'https://api.stripe.com/v1/payment_intents/pi_noshow_123/capture' => Http::response(['id' => 'pi_noshow_123', 'status' => 'succeeded']),
    ]);

    $this->actingAs($admin)->postJson("/api/admin/appointments/{$appointment->id}/no-show", [
        'charge_deposit' => true,
    ])->assertOk();

    $payment->refresh();
    $appointment->refresh();
    $patient->refresh();

    expect($payment->status)->toBe('succeeded')
        ->and($payment->captured_at)->not->toBeNull();
    expect($appointment->status)->toBe('no_show');
    expect($patient->no_show_count)->toBe(1);
});

it('requires admin for no-show endpoint', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $appointment = Appointment::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/admin/appointments/{$appointment->id}/no-show")
        ->assertForbidden();
});
