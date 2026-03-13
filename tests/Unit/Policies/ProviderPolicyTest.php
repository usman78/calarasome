<?php

use App\Models\Provider;
use App\Models\User;
use App\Policies\ProviderPolicy;

it('denies non-admin for all provider policy actions', function () {
    $policy = new ProviderPolicy();
    $user = new User(['is_admin' => false]);
    $provider = new Provider();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $provider))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $provider))->toBeFalse();
    expect($policy->delete($user, $provider))->toBeFalse();
});

it('allows admin for all provider policy actions', function () {
    $policy = new ProviderPolicy();
    $user = new User(['is_admin' => true]);
    $provider = new Provider();

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $provider))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user, $provider))->toBeTrue();
    expect($policy->delete($user, $provider))->toBeTrue();
});
