<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentPayment;
use App\Models\InsuranceVerification;
use App\Models\PatientMatchAlert;
use Livewire\Component;

class DashboardAlerts extends Component
{
    public int $paymentsInGrace = 0;
    public int $paymentsExpired = 0;
    public int $insuranceUrgent = 0;
    public int $matchAlerts = 0;

    public function mount(): void
    {
        $this->ensureAdmin();
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.admin.dashboard-alerts');
    }

    private function loadCounts(): void
    {
        $this->paymentsInGrace = AppointmentPayment::query()
            ->whereIn('status', ['failed', 'canceled'])
            ->whereNotNull('grace_expires_at')
            ->where('grace_expires_at', '>', now())
            ->count();

        $this->paymentsExpired = AppointmentPayment::query()
            ->where('status', 'grace_expired')
            ->count();

        $this->insuranceUrgent = InsuranceVerification::query()
            ->where('status', 'pending')
            ->whereIn('urgency', ['critical', 'high'])
            ->count();

        $this->matchAlerts = PatientMatchAlert::query()
            ->whereNull('resolved_at')
            ->count();
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
