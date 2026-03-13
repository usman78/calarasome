<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentPayment;
use Livewire\Component;

class PaymentAlertsBanner extends Component
{
    public int $inGraceCount = 0;
    public int $expiredCount = 0;

    public function mount(): void
    {
        $this->ensureAdmin();
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.admin.payment-alerts-banner');
    }

    private function loadCounts(): void
    {
        $this->inGraceCount = AppointmentPayment::query()
            ->whereIn('status', ['failed', 'canceled'])
            ->whereNotNull('grace_expires_at')
            ->where('grace_expires_at', '>', now())
            ->count();

        $this->expiredCount = AppointmentPayment::query()
            ->where('status', 'grace_expired')
            ->count();
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
