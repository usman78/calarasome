<?php

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    $this->actingAs($user)
        ->get(route('admin.providers'))
        ->assertOk()
        ->assertSee('Provider Management');
});

it('requires admin for patient match alerts page', function () {
    $this->get(route('admin.patient-match-alerts'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.patient-match-alerts'))->assertForbidden();
});

it('allows admin to access patient match alerts page and see alert rows', function () {
    $clinic = Clinic::factory()->create(['name' => 'SmartBook Demo Clinic']);
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

    $admin = User::factory()->create(['is_admin' => true]);

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

    $this->actingAs($user)
        ->get(route('admin.appointment-types'))
        ->assertOk()
        ->assertSee('Appointment Type Management');
});

it('requires admin for payments page', function () {
    $this->get(route('admin.payments'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.payments'))->assertForbidden();
});

it('allows admin to access payments page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('Payments Monitor');
});

it('requires admin for waitlist page', function () {
    $this->get(route('admin.waitlist'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.waitlist'))->assertForbidden();
});

it('allows admin to access waitlist page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.waitlist'))
        ->assertOk()
        ->assertSee('Waitlist Priority');
});

it('requires admin for insurance verification queue', function () {
    $this->get(route('admin.insurance-verifications'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.insurance-verifications'))->assertForbidden();
});

it('allows admin to access insurance verification queue', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.insurance-verifications'))
        ->assertOk()
        ->assertSee('Insurance Verification Queue');
});

it('requires admin for patient merge audit page', function () {
    $this->get(route('admin.patient-merge-audit'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get(route('admin.patient-merge-audit'))->assertForbidden();
});

it('allows admin to access patient merge audit page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.patient-merge-audit'))
        ->assertOk()
        ->assertSee('Patient Merge Audit Log');
});

it('allows admin to mark a patient match alert as resolved', function () {
    $clinic = Clinic::factory()->create();
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
    $alert = PatientMatchAlert::query()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'alert_type' => 'shared_email_mismatch',
        'payload' => ['email' => $patient->email],
        'resolved_at' => null,
    ]);

    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\PatientMatchAlertsPage::class)
        ->call('markResolved', $alert->id)
        ->assertSee('Alert marked as resolved.');

    expect($alert->fresh()->resolved_at)->not->toBeNull();
});
