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
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists payment records and filters by status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create(['name' => 'Pay Clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'name' => 'Laser']);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Dr. Paid']);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Payment Patient']);

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
        'amount_cents' => 5000,
        'currency' => 'usd',
        'grace_expires_at' => now()->addHours(48),
    ]);

    Livewire::actingAs($admin)
        ->test(PaymentsPage::class)
        ->set('clinicFilter', (string) $clinic->id)
        ->set('statusFilter', 'failed')
        ->assertSee('Payments Monitor')
        ->assertSee('failed')
        ->assertSee('Payment Patient')
        ->assertSee('Dr. Paid');
});

it('filters in grace versus grace expired', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create(['name' => 'Grace Clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'name' => 'Grace Consult']);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Dr. Grace']);

    $patientInGrace = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'In Grace Patient']);
    $patientExpired = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Expired Grace Patient']);
    $patientStatusExpired = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Status Grace Expired']);

    $appointmentInGrace = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patientInGrace->id,
        'slot_datetime' => now()->addDay(),
    ]);

    $appointmentExpired = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patientExpired->id,
        'slot_datetime' => now()->addDays(2),
    ]);

    $appointmentStatusExpired = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patientStatusExpired->id,
        'slot_datetime' => now()->addDays(3),
    ]);

    AppointmentPayment::factory()->create([
        'appointment_id' => $appointmentInGrace->id,
        'patient_id' => $patientInGrace->id,
        'appointment_type_id' => $appointmentType->id,
        'status' => 'failed',
        'amount_cents' => 4000,
        'currency' => 'usd',
        'grace_expires_at' => now()->addHours(2),
    ]);

    AppointmentPayment::factory()->create([
        'appointment_id' => $appointmentExpired->id,
        'patient_id' => $patientExpired->id,
        'appointment_type_id' => $appointmentType->id,
        'status' => 'failed',
        'amount_cents' => 4000,
        'currency' => 'usd',
        'grace_expires_at' => now()->subHours(2),
    ]);

    AppointmentPayment::factory()->create([
        'appointment_id' => $appointmentStatusExpired->id,
        'patient_id' => $patientStatusExpired->id,
        'appointment_type_id' => $appointmentType->id,
        'status' => 'grace_expired',
        'amount_cents' => 4000,
        'currency' => 'usd',
        'grace_expires_at' => now()->subHours(4),
    ]);

    Livewire::actingAs($admin)
        ->test(PaymentsPage::class)
        ->set('clinicFilter', (string) $clinic->id)
        ->set('statusFilter', 'in_grace')
        ->assertSee('In Grace Patient')
        ->assertDontSee('Expired Grace Patient')
        ->assertDontSee('Status Grace Expired')
        ->set('statusFilter', 'grace_expired')
        ->assertSee('Expired Grace Patient')
        ->assertSee('Status Grace Expired')
        ->assertDontSee('In Grace Patient');
});
