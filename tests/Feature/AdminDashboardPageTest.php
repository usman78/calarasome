<?php

use App\Livewire\Admin\DashboardPage;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('filters appointments by clinic, provider, and status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create(['name' => 'Dashboard Clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'name' => 'Consult']);
    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Dr. Alpha']);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Dr. Beta']);

    $patientA = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Patient A']);
    $patientB = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Patient B']);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $providerA->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patientA->id,
        'status' => 'confirmed',
        'slot_datetime' => now()->addDay(),
    ]);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $providerB->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patientB->id,
        'status' => 'cancelled_by_patient',
        'slot_datetime' => now()->addDays(2),
    ]);

    Livewire::actingAs($admin)
        ->test(DashboardPage::class)
        ->set('clinicFilter', (string) $clinic->id)
        ->set('providerFilter', (string) $providerA->id)
        ->set('statusFilter', 'confirmed')
        ->assertSee('Patient A')
        ->assertDontSee('Patient B');
});
