<?php

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\ProviderBlockedTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires auth on provider admin index', function () {
    $clinic = Clinic::factory()->create();

    $this->getJson('/api/admin/providers?clinic_id='.$clinic->id)
        ->assertStatus(401);
});

it('requires auth on provider admin store', function () {
    $clinic = Clinic::factory()->create();

    $this->postJson('/api/admin/providers', [
        'clinic_id' => $clinic->id,
        'full_name' => 'Dr Guest',
    ])->assertStatus(401);
});

it('requires auth on provider admin update', function () {
    $provider = Provider::factory()->create();

    $this->putJson('/api/admin/providers/'.$provider->id, [
        'full_name' => 'Updated',
    ])->assertStatus(401);
});

it('requires auth on provider admin delete', function () {
    $provider = Provider::factory()->create();

    $this->deleteJson('/api/admin/providers/'.$provider->id)
        ->assertStatus(401);
});

it('requires auth on provider admin schedule index', function () {
    $provider = Provider::factory()->create();

    $this->getJson('/api/admin/providers/'.$provider->id.'/schedule')
        ->assertStatus(401);
});

it('requires auth on provider admin schedule update', function () {
    $provider = Provider::factory()->create();

    $this->putJson('/api/admin/providers/'.$provider->id.'/schedule', [
        'schedules' => [[
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]],
    ])->assertStatus(401);
});

it('requires auth on provider admin block store', function () {
    $provider = Provider::factory()->create();

    $this->postJson('/api/admin/providers/'.$provider->id.'/block-time', [
        'start_datetime' => now()->addDay()->toDateTimeString(),
        'end_datetime' => now()->addDay()->addHour()->toDateTimeString(),
        'reason' => 'Vacation',
    ])->assertStatus(401);
});

it('requires auth on provider admin block delete', function () {
    $provider = Provider::factory()->create();
    $block = ProviderBlockedTime::factory()->create(['provider_id' => $provider->id]);

    $this->deleteJson('/api/admin/providers/'.$provider->id.'/block-time/'.$block->id)
        ->assertStatus(401);
});

it('forbids non-admin on provider admin index', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->getJson('/api/admin/providers?clinic_id='.$clinic->id)
        ->assertStatus(403);
});

it('forbids non-admin on provider admin store', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->postJson('/api/admin/providers', [
            'clinic_id' => $clinic->id,
            'full_name' => 'Dr NoAdmin',
        ])->assertStatus(403);
});

it('forbids non-admin on provider admin update', function () {
    $provider = Provider::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->putJson('/api/admin/providers/'.$provider->id, [
            'full_name' => 'Updated',
        ])->assertStatus(403);
});

it('forbids non-admin on provider admin delete', function () {
    $provider = Provider::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->deleteJson('/api/admin/providers/'.$provider->id)
        ->assertStatus(403);
});

it('forbids non-admin on provider admin schedule index', function () {
    $provider = Provider::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->getJson('/api/admin/providers/'.$provider->id.'/schedule')
        ->assertStatus(403);
});

it('forbids non-admin on provider admin schedule update', function () {
    $provider = Provider::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->putJson('/api/admin/providers/'.$provider->id.'/schedule', [
            'schedules' => [[
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
            ]],
        ])->assertStatus(403);
});

it('forbids non-admin on provider admin block store', function () {
    $provider = Provider::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->postJson('/api/admin/providers/'.$provider->id.'/block-time', [
            'start_datetime' => now()->addDay()->toDateTimeString(),
            'end_datetime' => now()->addDay()->addHour()->toDateTimeString(),
            'reason' => 'Vacation',
        ])->assertStatus(403);
});

it('forbids non-admin on provider admin block delete', function () {
    $provider = Provider::factory()->create();
    $block = ProviderBlockedTime::factory()->create(['provider_id' => $provider->id]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->deleteJson('/api/admin/providers/'.$provider->id.'/block-time/'.$block->id)
        ->assertStatus(403);
});
