<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Appointments</flux:heading>
                <flux:subheading>Review upcoming, past, and cancelled appointments at a glance.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-52">
                    <flux:select wire:model.live="clinicFilter" label="Clinic">
                        <flux:select.option value="all">All clinics</flux:select.option>
                        @foreach ($clinics as $clinic)
                            <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-40">
                    <flux:select wire:model.live="dateFilter" label="Date">
                        <flux:select.option value="today">Today</flux:select.option>
                        <flux:select.option value="future">Future</flux:select.option>
                        <flux:select.option value="past">Past</flux:select.option>
                        <flux:select.option value="next7">Next 7 Days</flux:select.option>
                        <flux:select.option value="all">All</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-44">
                    <flux:select wire:model.live="statusFilter" label="Status">
                        <flux:select.option value="all">All</flux:select.option>
                        <flux:select.option value="confirmed">Confirmed</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="cancelled_by_patient">Canceled (Patient)</flux:select.option>
                        <flux:select.option value="cancelled_by_clinic">Canceled (Clinic)</flux:select.option>
                        <flux:select.option value="no_show">No-Show</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, provider, type..." />
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-3 sm:gap-3">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Today</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $todayCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Upcoming</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $futureCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Past</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $pastCount }}</div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
        <div wire:loading.flex wire:target="clinicFilter,dateFilter,statusFilter,search" class="mb-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
            <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
            Updating appointments...
        </div>

        @if ($appointments instanceof \Illuminate\Pagination\LengthAwarePaginator ? $appointments->isEmpty() : empty($appointments))
            <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                No appointments match the current filters.
            </div>
        @else
            <div class="overflow-auto -mx-4 sm:mx-0 max-h-[70vh] rounded-lg border border-zinc-200/60 bg-white dark:border-zinc-800/70 dark:bg-zinc-950">
                <div class="w-full">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                        <tr class="border-b border-zinc-200 text-left text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Date/Time</th>
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Patient</th>
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Provider</th>
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Type</th>
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Status</th>
                            <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($appointments as $appointment)
                            @php
                                $statusBadge = [
                                    'confirmed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                                    'completed' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200',
                                    'cancelled_by_patient' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
                                    'cancelled_by_clinic' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
                                    'no_show' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                ][$appointment['status']] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200';
                            @endphp
                            <tr class="border-b border-zinc-100 align-top dark:border-zinc-800">
                                <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                    <div class="font-medium">{{ $appointment['slot_local'] ?? 'TBD' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $appointment['timezone'] }}</div>
                                    <button
                                        type="button"
                                        class="mt-2 text-xs text-zinc-500 underline hover:text-zinc-800"
                                        wire:click="toggleDetails({{ $appointment['id'] }})"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ ($openDetails[$appointment['id']] ?? false) ? 'Hide details' : 'View details' }}
                                    </button>
                                </td>
                                <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                    <div class="font-medium">{{ $appointment['patient'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $appointment['email'] }}</div>
                                    @if ($appointment['phone'])
                                        <div class="text-xs text-zinc-500">{{ $appointment['phone'] }}</div>
                                    @endif
                                </td>
                                <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $appointment['provider'] }}</td>
                                <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $appointment['appointment_type'] }}</td>
                                <td class="px-2.5 py-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $statusBadge }}">
                                        {{ str_replace('_', ' ', $appointment['status']) }}
                                    </span>
                                    @if ($appointment['payment_status'])
                                        <div class="mt-1 text-xs text-zinc-500">Payment: {{ $appointment['payment_status'] }}</div>
                                    @endif
                                </td>
                                <td class="px-2.5 py-2">
                                    <div class="flex flex-col gap-2">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :disabled="! $appointment['can_cancel']"
                                            x-on:click.prevent="confirm('Cancel this appointment? This will apply the clinic policy.') && $wire.cancelAppointment({{ $appointment['id'] }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Cancel
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :disabled="! $appointment['can_no_show']"
                                            x-on:click.prevent="confirm('Mark this appointment as no-show? Deposit may be captured.') && $wire.markNoShow({{ $appointment['id'] }})"
                                            wire:loading.attr="disabled"
                                        >
                                            No-Show
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                            @if ($openDetails[$appointment['id']] ?? false)
                                <tr class="border-b border-zinc-100 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-900/40">
                                    <td colspan="6" class="px-3 py-3 text-xs text-zinc-600 dark:text-zinc-300">
                                        <div class="grid gap-3 md:grid-cols-3">
                                            <div>
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500">Clinic</div>
                                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $appointment['clinic'] }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500">Payment</div>
                                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $appointment['payment_status'] ?? 'none' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500">Insurance</div>
                                                <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                                    {{ $appointment['insurance_status'] ?? 'not required' }}
                                                </div>
                                            </div>
                                            @if ($appointment['insurance_status'])
                                                <div>
                                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Insurance Provider</div>
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $appointment['insurance_provider'] ?? 'N/A' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Member ID</div>
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $appointment['insurance_member_id'] ?? 'N/A' }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] uppercase tracking-wide text-zinc-500">Urgency</div>
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $appointment['insurance_urgency'] ?? 'standard' }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($appointments instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
</div>
