<?php

use App\Livewire\Admin\DashboardAlerts;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows dashboard alerts for insurance, payments, and match alerts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create(['name' => 'Alert Clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => now()->addDay(),
    ]);

    AppointmentPayment::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'status' => 'failed',
        'amount_cents' => 4000,
        'currency' => 'usd',
        'grace_expires_at' => now()->addHours(24),
    ]);

    InsuranceVerification::query()->create([
        'clinic_id' => $clinic->id,
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
        'urgency' => 'high',
        'insurance_data' => [
            'provider' => 'Aetna',
            'member_id' => 'MEM999',
        ],
        'alerted_at' => now(),
    ]);

    PatientMatchAlert::query()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'alert_type' => 'shared_email_mismatch',
        'payload' => [
            'email' => $patient->email,
            'newPatientId' => $patient->id,
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(DashboardAlerts::class)
        ->assertSee('Insurance Urgency Alerts')
        ->assertSee('Payment Alerts')
        ->assertSee('Shared Email Alerts');
});
