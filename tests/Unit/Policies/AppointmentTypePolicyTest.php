<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\User;
use App\Policies\AppointmentTypePolicy;

it('denies non-admin for all appointment type policy actions', function () {
    $policy = new AppointmentTypePolicy();
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $type = AppointmentType::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $type))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $type))->toBeFalse();
    expect($policy->delete($user, $type))->toBeFalse();
});

it('allows admin for appointment type actions within an accessible clinic', function () {
    $policy = new AppointmentTypePolicy();
    $user = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create(['owner_id' => $user->id]);
    $type = AppointmentType::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $type))->toBeTrue();
    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user, $type))->toBeTrue();
    expect($policy->delete($user, $type))->toBeTrue();
});

it('denies admin for appointment type actions outside an owned clinic', function () {
    $policy = new AppointmentTypePolicy();
    $user = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $otherAdmin = User::factory()->admin()->create();
    $clinic->update(['owner_id' => $otherAdmin->id]);
    $type = AppointmentType::factory()->make(['clinic_id' => $clinic->id]);

    expect($policy->view($user, $type))->toBeFalse();
    expect($policy->update($user, $type))->toBeFalse();
    expect($policy->delete($user, $type))->toBeFalse();
});
