<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth on appointment-type admin index', function () {
    $clinic = Clinic::factory()->create();

    $this->getJson('/api/admin/appointment-types?clinic_id='.$clinic->id)
        ->assertStatus(401);
});

it('requires auth on appointment-type admin store', function () {
    $clinic = Clinic::factory()->create();

    $this->postJson('/api/admin/appointment-types', [
        'clinic_id' => $clinic->id,
        'name' => 'Consultation',
        'duration_minutes' => 30,
    ])->assertStatus(401);
});

it('requires auth on appointment-type admin update', function () {
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);

    $this->putJson('/api/admin/appointment-types/'.$type->id, [
        'name' => 'Updated Name',
    ])->assertStatus(401);
});

it('requires auth on appointment-type admin delete', function () {
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);

    $this->deleteJson('/api/admin/appointment-types/'.$type->id)
        ->assertStatus(401);
});

it('forbids non-admin on appointment-type admin index', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->getJson('/api/admin/appointment-types?clinic_id='.$clinic->id)
        ->assertStatus(403);
});

it('forbids non-admin on appointment-type admin store', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->postJson('/api/admin/appointment-types', [
            'clinic_id' => $clinic->id,
            'name' => 'Consultation',
            'duration_minutes' => 30,
        ])->assertStatus(403);
});

it('forbids non-admin on appointment-type admin update', function () {
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->putJson('/api/admin/appointment-types/'.$type->id, [
            'name' => 'Updated Name',
        ])->assertStatus(403);
});

it('forbids non-admin on appointment-type admin delete', function () {
    $clinic = Clinic::factory()->create();
    $type = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->deleteJson('/api/admin/appointment-types/'.$type->id)
        ->assertStatus(403);
});
