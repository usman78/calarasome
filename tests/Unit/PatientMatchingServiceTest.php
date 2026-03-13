<?php

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Services\PatientMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('matches existing patient by email and dob first', function () {
    $clinic = Clinic::factory()->create();

    $existing = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'phone' => '111-1111',
        'date_of_birth' => '1990-01-01',
    ]);

    $matched = app(PatientMatchingService::class)->findOrCreate([
        'full_name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'phone' => '222-2222',
        'date_of_birth' => '1990-01-01',
        'communication_consent' => ['emailConsent' => true],
    ], $clinic->id);

    expect($matched->id)->toBe($existing->id);
    expect($matched->fresh()->phone)->toBe('222-2222');
});

it('falls back to phone and dob when email changed', function () {
    $clinic = Clinic::factory()->create();

    $existing = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Brian Khan',
        'email' => 'old@example.com',
        'phone' => '333-3333',
        'date_of_birth' => '1988-08-08',
    ]);

    $matched = app(PatientMatchingService::class)->findOrCreate([
        'full_name' => 'Brian Khan',
        'email' => 'new@example.com',
        'phone' => '333-3333',
        'date_of_birth' => '1988-08-08',
        'communication_consent' => ['emailConsent' => true],
    ], $clinic->id);

    expect($matched->id)->toBe($existing->id);
    expect($matched->fresh()->email)->toBe('new@example.com');
});

it('falls back to unique normalized name and dob when phone is missing', function () {
    $clinic = Clinic::factory()->create();

    $existing = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Dr. Sana Malik',
        'email' => 'old-sana@example.com',
        'phone' => null,
        'date_of_birth' => '1985-05-05',
    ]);

    Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Another Person',
        'email' => 'another@example.com',
        'phone' => null,
        'date_of_birth' => '1985-05-05',
    ]);

    $matched = app(PatientMatchingService::class)->findOrCreate([
        'full_name' => 'Sana Malik',
        'email' => 'new-sana@example.com',
        'phone' => null,
        'date_of_birth' => '1985-05-05',
        'communication_consent' => ['emailConsent' => true],
    ], $clinic->id);

    expect($matched->id)->toBe($existing->id);
    expect($matched->fresh()->email)->toBe('new-sana@example.com');
});

it('creates shared-email patient and alert when email matches but dob differs', function () {
    $clinic = Clinic::factory()->create();

    $existing = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Parent One',
        'email' => 'family@example.com',
        'phone' => '444-4444',
        'date_of_birth' => '1980-01-01',
        'is_shared_email_account' => false,
    ]);

    $created = app(PatientMatchingService::class)->findOrCreate([
        'full_name' => 'Child One',
        'email' => 'family@example.com',
        'phone' => null,
        'date_of_birth' => '2012-02-02',
        'communication_consent' => ['emailConsent' => true],
    ], $clinic->id);

    expect($created->id)->not->toBe($existing->id);
    expect($created->is_shared_email_account)->toBeTrue();

    $alert = PatientMatchAlert::query()
        ->where('clinic_id', $clinic->id)
        ->where('patient_id', $created->id)
        ->where('alert_type', 'shared_email_mismatch')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert?->payload['existingPatientIds'] ?? [])->toContain($existing->id);
});

it('creates a new normal patient when no matching signals exist', function () {
    $clinic = Clinic::factory()->create();

    $created = app(PatientMatchingService::class)->findOrCreate([
        'full_name' => 'New Person',
        'email' => 'brandnew@example.com',
        'phone' => '555-5555',
        'date_of_birth' => '1999-09-09',
        'communication_consent' => ['emailConsent' => true],
    ], $clinic->id);

    expect($created->is_shared_email_account)->toBeFalse();
    expect(PatientMatchAlert::query()->count())->toBe(0);
});
