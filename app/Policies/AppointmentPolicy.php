<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->canManageClinicId($appointment->clinic_id);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->canManageClinicId($appointment->clinic_id);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->canManageClinicId($appointment->clinic_id);
    }
}
