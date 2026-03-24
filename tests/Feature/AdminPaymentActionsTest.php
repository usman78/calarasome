<?php

use App\Livewire\Admin\PaymentsPage;
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
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows admin to cancel appointment from payments page', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'admincancel@example.com']);

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
        'status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_admin_cancel',
    ]);

    Http::fake([
        'https://api.stripe.com/v1/payment_intents/pi_admin_cancel/cancel' => Http::response(['id' => 'pi_admin_cancel']),
    ]);

    Livewire::actingAs($admin)
        ->test(PaymentsPage::class)
        ->call('cancelAppointment', $appointment->id);

    $payment->refresh();
    $appointment->refresh();

    expect($appointment->status)->toBe('cancelled_by_clinic');
    expect($payment->status)->toBe('voided')
        ->and($payment->voided_at)->not->toBeNull();
});

it('allows admin to mark no-show from payments page', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'adminnoshow@example.com']);

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
        'stripe_payment_intent_id' => 'pi_admin_noshow',
    ]);

    Http::fake([
        'https://api.stripe.com/v1/payment_intents/pi_admin_noshow/capture' => Http::response(['id' => 'pi_admin_noshow', 'status' => 'succeeded']),
    ]);

    Livewire::actingAs($admin)
        ->test(PaymentsPage::class)
        ->call('markNoShow', $appointment->id, true);

    $payment->refresh();
    $appointment->refresh();
    $patient->refresh();

    expect($appointment->status)->toBe('no_show');
    expect($payment->status)->toBe('succeeded')
        ->and($payment->captured_at)->not->toBeNull();
    expect($patient->no_show_count)->toBe(1);
});
