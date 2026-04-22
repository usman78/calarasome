<?php

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use App\Policies\ProviderPolicy;

it('denies non-admin for all provider policy actions', function () {
    $policy = new ProviderPolicy();
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $provider = Provider::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $provider))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $provider))->toBeFalse();
    expect($policy->delete($user, $provider))->toBeFalse();
});

it('allows admin for provider actions within an accessible clinic', function () {
    $policy = new ProviderPolicy();
    $user = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create(['owner_id' => $user->id]);
    $provider = Provider::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $provider))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user, $provider))->toBeTrue();
    expect($policy->delete($user, $provider))->toBeTrue();
});

it('denies admin for provider actions outside an owned clinic', function () {
    $policy = new ProviderPolicy();
    $user = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $otherAdmin = User::factory()->admin()->create();
    $clinic->update(['owner_id' => $otherAdmin->id]);
    $provider = Provider::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->view($user, $provider))->toBeFalse();
    expect($policy->update($user, $provider))->toBeFalse();
    expect($policy->delete($user, $provider))->toBeFalse();
});
