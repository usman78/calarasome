<?php

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\AppointmentPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
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

it('allows admin to undo a no-show during the reversal window and refunds the deposit', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'undo@example.com', 'no_show_count' => 1]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->subHour(),
        'status' => 'no_show',
        'no_show_previous_status' => 'confirmed',
        'no_show_marked_at' => now()->subMinutes(10),
        'no_show_reversible_until' => now()->addMinutes(20),
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'succeeded',
        'amount_cents' => 2500,
        'stripe_payment_intent_id' => 'pi_reverse_123',
        'captured_at' => now()->subMinutes(10),
    ]);

    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_reverse_123']),
    ]);

    $result = app(AppointmentPaymentService::class)->reverseNoShow($appointment, $admin->id);

    $appointment->refresh();
    $payment->refresh();
    $patient->refresh();

    expect($result['window_mode'])->toBe('undo_window');
    expect($appointment->status)->toBe('confirmed');
    expect($payment->status)->toBe('refunded')
        ->and($payment->refund_id)->toBe('re_reverse_123')
        ->and($payment->refunded_at)->not->toBeNull();
    expect($patient->no_show_count)->toBe(0);
    expect(AuditLog::query()->where('action', 'reverse_no_show')->count())->toBe(1);
});

it('requires a reason when reversing a no-show after the undo window has closed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $appointment = Appointment::factory()->create([
        'status' => 'no_show',
        'no_show_previous_status' => 'confirmed',
        'no_show_marked_at' => now()->subHour(),
        'no_show_reversible_until' => now()->subMinutes(5),
    ]);

    expect(fn () => app(AppointmentPaymentService::class)->reverseNoShow($appointment, $admin->id))
        ->toThrow(RuntimeException::class, 'A reason is required once the no-show undo window has closed.');
});

it('allows admin to reverse a no-show after the window with a reason and logs it', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'email' => 'late-reverse@example.com', 'no_show_count' => 1]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->subHours(2),
        'status' => 'no_show',
        'no_show_previous_status' => 'completed',
        'no_show_marked_at' => now()->subHour(),
        'no_show_reversible_until' => now()->subMinutes(10),
    ]);

    $payment = AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'strategy' => 'payment_intent',
        'status' => 'succeeded',
        'amount_cents' => 1500,
        'stripe_payment_intent_id' => 'pi_reverse_late_123',
        'captured_at' => now()->subHour(),
    ]);

    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response(['id' => 're_reverse_late_123']),
    ]);

    $result = app(AppointmentPaymentService::class)->reverseNoShow(
        $appointment,
        $admin->id,
        'patient_attended',
        'Front desk confirmed the patient attended.'
    );

    $appointment->refresh();
    $payment->refresh();
    $patient->refresh();
    $audit = AuditLog::query()->latest('id')->first();

    expect($result['window_mode'])->toBe('post_window');
    expect($appointment->status)->toBe('completed');
    expect($payment->status)->toBe('refunded');
    expect($patient->no_show_count)->toBe(0);
    expect($audit)->not->toBeNull();
    expect($audit?->reason)->toBe('patient_attended');
    expect($audit?->notes)->toBe('Front desk confirmed the patient attended.');
});
