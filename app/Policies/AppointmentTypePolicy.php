<?php

namespace App\Policies;

use App\Models\AppointmentType;
use App\Models\User;

class AppointmentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function view(User $user, AppointmentType $appointmentType): bool
    {
        return $user->canManageClinicId($appointmentType->clinic_id);
    }

    public function create(User $user): bool
    {
        return $user->hasClinicManagementAccess();
    }

    public function update(User $user, AppointmentType $appointmentType): bool
    {
        return $user->canManageClinicId($appointmentType->clinic_id);
    }

    public function delete(User $user, AppointmentType $appointmentType): bool
    {
        return $user->canManageClinicId($appointmentType->clinic_id);
    }
}
