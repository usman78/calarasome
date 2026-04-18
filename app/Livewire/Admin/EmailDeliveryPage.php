<?php

namespace App\Livewire\Admin;

use App\Models\Clinic;
use App\Models\EmailDeliveryLog;
use Livewire\Component;
use Livewire\WithPagination;

class EmailDeliveryPage extends Component
{
    use WithPagination;

    /** @var array<int, array{id:int,name:string}> */
    public array $clinics = [];

    /** @var \Illuminate\Pagination\LengthAwarePaginator|array<int, array<string,mixed>> */
    protected $logs = [];

    public string $clinicFilter = 'all';
    public string $statusFilter = 'failed';
    public string $search = '';
    public int $perPage = 15;

    public int $failedCount = 0;
    public int $sentCount = 0;
    public int $skippedCount = 0;

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

        $this->loadLogs();
    }

    public function updatedClinicFilter(): void
    {
        $this->resetPage();
        $this->loadLogs();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->loadLogs();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadLogs();
    }

    public function markResolved(int $logId): void
    {
        $this->ensureAdmin();

        $log = EmailDeliveryLog::query()->findOrFail($logId);
        $log->update(['resolved_at' => now()]);

        $this->dispatch('toast', type: 'success', message: 'Email delivery issue marked as resolved.');
        $this->loadLogs();
    }

    private function loadLogs(): void
    {
        $query = EmailDeliveryLog::query()
            ->with(['clinic:id,name', 'patient:id,full_name,email'])
            ->orderByDesc('created_at');

        if ($this->clinicFilter !== 'all') {
            $query->where('clinic_id', (int) $this->clinicFilter);
        }

        if ($this->search !== '') {
            $search = '%'.strtolower($this->search).'%';
            $query->where(function ($sub) use ($search): void {
                $sub->whereRaw('LOWER(recipient_email) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(mailable) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(context_type) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(failure_reason, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(failure_message, \'\')) LIKE ?', [$search]);
            });
        }

        $this->failedCount = (clone $query)->where('status', 'failed')->whereNull('resolved_at')->count();
        $this->sentCount = (clone $query)->where('status', 'sent')->count();
        $this->skippedCount = (clone $query)->where('status', 'skipped')->count();

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'failed') {
                $query->where('status', 'failed')->whereNull('resolved_at');
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        $this->logs = $query
            ->paginate($this->perPage)
            ->through(fn (EmailDeliveryLog $log): array => [
                'id' => $log->id,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                'clinic' => $log->clinic?->name ?? 'System',
                'patient' => $log->patient?->full_name,
                'recipient_email' => $log->recipient_email,
                'mailable' => class_basename($log->mailable),
                'status' => $log->status,
                'context' => $this->contextLabel($log),
                'failure_reason' => $log->failure_reason,
                'failure_message' => $log->failure_message,
                'suggested_action' => $log->suggested_action,
                'resolved_at' => $log->resolved_at?->format('Y-m-d H:i:s'),
            ]);
    }

    private function contextLabel(EmailDeliveryLog $log): string
    {
        $label = str_replace('_', ' ', $log->context_type ?? 'message');
        $label = ucwords($label);

        if ($log->context_id) {
            return $label.' #'.$log->context_id;
        }

        return $label;
    }

    public function render()
    {
        $this->loadLogs();

        return view('livewire.admin.email-delivery-page', [
            'logs' => $this->logs,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Admin access required.');
    }
}
