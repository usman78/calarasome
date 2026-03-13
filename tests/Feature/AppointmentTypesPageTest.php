<?php

use App\Livewire\Admin\AppointmentTypesPage;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates an appointment type and maps selected providers from admin page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);

    Livewire::actingAs($admin)
        ->test(AppointmentTypesPage::class)
        ->set('clinicId', $clinic->id)
        ->set('name', 'Initial Consultation')
        ->set('durationMinutes', 45)
        ->set('depositAmountCents', 2500)
        ->set('depositCurrency', 'usd')
        ->set('isMedical', false)
        ->set('isActive', true)
        ->set('selectedProviderIds', [$providerA->id])
        ->call('saveAppointmentType')
        ->assertSee('Appointment type saved.');

    $type = AppointmentType::query()
        ->where('clinic_id', $clinic->id)
        ->where('name', 'Initial Consultation')
        ->first();

    expect($type)->not->toBeNull();
    expect($type?->deposit_amount_cents)->toBe(2500);
    expect($type?->deposit_currency)->toBe('usd');
    expect($type?->is_medical)->toBeFalse();
    expect(Provider::query()->findOrFail($providerA->id)->default_appointment_types)->toContain($type->id);
    expect(Provider::query()->findOrFail($providerB->id)->default_appointment_types)->not->toContain($type->id);
});

it('updates appointment type details and provider mapping from admin page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Old Treatment',
        'duration_minutes' => 20,
        'is_active' => true,
    ]);

    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => [$type->id]]);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);

    Livewire::actingAs($admin)
        ->test(AppointmentTypesPage::class)
        ->set('clinicId', $clinic->id)
        ->call('selectAppointmentType', $type->id)
        ->set('name', 'Updated Treatment')
        ->set('durationMinutes', 35)
        ->set('depositAmountCents', 4500)
        ->set('depositCurrency', 'eur')
        ->set('isMedical', true)
        ->set('selectedProviderIds', [$providerB->id])
        ->call('saveAppointmentType')
        ->assertSee('Appointment type saved.');

    expect($type->fresh()->name)->toBe('Updated Treatment');
    expect($type->fresh()->duration_minutes)->toBe(35);
    expect($type->fresh()->deposit_amount_cents)->toBe(4500);
    expect($type->fresh()->deposit_currency)->toBe('eur');
    expect($type->fresh()->is_medical)->toBeTrue();
    expect(Provider::query()->findOrFail($providerA->id)->default_appointment_types)->not->toContain($type->id);
    expect(Provider::query()->findOrFail($providerB->id)->default_appointment_types)->toContain($type->id);
});

it('deletes appointment type and removes provider mappings from admin page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => [$type->id]]);

    Livewire::actingAs($admin)
        ->test(AppointmentTypesPage::class)
        ->set('clinicId', $clinic->id)
        ->call('deleteAppointmentType', $type->id)
        ->assertSee('Appointment type deleted.');

    $this->assertDatabaseMissing('appointment_types', ['id' => $type->id]);
    expect(Provider::query()->findOrFail($provider->id)->default_appointment_types)->not->toContain($type->id);
});
