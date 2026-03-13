<?php

use App\Models\AppointmentType;
use App\Models\User;
use App\Policies\AppointmentTypePolicy;

it('denies non-admin for all appointment type policy actions', function () {
    $policy = new AppointmentTypePolicy();
    $user = new User(['is_admin' => false]);
    $type = new AppointmentType();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $type))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $type))->toBeFalse();
    expect($policy->delete($user, $type))->toBeFalse();
});

it('allows admin for all appointment type policy actions', function () {
    $policy = new AppointmentTypePolicy();
    $user = new User(['is_admin' => true]);
    $type = new AppointmentType();

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $type))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user, $type))->toBeTrue();
    expect($policy->delete($user, $type))->toBeTrue();
});
