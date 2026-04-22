<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function view(User $user, Provider $provider): bool
    {
        return $user->canManageClinicId($provider->clinic_id);
    }

    public function create(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function update(User $user, Provider $provider): bool
    {
        return $user->canManageClinicId($provider->clinic_id);
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->canManageClinicId($provider->clinic_id);
    }
}
