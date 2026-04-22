<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitlistEntry;

class WaitlistEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function view(User $user, WaitlistEntry $waitlistEntry): bool
    {
        return $user->canManageClinicId($waitlistEntry->clinic_id);
    }
}
