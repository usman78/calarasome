<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitlistEntry;

class WaitlistEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }
}
