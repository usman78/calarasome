<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Provider $provider): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Provider $provider): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->is_admin;
    }
}
