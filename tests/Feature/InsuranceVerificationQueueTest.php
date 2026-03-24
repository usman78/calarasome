<?php

use App\Livewire\Admin\InsuranceVerificationPage;
use App\Mail\InsuranceVerificationFailedEmail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows admin to mark insurance verification as failed and notifies patient', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'is_medical' => true]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $verification = InsuranceVerification::query()->create([
        'clinic_id' => $clinic->id,
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
        'urgency' => 'high',
        'insurance_data' => [
            'provider' => 'Blue Shield',
            'member_id' => 'MEM123',
        ],
        'alerted_at' => now(),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(InsuranceVerificationPage::class)
        ->call('markFailed', $verification->id);

    $verification->refresh();
    expect($verification->status)->toBe('failed');
    expect($verification->failed_at)->not->toBeNull();

    Mail::assertSent(InsuranceVerificationFailedEmail::class);
});

it('allows admin to mark insurance verification as verified', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'is_medical' => true]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
    ]);

    $verification = InsuranceVerification::query()->create([
        'clinic_id' => $clinic->id,
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
        'urgency' => 'standard',
        'insurance_data' => [
            'provider' => 'Aetna',
            'member_id' => 'MEM777',
        ],
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(InsuranceVerificationPage::class)
        ->call('markVerified', $verification->id);

    $verification->refresh();
    expect($verification->status)->toBe('verified');
    expect($verification->verified_at)->not->toBeNull();
});
