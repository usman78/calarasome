<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\InsuranceVerification;
use App\Models\Provider;
use App\Models\SlotReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('requires insurance details for medical appointments', function () {
    $clinic = Clinic::factory()->create([
        'slug' => 'insurance-clinic',
        'min_booking_notice_hours' => 0,
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'is_medical' => true,
    ]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $sessionToken = Str::random(64);
    SlotReservation::query()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => now()->addDays(2),
        'session_token' => $sessionToken,
        'reserved_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $payload = [
        'session_token' => $sessionToken,
        'full_name' => 'Medical Patient',
        'email' => 'medical@example.com',
        'date_of_birth' => '1990-01-01',
        'email_consent' => true,
        'email_phi' => false,
    ];

    $this->postJson("/api/public/clinics/{$clinic->slug}/appointments", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'insurance_provider',
            'insurance_member_id',
            'insurance_subscriber_name',
            'insurance_subscriber_dob',
            'insurance_relationship',
            'insurance_urgency',
        ]);
});

it('creates insurance verification and alerts for high urgency', function () {
    $clinic = Clinic::factory()->create([
        'slug' => 'insurance-clinic-two',
        'min_booking_notice_hours' => 0,
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'is_medical' => true,
        'deposit_amount_cents' => 0,
    ]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $sessionToken = Str::random(64);
    SlotReservation::query()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => now()->addDays(2),
        'session_token' => $sessionToken,
        'reserved_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $payload = [
        'session_token' => $sessionToken,
        'full_name' => 'Medical Patient',
        'email' => 'medical2@example.com',
        'date_of_birth' => '1992-02-02',
        'email_consent' => true,
        'email_phi' => false,
        'insurance_provider' => 'Blue Shield',
        'insurance_member_id' => 'MEM123',
        'insurance_group_id' => 'GRP456',
        'insurance_plan' => 'PPO Plus',
        'insurance_subscriber_name' => 'Medical Patient',
        'insurance_subscriber_dob' => '1992-02-02',
        'insurance_relationship' => 'self',
        'insurance_phone' => '800-555-1010',
        'insurance_urgency' => 'high',
    ];

    $this->postJson("/api/public/clinics/{$clinic->slug}/appointments", $payload)
        ->assertCreated();

    $verification = InsuranceVerification::query()->first();
    expect($verification)->not->toBeNull();
    expect($verification?->urgency)->toBe('high');
    expect($verification?->alerted_at)->not->toBeNull();
    expect($verification?->insurance_data['provider'] ?? null)->toBe('Blue Shield');
});
