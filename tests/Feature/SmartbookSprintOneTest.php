<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SlotReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deactivates provider with appointment history instead of hard deleting', function () {
    $this->actingAs(User::factory()->admin()->create());

    $clinic = Clinic::factory()->create();
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'is_active' => true,
    ]);

    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $response = $this->deleteJson('/api/admin/providers/'.$provider->id);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'At least one active provider is required per clinic.');
});

it('reserves a slot and creates appointment with valid consent and session token', function () {
    $clinic = Clinic::factory()->create(['slug' => 'demo-clinic']);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'duration_minutes' => 30,
    ]);

    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    ProviderSchedule::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'day_of_week' => CarbonImmutable::parse('next monday', $clinic->timezone)->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    $slotLocal = CarbonImmutable::parse('next monday 09:00:00', $clinic->timezone)->format('Y-m-d H:i:s');

    $reserve = $this->postJson('/api/public/clinics/'.$clinic->slug.'/slots/reserve', [
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_local_datetime' => $slotLocal,
    ]);

    $reserve->assertCreated();
    $token = $reserve->json('sessionToken');

    $book = $this->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => $token,
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '123456789',
        'date_of_birth' => '1990-01-01',
        'email_consent' => true,
        'email_phi' => false,
    ]);

    $book->assertCreated();
    expect(Appointment::query()->count())->toBe(1);
    expect(SlotReservation::query()->first()?->released_at)->not->toBeNull();
});

it('rejects appointment creation when reservation token is invalid or expired', function () {
    $clinic = Clinic::factory()->create(['slug' => 'demo-clinic']);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => str_repeat('a', 64),
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '123456789',
        'date_of_birth' => '1990-01-01',
        'email_consent' => true,
        'email_phi' => false,
    ]);

    $response->assertStatus(409)
        ->assertJsonPath('code', 'RESERVATION_EXPIRED');
});

it('uses any-available assignment and chooses less loaded provider first', function () {
    $clinic = Clinic::factory()->create(['slug' => 'demo-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);

    $providerA = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
        'last_auto_assigned_at' => now()->subMinute(),
    ]);

    $providerB = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
        'last_auto_assigned_at' => null,
    ]);

    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $providerA->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDay(),
        'status' => 'confirmed',
    ]);

    $slotLocal = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0)->setTimezone($clinic->timezone)->format('Y-m-d H:i:s');

    $reserve = $this->postJson('/api/public/clinics/'.$clinic->slug.'/slots/reserve', [
        'provider_id' => 'any',
        'appointment_type_id' => $appointmentType->id,
        'slot_local_datetime' => $slotLocal,
    ]);

    $reserve->assertCreated();
    expect($reserve->json('providerAssigned.id'))->toBe($providerB->id);
});

it('flags shared-email scenario as shared account when dob differs', function () {
    $clinic = Clinic::factory()->create(['slug' => 'demo-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'email' => 'family@example.com',
        'date_of_birth' => '1980-01-01',
        'full_name' => 'Parent One',
    ]);

    $reservation = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->addMinutes(10),
        'released_at' => null,
    ]);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => $reservation->session_token,
        'full_name' => 'Child One',
        'email' => 'family@example.com',
        'phone' => '555-1212',
        'date_of_birth' => '2012-02-02',
        'email_consent' => true,
        'email_phi' => false,
    ]);

    $response->assertCreated();

    $created = Patient::query()->where('full_name', 'Child One')->first();
    expect($created)->not->toBeNull();
    expect($created?->is_shared_email_account)->toBeTrue();
});
