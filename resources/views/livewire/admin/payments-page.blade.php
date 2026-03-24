<div class="mx-auto w-full max-w-7xl space-y-6">
    <livewire:admin.payment-alerts-banner />
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Payments Monitor</flux:heading>
                <flux:subheading>Track failed, canceled, disputed, and refunded deposits.</flux:subheading>
            </div>
            <div class="grid w-full gap-3 md:w-auto md:grid-cols-3">
                <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, provider, treatment..." />
                <flux:select wire:model.live="clinicFilter" label="Clinic">
                    <flux:select.option value="all">All clinics</flux:select.option>
                    @foreach ($clinics as $clinic)
                        <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="statusFilter" label="Status">
                    <flux:select.option value="all">All statuses</flux:select.option>
                    <flux:select.option value="in_grace">In Grace</flux:select.option>
                    <flux:select.option value="grace_expired">Grace Expired</flux:select.option>
                    <flux:select.option value="failed">Failed</flux:select.option>
                    <flux:select.option value="canceled">Canceled</flux:select.option>
                    <flux:select.option value="disputed">Disputed</flux:select.option>
                    <flux:select.option value="refunded">Refunded</flux:select.option>
                </flux:select>
            </div>
            <div class="w-full md:w-auto">
                <flux:button
                    variant="filled"
                    size="sm"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv,clinicFilter,statusFilter"
                >
                    Export CSV
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-xs">
            <div class="text-xs uppercase tracking-wide text-red-700">Failed</div>
            <div class="mt-1 text-2xl font-semibold text-red-800">{{ $failedCount }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-xs">
            <div class="text-xs uppercase tracking-wide text-amber-700">Canceled</div>
            <div class="mt-1 text-2xl font-semibold text-amber-800">{{ $canceledCount }}</div>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 shadow-xs">
            <div class="text-xs uppercase tracking-wide text-purple-700">Disputed</div>
            <div class="mt-1 text-2xl font-semibold text-purple-800">{{ $disputedCount }}</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-xs">
            <div class="text-xs uppercase tracking-wide text-blue-700">Refunded</div>
            <div class="mt-1 text-2xl font-semibold text-blue-800">{{ $refundedCount }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
        @if ($actionError)
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $actionError }}
            </div>
        @elseif ($actionMessage)
            <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ $actionMessage }}
            </div>
        @endif
        <div wire:loading.flex wire:target="clinicFilter,statusFilter,search" class="mb-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
            <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
            Updating payments...
        </div>

        <div class="overflow-auto -mx-4 sm:mx-0 max-h-[70vh] rounded-lg border border-zinc-200/60 bg-white dark:border-zinc-800/70 dark:bg-zinc-950">
            <div class="min-w-full px-4 sm:px-0">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead>
                    <tr class="border-b border-zinc-200 text-left text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Updated</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Clinic</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Patient</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Status</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Strategy</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Amount</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Provider</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Treatment</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Slot</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Grace Expires</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $status = $payment['status'] ?? 'unknown';
                            $statusStyles = match ($status) {
                                'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                'grace_expired' => 'bg-red-200 text-red-900 dark:bg-red-900/50 dark:text-red-100',
                                'canceled' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
                                'disputed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200',
                                'refunded' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
                                'succeeded' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                                'captured' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                                'voided' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
                            };
                            $appointmentStatus = $payment['appointment_status'] ?? 'unknown';
                            $isFinalStatus = in_array($appointmentStatus, ['cancelled_by_patient', 'cancelled_by_clinic', 'no_show'], true);
                            $amountFormatted = number_format($payment['amount'] / 100, 2).' '.strtoupper($payment['currency']);
                            $slotLabel = $payment['slot_local'] ?: 'n/a';
                            $cancelMessage = "Cancel appointment for {$payment['patient']}? This will void/release any deposit hold (or refund if already captured).";
                            $noShowMessage = "Mark no-show for {$payment['patient']} ({$slotLabel})? Capturing {$amountFormatted} deposit hold. This will notify the patient and increment no-show count.";
                        @endphp
                        <tr class="border-b border-zinc-100 align-top dark:border-zinc-800">
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $payment['updated_at'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $payment['clinic'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                <div class="font-medium">{{ $payment['patient'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $payment['email'] ?: 'n/a' }}</div>
                            </td>
                            <td class="px-2.5 py-2">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $statusStyles }}">
                                        {{ $payment['status'] }}
                                    </span>
                                    @if ($payment['is_in_grace'])
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                                            in grace
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $payment['strategy'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                {{ number_format($payment['amount'] / 100, 2) }} {{ strtoupper($payment['currency']) }}
                            </td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $payment['provider'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $payment['appointment_type'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                <div>{{ $payment['slot_local'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $payment['timezone'] }}</div>
                            </td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                {{ $payment['grace_expires_at'] ?: '-' }}
                            </td>
                            <td class="px-2.5 py-2">
                                <div class="flex flex-col gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        @if ($isFinalStatus || ! $payment['appointment_id'])
                                            disabled
                                        @else
                                            x-data
                                            @click="confirm(@js($cancelMessage)) && $wire.cancelAppointment({{ $payment['appointment_id'] }})"
                                        @endif
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-amber-300 px-2.5 py-1 text-xs font-semibold text-amber-800 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-amber-700 dark:text-amber-200 dark:hover:bg-amber-900/20"
                                        @if ($isFinalStatus || ! $payment['appointment_id'])
                                            disabled
                                        @else
                                            x-data
                                            @click="confirm(@js($noShowMessage)) && $wire.markNoShow({{ $payment['appointment_id'] }}, true)"
                                        @endif
                                    >
                                        Mark No-Show
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No payment records match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        @if ($payments instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
