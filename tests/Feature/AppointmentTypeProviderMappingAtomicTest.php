<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use App\Services\AppointmentTypeProviderMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores appointment type and syncs provider mappings', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();

    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);
    $providerC = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);

    $response = $this->actingAs($admin)->postJson('/api/admin/appointment-types', [
        'clinic_id' => $clinic->id,
        'name' => 'Initial Consult',
        'duration_minutes' => 30,
        'providerIds' => [$providerA->id, $providerC->id],
    ]);

    $response->assertCreated();
    $typeId = (int) $response->json('id');

    expect(Provider::query()->findOrFail($providerA->id)->default_appointment_types)->toContain($typeId);
    expect(Provider::query()->findOrFail($providerB->id)->default_appointment_types)->not->toContain($typeId);
    expect(Provider::query()->findOrFail($providerC->id)->default_appointment_types)->toContain($typeId);
});

it('updates appointment type and re-syncs provider mappings', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'name' => 'Old Name']);

    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => [$type->id]]);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);

    $response = $this->actingAs($admin)->putJson('/api/admin/appointment-types/'.$type->id, [
        'name' => 'New Name',
        'providerIds' => [$providerB->id],
    ]);

    $response->assertOk();
    expect($type->fresh()->name)->toBe('New Name');
    expect(Provider::query()->findOrFail($providerA->id)->default_appointment_types)->not->toContain($type->id);
    expect(Provider::query()->findOrFail($providerB->id)->default_appointment_types)->toContain($type->id);
});

it('deletes appointment type and removes mappings from providers', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => [$type->id]]);

    $this->actingAs($admin)->deleteJson('/api/admin/appointment-types/'.$type->id)
        ->assertNoContent();

    $this->assertDatabaseMissing('appointment_types', ['id' => $type->id]);
    expect(Provider::query()->findOrFail($provider->id)->default_appointment_types)->not->toContain($type->id);
});

it('rolls back appointment type create when provider mapping sync fails', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $this->app->instance(AppointmentTypeProviderMappingService::class, new class extends AppointmentTypeProviderMappingService
    {
        public function syncClinicProviders(int $clinicId, int $appointmentTypeId, array $providerIds): void
        {
            throw new RuntimeException('forced mapping failure');
        }
    });

    $this->actingAs($admin)->postJson('/api/admin/appointment-types', [
        'clinic_id' => $clinic->id,
        'name' => 'Rollback Test',
        'duration_minutes' => 30,
        'providerIds' => [$provider->id],
    ])->assertStatus(500);

    $this->assertDatabaseMissing('appointment_types', [
        'clinic_id' => $clinic->id,
        'name' => 'Rollback Test',
    ]);
});

it('rolls back appointment type update when provider mapping sync fails', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Stable Name',
        'duration_minutes' => 20,
    ]);

    $providerA = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => [$type->id]]);
    $providerB = Provider::factory()->create(['clinic_id' => $clinic->id, 'default_appointment_types' => []]);

    $this->app->instance(AppointmentTypeProviderMappingService::class, new class extends AppointmentTypeProviderMappingService
    {
        public function syncClinicProviders(int $clinicId, int $appointmentTypeId, array $providerIds): void
        {
            throw new RuntimeException('forced mapping failure');
        }
    });

    $this->actingAs($admin)->putJson('/api/admin/appointment-types/'.$type->id, [
        'name' => 'Should Rollback',
        'providerIds' => [$providerB->id],
    ])->assertStatus(500);

    expect($type->fresh()->name)->toBe('Stable Name');
    expect(Provider::query()->findOrFail($providerA->id)->default_appointment_types)->toContain($type->id);
    expect(Provider::query()->findOrFail($providerB->id)->default_appointment_types)->not->toContain($type->id);
});

it('validates provider ids belong to the same clinic for mapping', function () {
    $admin = User::factory()->admin()->create();
    $clinicA = Clinic::factory()->create();
    $clinicB = Clinic::factory()->create();
    $outsideProvider = Provider::factory()->create(['clinic_id' => $clinicB->id]);

    $this->actingAs($admin)->postJson('/api/admin/appointment-types', [
        'clinic_id' => $clinicA->id,
        'name' => 'Clinic Bound Type',
        'duration_minutes' => 30,
        'providerIds' => [$outsideProvider->id],
    ])->assertStatus(422)->assertJsonValidationErrors('providerIds.0');

    $this->assertDatabaseMissing('appointment_types', [
        'clinic_id' => $clinicA->id,
        'name' => 'Clinic Bound Type',
    ]);
});
