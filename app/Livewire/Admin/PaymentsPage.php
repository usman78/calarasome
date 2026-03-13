<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentPayment;
use App\Models\Clinic;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsPage extends Component
{
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var array<int, array<string,mixed>> */
    public array $payments = [];

    public string $clinicFilter = 'all';
    public string $statusFilter = 'all';

    public int $failedCount = 0;
    public int $canceledCount = 0;
    public int $disputedCount = 0;
    public int $refundedCount = 0;

    public function mount(): void
    {
        $this->ensureAdmin();

        $this->clinics = Clinic::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
            ])->all();

        $requestedClinicId = request()->integer('clinic_id');
        if ($requestedClinicId > 0) {
            $this->clinicFilter = (string) $requestedClinicId;
        }

        $this->loadPayments();
    }

    public function updatedClinicFilter(): void
    {
        $this->loadPayments();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadPayments();
    }

    public function exportCsv(): StreamedResponse
    {
        $this->ensureAdmin();

        $fileName = 'payments_export_'.now()->format('Ymd_His').'.csv';
        $query = $this->filteredQuery();

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Updated At',
                'Clinic',
                'Patient',
                'Email',
                'Status',
                'Strategy',
                'Amount',
                'Currency',
                'Provider',
                'Treatment',
                'Slot Local',
                'Timezone',
            ]);

            $query->chunk(200, function ($rows) use ($handle): void {
                foreach ($rows as $payment) {
                    $appointment = $payment->appointment;
                    $timezone = $appointment?->clinic?->timezone ?? 'UTC';
                    $slotLocal = $appointment?->slot_datetime
                        ? CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s')
                        : null;

                    fputcsv($handle, [
                        $payment->updated_at?->format('Y-m-d H:i:s'),
                        $appointment?->clinic?->name ?? 'Clinic',
                        $payment->patient?->full_name ?? 'Patient',
                        $payment->patient?->email ?? '',
                        $payment->status,
                        $payment->strategy,
                        number_format($payment->amount_cents / 100, 2, '.', ''),
                        strtoupper($payment->currency),
                        $appointment?->provider?->full_name ?? 'Provider',
                        $appointment?->appointmentType?->name ?? 'Appointment',
                        $slotLocal,
                        $timezone,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName);
    }

    private function loadPayments(): void
    {
        $query = $this->filteredQuery();
        $now = now();

        $baseCounts = AppointmentPayment::query();
        if ($this->clinicFilter !== 'all') {
            $baseCounts->whereHas('appointment', function ($sub): void {
                $sub->where('clinic_id', (int) $this->clinicFilter);
            });
        }

        $this->failedCount = (clone $baseCounts)->where('status', 'failed')->count();
        $this->canceledCount = (clone $baseCounts)->where('status', 'canceled')->count();
        $this->disputedCount = (clone $baseCounts)->where('status', 'disputed')->count();
        $this->refundedCount = (clone $baseCounts)->where('status', 'refunded')->count();

        $this->payments = $query
            ->limit(200)
            ->get()
            ->map(function (AppointmentPayment $payment) use ($now): array {
                $appointment = $payment->appointment;
                $timezone = $appointment?->clinic?->timezone ?? 'UTC';
                $slotLocal = $appointment?->slot_datetime
                    ? CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s')
                    : null;
                $isInGrace = in_array($payment->status, ['failed', 'canceled'], true)
                    && $payment->grace_expires_at
                    && $payment->grace_expires_at->greaterThan($now);

                return [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'strategy' => $payment->strategy,
                    'amount' => $payment->amount_cents,
                    'currency' => $payment->currency,
                    'updated_at' => $payment->updated_at?->format('Y-m-d H:i:s'),
                    'grace_expires_at' => $payment->grace_expires_at?->format('Y-m-d H:i:s'),
                    'is_in_grace' => $isInGrace,
                    'clinic' => $appointment?->clinic?->name ?? 'Clinic',
                    'provider' => $appointment?->provider?->full_name ?? 'Provider',
                    'appointment_type' => $appointment?->appointmentType?->name ?? 'Appointment',
                    'slot_local' => $slotLocal,
                    'timezone' => $timezone,
                    'patient' => $payment->patient?->full_name ?? 'Patient',
                    'email' => $payment->patient?->email ?? null,
                ];
            })->all();
    }

    private function filteredQuery()
    {
        $query = AppointmentPayment::query()
            ->with([
                'appointment:id,clinic_id,provider_id,appointment_type_id,slot_datetime',
                'appointment.clinic:id,name,timezone',
                'appointment.provider:id,full_name',
                'appointment.appointmentType:id,name',
                'patient:id,full_name,email',
            ])
            ->orderByDesc('updated_at');

        if ($this->clinicFilter !== 'all') {
            $query->whereHas('appointment', function ($sub): void {
                $sub->where('clinic_id', (int) $this->clinicFilter);
            });
        }

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'in_grace') {
                $query->whereIn('status', ['failed', 'canceled'])
                    ->whereNotNull('grace_expires_at')
                    ->where('grace_expires_at', '>', now());
            } elseif ($this->statusFilter === 'grace_expired') {
                $query->where(function ($sub): void {
                    $sub->where('status', 'grace_expired')
                        ->orWhere(function ($inner): void {
                            $inner->whereIn('status', ['failed', 'canceled'])
                                ->whereNotNull('grace_expires_at')
                                ->where('grace_expires_at', '<=', now());
                        });
                });
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.admin.payments-page');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
