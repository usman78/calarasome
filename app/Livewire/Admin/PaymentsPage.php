<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentPayment;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Services\AppointmentPaymentService;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsPage extends Component
{
    use WithPagination;
    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    protected $payments = [];

    public string $clinicFilter = 'all';
    public string $statusFilter = 'all';
    public string $search = '';
    public int $perPage = 20;

    public int $failedCount = 0;
    public int $canceledCount = 0;
    public int $disputedCount = 0;
    public int $refundedCount = 0;
    public ?string $actionError = null;
    public ?string $actionMessage = null;

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

    public function cancelAppointment(int $appointmentId): void
    {
        $this->ensureAdmin();
        $this->actionError = null;
        $this->actionMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $paymentService = app(AppointmentPaymentService::class);

        try {
            $result = $paymentService->cancelByClinic($appointment);
            $this->actionMessage = "Appointment cancelled ({$result['payment_action']}).";
        } catch (RuntimeException $exception) {
            $this->actionError = $exception->getMessage();
        }

        $this->loadPayments();
    }

    public function markNoShow(int $appointmentId, bool $chargeDeposit = true): void
    {
        $this->ensureAdmin();
        $this->actionError = null;
        $this->actionMessage = null;

        $appointment = Appointment::query()->findOrFail($appointmentId);
        $paymentService = app(AppointmentPaymentService::class);

        try {
            $result = $paymentService->markNoShow($appointment, $chargeDeposit);
            $this->actionMessage = "Appointment marked no-show ({$result['payment_action']}).";
        } catch (RuntimeException $exception) {
            $this->actionError = $exception->getMessage();
        }

        $this->loadPayments();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadPayments();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadPayments();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
            ->paginate($this->perPage)
            ->through(function (AppointmentPayment $payment) use ($now): array {
                $appointment = $payment->appointment;
                $timezone = $appointment?->clinic?->timezone ?? 'UTC';
                $slotLocal = $appointment?->slot_datetime
                    ? CarbonImmutable::parse($appointment->slot_datetime)->setTimezone($timezone)->format('Y-m-d H:i:s')
                    : null;
                $isInGrace = in_array($payment->status, ['failed', 'canceled'], true)
                    && $payment->grace_expires_at
                    && $payment->grace_expires_at->greaterThan($now);
                $canNoShow = $appointment?->slot_datetime
                    ? $appointment->slot_datetime->lessThan($now->copy()->utc())
                    : false;

                return [
                    'id' => $payment->id,
                    'appointment_id' => $appointment?->id,
                    'appointment_status' => $appointment?->status ?? 'unknown',
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
                    'can_no_show' => $canNoShow,
                ];
            });
    }

    private function filteredQuery()
    {
        $query = AppointmentPayment::query()
            ->with([
                'appointment:id,clinic_id,provider_id,appointment_type_id,slot_datetime,status',
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

        if ($this->search) {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereHas('patient', function ($patientSub) use ($search): void {
                    $patientSub->whereRaw('LOWER(full_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                })->orWhereHas('appointment.provider', function ($providerSub) use ($search): void {
                    $providerSub->whereRaw('LOWER(full_name) LIKE ?', [$search]);
                })->orWhereHas('appointment.appointmentType', function ($typeSub) use ($search): void {
                    $typeSub->whereRaw('LOWER(name) LIKE ?', [$search]);
                });
            });
        }

        return $query;
    }

    public function render()
    {
        $this->loadPayments();

        return view('livewire.admin.payments-page', [
            'payments' => $this->payments,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
