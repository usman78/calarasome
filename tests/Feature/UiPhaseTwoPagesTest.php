<?php

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders booking wizard page for a clinic slug', function () {
    $clinic = Clinic::factory()->create(['slug' => 'phase-two-clinic']);

    $this->get(route('booking.wizard', ['clinic' => $clinic->slug]))
        ->assertOk()
        ->assertSee('Book an Appointment');
});

it('requires admin for provider management page', function () {
    $this->get(route('admin.providers'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.providers'))->assertForbidden();
});

it('allows admin to access provider management page', function () {
    $user = User::factory()->create(['is_admin' => true]);
    Clinic::factory()->create(['owner_id' => $user->id, 'name' => 'Owned Clinic']);

    $this->actingAs($user)
        ->get(route('admin.providers'))
        ->assertOk()
        ->assertSee('Provider Management');
});

it('only shows clinics owned by the signed-in admin on admin pages', function () {
    $owner = User::factory()->admin()->create();
    $otherOwner = User::factory()->admin()->create();

    Clinic::factory()->create(['owner_id' => $owner->id, 'name' => 'Owner Clinic']);
    Clinic::factory()->create(['owner_id' => $otherOwner->id, 'name' => 'Other Clinic']);

    $this->actingAs($owner)
        ->get(route('admin.providers'))
        ->assertOk()
        ->assertSee('Owner Clinic')
        ->assertDontSee('Other Clinic');
});

it('requires admin for patient match alerts page', function () {
    $this->get(route('admin.patient-match-alerts'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.patient-match-alerts'))->assertForbidden();
});

it('allows admin to access patient match alerts page and see alert rows', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create(['name' => 'SmartBook Demo Clinic', 'owner_id' => $admin->id]);
    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Child Example',
        'email' => 'family@example.com',
        'date_of_birth' => '2012-02-02',
    ]);

    PatientMatchAlert::query()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'alert_type' => 'shared_email_mismatch',
        'payload' => [
            'email' => 'family@example.com',
            'newPatientId' => $patient->id,
            'newPatientDob' => '2012-02-02',
            'existingPatientIds' => [999],
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.patient-match-alerts'))
        ->assertOk()
        ->assertSee('Patient Match Alerts')
        ->assertSee('shared_email_mismatch')
        ->assertSee('family@example.com');
});

it('requires admin for appointment types page', function () {
    $this->get(route('admin.appointment-types'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.appointment-types'))->assertForbidden();
});

it('allows admin to access appointment types page', function () {
    $user = User::factory()->create(['is_admin' => true]);
    Clinic::factory()->create(['owner_id' => $user->id, 'name' => 'Owned Clinic']);

    $this->actingAs($user)
        ->get(route('admin.appointment-types'))
        ->assertOk()
        ->assertSee('Appointment Type Management');
});
