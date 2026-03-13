<?php

use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('verifies date of birth and shows appointment details', function () {
    $clinic = Clinic::factory()->create([
        'name' => 'Secure Clinic',
        'timezone' => 'America/New_York',
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Consultation',
    ]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Dr. Secure',
    ]);
    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'date_of_birth' => '1990-01-01',
    ]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $issued = AppointmentAccessToken::issue($appointment, $patient);
    $token = $issued['token'];

    $this->get(route('appointments.secure', ['token' => $token]))
        ->assertOk()
        ->assertSee('Secure Appointment Details')
        ->assertSee('Verify your date of birth');

    $this->post(route('appointments.secure.verify', ['token' => $token]), [
        'date_of_birth' => '1990-01-01',
    ])->assertOk()
        ->assertSee('Consultation')
        ->assertSee('Dr. Secure')
        ->assertSee('Secure Clinic');
});

it('locks out after three failed date of birth attempts', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'date_of_birth' => '1992-02-02',
    ]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $issued = AppointmentAccessToken::issue($appointment, $patient);
    $token = $issued['token'];

    $this->post(route('appointments.secure.verify', ['token' => $token]), [
        'date_of_birth' => '1990-01-01',
    ])->assertStatus(422);

    $this->post(route('appointments.secure.verify', ['token' => $token]), [
        'date_of_birth' => '1991-01-01',
    ])->assertStatus(422);

    $this->post(route('appointments.secure.verify', ['token' => $token]), [
        'date_of_birth' => '1993-03-03',
    ])->assertStatus(423)
        ->assertSee('locked');

    $record = AppointmentAccessToken::query()->where('token_hash', hash('sha256', $token))->first();
    expect($record)->not->toBeNull();
    expect($record?->failed_attempts)->toBeGreaterThanOrEqual(3);
    expect($record?->locked_until)->not->toBeNull();

    $this->post(route('appointments.secure.verify', ['token' => $token]), [
        'date_of_birth' => '1992-02-02',
    ])->assertStatus(423);
});

it('rejects expired token links', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $token = 'expired-token';

    AppointmentAccessToken::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'email' => $patient->email,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->subMinute(),
        'failed_attempts' => 0,
        'locked_until' => null,
        'last_sent_at' => now()->subDay(),
    ]);

    $this->get(route('appointments.secure', ['token' => $token]))
        ->assertStatus(410)
        ->assertSee('expired');
});

it('rejects invalid token links', function () {
    $this->get(route('appointments.secure', ['token' => 'unknown-token']))
        ->assertStatus(404)
        ->assertSee('invalid');
});
