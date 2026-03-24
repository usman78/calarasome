<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\WaitlistPriorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('scores urgent tier when urgency flag is set', function () {
    $clinic = Clinic::factory()->create();
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'no_show_count' => 1]);

    $entry = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'triage_data' => ['urgency_flag' => true],
        'created_at' => now()->subDays(3),
    ]);

    $service = app(WaitlistPriorityService::class);
    $entry = $service->refreshEntry($entry);

    expect($entry->tier)->toBe('urgent')
        ->and($entry->priority_score)->toBeGreaterThanOrEqual(100);
});

it('scores high tier for patient with completed appointment', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'status' => 'completed',
    ]);

    $entry = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'triage_data' => ['urgency_flag' => false],
        'created_at' => now()->subDay(),
    ]);

    $service = app(WaitlistPriorityService::class);
    $entry = $service->refreshEntry($entry);

    expect($entry->tier)->toBe('high')
        ->and($entry->priority_score)->toBeGreaterThanOrEqual(50);
});

it('applies wait time cap and no-show penalty', function () {
    $clinic = Clinic::factory()->create();
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id, 'no_show_count' => 2]);

    $entry = WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'triage_data' => ['urgency_flag' => false],
        'preferred_datetime' => now()->addDay(),
        'created_at' => now()->subDays(60),
    ]);

    $service = app(WaitlistPriorityService::class);
    $entry = $service->refreshEntry($entry);

    expect($entry->priority_score)->toBe(15);
    expect($entry->tier)->toBe('standard');
});

it('exposes priority breakdown for admins', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    WaitlistEntry::factory()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'triage_data' => ['urgency_flag' => true],
    ]);

    $this->actingAs($admin)
        ->getJson('/api/admin/waitlist/priority-breakdown')
        ->assertOk()
        ->assertJsonStructure([
            'urgent',
            'high',
            'standard',
        ]);
});
