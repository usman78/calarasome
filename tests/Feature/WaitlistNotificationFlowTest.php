<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use App\Models\WaitlistNotification;
use App\Models\WaitlistNotificationRecipient;
use App\Services\AppointmentPaymentService;
use App\Services\WaitlistNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('staggered waitlist notifications by tier', function () {
    Mail::fake();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-17 10:00:00'));

    $clinic = Clinic::factory()->create(['min_booking_notice_hours' => 0]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'deposit_amount_cents' => 0,
    ]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $urgentPatient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $highPatient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $standardPatient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $urgent = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $urgentPatient->id,
        'appointment_type_id' => $appointmentType->id,
        'provider_id' => $provider->id,
        'preferred_datetime' => null,
        'priority_score' => 120,
        'tier' => 'urgent',
        'status' => 'active',
    ]);

    $high = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $highPatient->id,
        'appointment_type_id' => $appointmentType->id,
        'provider_id' => $provider->id,
        'preferred_datetime' => null,
        'priority_score' => 70,
        'tier' => 'high',
        'status' => 'active',
    ]);

    $standard = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $standardPatient->id,
        'appointment_type_id' => $appointmentType->id,
        'provider_id' => $provider->id,
        'preferred_datetime' => null,
        'priority_score' => 10,
        'tier' => 'standard',
        'status' => 'active',
    ]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => now()->addDays(2),
        'status' => 'confirmed',
    ]);

    app(AppointmentPaymentService::class)->cancelByPatient($appointment);

    $notification = WaitlistNotification::query()->first();
    expect($notification)->not->toBeNull();

    $service = app(WaitlistNotificationService::class);
    $service->dispatchDueNotifications();

    $recipients = WaitlistNotificationRecipient::query()->get();
    expect($recipients)->toHaveCount(1);
    expect($recipients->first()->waitlist_entry_id)->toBe($urgent->id);

    $notification->refresh();
    CarbonImmutable::setTestNow($notification->next_round_at->addMinute());
    $service->dispatchDueNotifications();

    $recipients = WaitlistNotificationRecipient::query()->get();
    expect($recipients)->toHaveCount(2);
    expect($recipients->pluck('waitlist_entry_id')->all())->toContain($high->id);

    $notification->refresh();
    CarbonImmutable::setTestNow($notification->next_round_at->addMinute());
    $service->dispatchDueNotifications();

    $recipients = WaitlistNotificationRecipient::query()->get();
    expect($recipients)->toHaveCount(3);
    expect($recipients->pluck('waitlist_entry_id')->all())->toContain($standard->id);

    $notification->refresh();
    expect($notification->status)->toBe('expired');
});

it('claims a waitlist slot and cancels remaining recipients', function () {
    Mail::fake();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-17 11:00:00'));

    $clinic = Clinic::factory()->create(['min_booking_notice_hours' => 0]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'deposit_amount_cents' => 0,
    ]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $patientA = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $patientB = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $urgentA = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patientA->id,
        'appointment_type_id' => $appointmentType->id,
        'provider_id' => $provider->id,
        'preferred_datetime' => null,
        'priority_score' => 120,
        'tier' => 'urgent',
        'status' => 'active',
    ]);

    $urgentB = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patientB->id,
        'appointment_type_id' => $appointmentType->id,
        'provider_id' => $provider->id,
        'preferred_datetime' => null,
        'priority_score' => 110,
        'tier' => 'urgent',
        'status' => 'active',
    ]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => now()->addDays(2),
        'status' => 'confirmed',
    ]);

    app(AppointmentPaymentService::class)->cancelByPatient($appointment);

    $service = app(WaitlistNotificationService::class);
    $service->dispatchDueNotifications();

    $recipient = WaitlistNotificationRecipient::query()
        ->where('waitlist_entry_id', $urgentA->id)
        ->first();

    expect($recipient)->not->toBeNull();

    $token = 'claim-token';
    $recipient->update(['token_hash' => hash('sha256', $token)]);

    $result = $service->claim($token, $patientA->date_of_birth->format('Y-m-d'));
    expect($result['status'])->toBe('claimed');

    $notification = WaitlistNotification::query()->first();
    expect($notification?->status)->toBe('claimed');
    expect($notification?->claimed_by_waitlist_entry_id)->toBe($urgentA->id);

    $recipient->refresh();
    expect($recipient->status)->toBe('claimed');

    $other = WaitlistNotificationRecipient::query()
        ->where('waitlist_entry_id', $urgentB->id)
        ->first();
    expect($other?->status)->toBe('canceled');

    $urgentA->refresh();
    expect($urgentA->status)->toBe('claimed');

    expect(Appointment::query()->count())->toBe(2);
});
