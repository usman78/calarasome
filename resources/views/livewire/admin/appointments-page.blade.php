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

        <div class="mt-4 flex flex-wrap gap-2 sm:gap-3">
            @foreach(['Today' => $todayCount, 'Upcoming' => $futureCount, 'Past' => $pastCount] as $label => $count)
                <div class="flex-1 min-w-[120px] rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 ring-1 ring-inset ring-zinc-200/50 dark:ring-zinc-800/60">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 dark:text-zinc-400 opacity-80">{{ $label }}</div>
                    <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white">{{ $count }}</div>
                </div>
            @endforeach
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-2 mb-4 content-start">
                @foreach ($appointments as $appointment)
                    @php
                        $statusBadge = [
                            'confirmed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'completed' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                            'cancelled_by_patient' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'cancelled_by_clinic' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'no_show' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        ][$appointment['status']] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
                        
                        $statusDot = [
                            'confirmed' => 'bg-emerald-500',
                            'completed' => 'bg-zinc-500',
                            'cancelled_by_patient' => 'bg-amber-500',
                            'cancelled_by_clinic' => 'bg-amber-500',
                            'no_show' => 'bg-red-500',
                        ][$appointment['status']] ?? 'bg-zinc-500';
                    @endphp

                    <div class="group relative flex flex-col rounded-xl bg-white p-3 shadow-sm ring-1 ring-zinc-200/60 transition-all hover:shadow-md dark:bg-zinc-950 dark:ring-zinc-800/70 h-full overflow-hidden">
                        <div class="absolute bottom-0 left-0 top-0 w-1 rounded-l-xl {{ $statusDot }}"></div>

                        {{-- Card Header --}}
                        <div class="border-b border-zinc-100 pb-5 dark:border-zinc-800/60 mb-2">
                             <div class="flex items-center justify-between mb-1.5">
                                <span class="block text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest opacity-80">
                                    {{ $appointment['slot_local'] ?? 'TBD' }}
                                    @if($appointment['timezone'])
                                        <span class="font-normal text-zinc-400 lowercase"> &bull; {{ $appointment['timezone'] }}</span>
                                    @endif
                                </span>
                             </div>
                             <div class="flex items-start justify-between gap-2">
                                <h3 class="font-heading text-xl font-bold tracking-tight text-zinc-900 dark:text-white truncate lg:max-w-[200px] xl:max-w-none">{{ $appointment['patient'] }}</h3>
                                <span class="flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest {{ $statusBadge }}">
                                    <span class="size-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ str_replace('_', ' ', $appointment['status']) }}
                                </span>
                             </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="flex-grow flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <span class="p-1.5 rounded-md bg-zinc-50 dark:bg-zinc-900 text-zinc-400 mr-2">
                                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                                </span>
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 truncate">{{ $appointment['email'] }}</span>
                            </div>
                            @if ($appointment['phone'])
                                <div class="flex items-center gap-3">
                                    <span class="p-1.5 rounded-md bg-zinc-50 dark:bg-zinc-900 text-zinc-400 mr-2">
                                        <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.896-1.595-5.22-3.919-6.814-6.815l1.292-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                    </span>
                                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 truncate">{{ $appointment['phone'] }}</span>
                                </div>
                            @endif
                            <div class="mt-1 flex items-center gap-3">
                                <span class="p-1.5 rounded-md bg-zinc-50 dark:bg-zinc-900 text-zinc-400 mr-2">
                                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                                </span>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $appointment['provider'] }}</span>
                                    <span class="text-[10px] font-medium text-zinc-500 uppercase tracking-tight">{{ $appointment['appointment_type'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-7 dark:border-zinc-800/60">
                            <div class="flex items-center gap-3 mt-2">
                                @if ($appointment['payment_status'])
                                    <span class="rounded-lg bg-zinc-50 border border-zinc-100/50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-tight text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400 dark:border-zinc-800">
                                        {{ str_replace('_', ' ', $appointment['payment_status']) }}
                                    </span>
                                @else
                                    <span class="rounded-lg bg-zinc-50 border border-zinc-100/50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-tight text-zinc-400 dark:bg-zinc-900 dark:text-zinc-500 dark:border-zinc-800">
                                        Unpaid
                                    </span>
                                @endif
                                <button
                                    type="button"
                                    class="text-xs font-bold text-primary hover:underline focus:outline-none dark:text-primary-400"
                                    wire:click="toggleDetails({{ $appointment['id'] }})"
                                    wire:loading.attr="disabled"
                                >
                                    {{ ($openDetails[$appointment['id']] ?? false) ? 'Hide details' : 'View details' }}
                                </button>
                            </div>

                            <div class="flex items-center gap-2 pr-1">
                                <flux:dropdown>
                                    <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" class="!px-2" />
                                    <flux:menu>
                                        <flux:menu.item
                                            :disabled="! $appointment['can_cancel']"
                                            x-on:click="confirm('Cancel this appointment? This will apply the clinic policy.') && $wire.cancelAppointment({{ $appointment['id'] }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Cancel Appointment
                                        </flux:menu.item>
                                        <flux:menu.item
                                            variant="danger"
                                            :disabled="! $appointment['can_no_show']"
                                            x-on:click="confirm('Mark this appointment as no-show? Deposit may be captured.') && $wire.markNoShow({{ $appointment['id'] }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Mark No-Show
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>

                        {{-- Expanded Details --}}
                        @if ($openDetails[$appointment['id']] ?? false)
                            <div class="mt-6 border-t border-zinc-100 pt-10 grid gap-5 grid-cols-2 dark:border-zinc-800/60 bg-zinc-50/15 rounded-b-xl pb-10">
                                <div class="col-span-2 mb-3">
                                    <div class="mb-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">Clinic</div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100 text-sm">
                                        {{ $appointment['clinic'] }}
                                    </div>
                                </div>

                                @if ($appointment['status'] === 'no_show')
                                    <div class="col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-100">
                                        <div class="font-semibold">No-show reversal</div>
                                        @if ($appointment['can_undo_no_show'])
                                            <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
                                                Quick undo available until {{ $appointment['undo_window_ends'] }} ({{ $appointment['timezone'] }}).
                                            </p>
                                            <div class="mt-3">
                                                <flux:button
                                                    size="sm"
                                                    variant="primary"
                                                    x-on:click="confirm('Undo this no-show and refund any captured deposit?') && $wire.undoNoShow({{ $appointment['id'] }})"
                                                    wire:loading.attr="disabled"
                                                >
                                                    Undo No-Show
                                                </flux:button>
                                            </div>
                                        @elseif ($appointment['requires_reasoned_reversal'])
                                            <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
                                                The quick undo window has closed. Reversal now requires a reason and creates an audit trail.
                                            </p>
                                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                <div>
                                                    <flux:select wire:model="reversalReasons.{{ $appointment['id'] }}" label="Reversal Reason">
                                                        <flux:select.option value="">Select a reason</flux:select.option>
                                                        <flux:select.option value="marked_in_error">Marked in error</flux:select.option>
                                                        <flux:select.option value="patient_attended">Patient attended</flux:select.option>
                                                        <flux:select.option value="system_issue">System issue</flux:select.option>
                                                    </flux:select>
                                                </div>
                                                <div>
                                                    <flux:input wire:model="reversalNotes.{{ $appointment['id'] }}" label="Notes (optional)" type="text" placeholder="Add context for the audit trail." />
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <flux:button
                                                    size="sm"
                                                    variant="primary"
                                                    x-on:click="confirm('Reverse this no-show and issue any refund now?') && $wire.reverseNoShowWithReason({{ $appointment['id'] }})"
                                                    wire:loading.attr="disabled"
                                                >
                                                    Reverse No-Show
                                                </flux:button>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="mb-2">
                                    <div class="mb-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">Insurance</div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100 text-xs capitalize">
                                        {{ str_replace('_', ' ', $appointment['insurance_status'] ?? 'No') }}
                                    </div>
                                </div>
                                @if ($appointment['insurance_status'])
                                    <div class="mb-2">
                                        <div class="mb-2 text-[10px] font-bold uppercase tracking-widest text-zinc-400">Priority</div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100 text-xs capitalize">
                                            {{ $appointment['insurance_urgency'] ?? 'Standard' }}
                                        </div>
                                    </div>
                                    <div class="col-span-2 mt-3">
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 mb-2">Insurance Provider & ID</div>
                                        <div class="text-xs text-zinc-800 dark:text-zinc-200">
                                            <span class="font-bold">{{ $appointment['insurance_provider'] ?? 'N/A' }}</span> &bull; 
                                            {{ $appointment['insurance_member_id'] ?? 'N/A' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($appointments instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>
</div>
