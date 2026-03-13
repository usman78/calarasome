<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\SlotReservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('releases expired reservations and command remains idempotent', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $expiredA = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->subMinutes(5),
        'released_at' => null,
    ]);

    $expiredB = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->subMinute(),
        'released_at' => null,
    ]);

    $active = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->addMinutes(5),
        'released_at' => null,
    ]);

    $alreadyReleased = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->subMinutes(20),
        'released_at' => now()->subMinutes(10),
    ]);

    $firstExitCode = Artisan::call('reservations:release-expired');
    $firstOutput = Artisan::output();

    expect($firstExitCode)->toBe(0);
    expect($firstOutput)->toContain('Released 2 expired reservation(s).');

    $expiredA->refresh();
    $expiredB->refresh();
    $active->refresh();
    $alreadyReleased->refresh();

    expect($expiredA->released_at)->not->toBeNull();
    expect($expiredB->released_at)->not->toBeNull();
    expect($active->released_at)->toBeNull();
    expect($alreadyReleased->released_at)->not->toBeNull();

    $firstReleasedAt = $expiredA->released_at?->toIso8601String();

    $secondExitCode = Artisan::call('reservations:release-expired');
    $secondOutput = Artisan::output();

    expect($secondExitCode)->toBe(0);
    expect($secondOutput)->toContain('Released 0 expired reservation(s).');

    $expiredA->refresh();
    expect($expiredA->released_at?->toIso8601String())->toBe($firstReleasedAt);
});

it('rejects reserve requests when provider is not mapped to appointment type', function () {
    $clinic = Clinic::factory()->create([
        'slug' => 'resilience-clinic',
        'min_booking_notice_hours' => 0,
    ]);

    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [],
    ]);

    $slotLocal = now()->addDay()->setTimezone($clinic->timezone)->startOfHour()->format('Y-m-d H:i:s');

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/slots/reserve', [
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_local_datetime' => $slotLocal,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Provider is not mapped to appointment type.');
});

it('rejects triage requests when provider is not mapped to appointment type', function () {
    $clinic = Clinic::factory()->create(['slug' => 'triage-resilience-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [],
    ]);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/triage', [
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'date' => now()->addDay()->setTimezone($clinic->timezone)->format('Y-m-d'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Provider is not mapped to appointment type.');
});

it('persists communication consent payload details when creating appointments', function () {
    $clinic = Clinic::factory()->create(['slug' => 'consent-details-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $reservation = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => now()->addDay(),
        'expires_at' => now()->addMinutes(15),
        'released_at' => null,
    ]);

    $response = $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.99',
    ])->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => $reservation->session_token,
        'full_name' => 'Consent Detail',
        'email' => 'consent-detail@example.com',
        'phone' => '555-2026',
        'date_of_birth' => '1993-04-10',
        'email_consent' => true,
        'email_phi' => true,
    ]);

    $response->assertCreated();

    $patient = Patient::query()
        ->where('clinic_id', $clinic->id)
        ->where('email', 'consent-detail@example.com')
        ->first();

    expect($patient)->not->toBeNull();
    expect($patient?->communication_consent['emailConsent'] ?? null)->toBeTrue();
    expect($patient?->communication_consent['emailPHI'] ?? null)->toBeTrue();
    expect($patient?->communication_consent['consentIP'] ?? null)->toBe('203.0.113.99');
    expect(fn () => CarbonImmutable::parse($patient?->communication_consent['consentedAt'] ?? ''))->not->toThrow(\Throwable::class);

    $reservation->refresh();
    expect($reservation->released_at)->not->toBeNull();
    expect($reservation->converted_to_appointment_id)->not->toBeNull();
});
