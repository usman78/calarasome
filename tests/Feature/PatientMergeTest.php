<?php

use App\Livewire\Admin\PatientMatchAlertsPage;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('merges a shared-email patient into an existing record and logs the merge', function () {
    $clinic = Clinic::factory()->create();
    $existing = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Existing Patient',
        'email' => 'family@example.com',
    ]);
    $newPatient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'New Patient',
        'email' => 'family@example.com',
        'is_shared_email_account' => true,
    ]);

    $alert = PatientMatchAlert::query()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $newPatient->id,
        'alert_type' => 'shared_email_mismatch',
        'payload' => [
            'email' => 'family@example.com',
            'newPatientId' => $newPatient->id,
            'existingPatientIds' => [$existing->id],
        ],
    ]);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $newPatient->id,
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(PatientMatchAlertsPage::class)
        ->set('mergeTargetId', $existing->id)
        ->call('mergePatient', $alert->id, $newPatient->id, $existing->id);

    expect(Patient::query()->whereKey($newPatient->id)->exists())->toBeFalse();
    expect(Appointment::query()->where('patient_id', $existing->id)->exists())->toBeTrue();
    expect($alert->fresh()->resolved_at)->not->toBeNull();
});
