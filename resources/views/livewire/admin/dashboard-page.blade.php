<div class="mx-auto w-full space-y-6">
    <style>
    ui-label[data-flux-label] {
        color: #71717a !important; /* zinc-500 */
    }

    html.dark ui-label[data-flux-label] {
        color: #a1a1aa !important; /* zinc-400 */
    }
    </style>
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Appointments Overview</flux:heading>
                <flux:subheading>Filter across clinics, providers, and statuses.</flux:subheading>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="text-sm text-zinc-500">Showing {{ $totalCount }} appointments</div>
                <flux:button size="sm" variant="ghost" wire:click="exportCsv">Export CSV</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="exportNext7Days">Export Next 7 Days</flux:button>
            </div>
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <article class="flex items-end justify-between rounded-lg border border-gray-100 bg-white p-6">
                <div class="flex items-center gap-4">
                    <span class="hidden rounded-full bg-gray-100 p-2 text-gray-600 sm:block">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    </span>

                    <div>
                    <p class="text-sm text-gray-500">Today</p>

                    <p class="text-2xl font-medium text-gray-900">{{ $todayCount}}</p>
                    </div>
                </div>
            </article>

            <article class="flex items-end justify-between rounded-lg border border-gray-100 bg-white p-6">
                <div class="flex items-center gap-4">
                    <span class="hidden rounded-full bg-gray-100 p-2 text-gray-600 sm:block">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    </span>

                    <div>
                    <p class="text-sm text-gray-500">Next 7 Days</p>

                    <p class="text-2xl font-medium text-gray-900">{{ $next7Count }}</p>
                    </div>
                </div>
            </article>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 dark:border-emerald-900/40 dark:bg-emerald-900/20 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-200">Confirmed</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-900 dark:text-white">{{ $statusConfirmed }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/40 dark:bg-amber-900/20 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-200">Pending</div>
                <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-white">{{ $statusPending }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Canceled</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $statusCanceled }}</div>
            </div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 dark:border-rose-900/40 dark:bg-rose-900/20 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-200">No‑Show</div>
                <div class="mt-1 text-2xl font-semibold text-rose-900 dark:text-white">{{ $statusNoShow }}</div>
            </div>
        </div>

        <div class="mt-4 grid gap-2 sm:gap-3">
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                    </svg>
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white">Search & Location</h3>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <flux:input wire:model.live="search" label="Search" label-class="text-red-600" type="text" placeholder="Patient, provider, treatment..." />

                    <flux:select wire:model.live="clinicFilter" label="Clinic">
                        <flux:select.option value="all">All clinics</flux:select.option>
                        @foreach ($clinics as $clinic)
                            <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center gap-2">
                    <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white">Appointment Details</h3>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <flux:select wire:model.live="providerFilter" label="Provider">
                        <flux:select.option value="all">All providers</flux:select.option>
                        @foreach ($providers as $provider)
                            <flux:select.option value="{{ $provider['id'] }}">{{ $provider['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="appointmentTypeFilter" label="Appointment Type">
                        <flux:select.option value="all">All types</flux:select.option>
                        @foreach ($appointmentTypes as $type)
                            <flux:select.option value="{{ $type['id'] }}">{{ $type['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="statusFilter" label="Status">
                        <flux:select.option value="all">All statuses</flux:select.option>
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="confirmed">Confirmed</flux:select.option>
                        <flux:select.option value="completed">Completed</flux:select.option>
                        <flux:select.option value="cancelled_by_patient">Canceled (Patient)</flux:select.option>
                        <flux:select.option value="cancelled_by_clinic">Canceled (Clinic)</flux:select.option>
                        <flux:select.option value="no_show">No-Show</flux:select.option>
                    </flux:select>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center gap-2">
                    <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-white">Time Horizon</h3>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <flux:input wire:model.live="dateFrom" label="Date From" type="date" />
                    <flux:input wire:model.live="dateTo" label="Date To" type="date" />
                </div>
            </div>
        </div>
    </div>

    @if ($appointments instanceof \Illuminate\Pagination\LengthAwarePaginator ? $appointments->isEmpty() : empty($appointments))
        <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
            No appointments match the current filters.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($appointments as $appointment)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-xs text-zinc-500">{{ $appointment['clinic'] }}</div>
                            <div class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $appointment['patient'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $appointment['appointment_type'] }} with <b>{{ $appointment['provider'] }}</b></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                {{ str_replace('_', ' ', $appointment['status']) }}
                            </span>
                            @if ($appointment['payment_status'])
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    Payment: {{ $appointment['payment_status'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 text-sm text-zinc-500">
                        {{ $appointment['slot_local'] }} ({{ $appointment['timezone'] }})
                    </div>
                </div>
            @endforeach
        </div>

        @if ($appointments instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $appointments->links() }}
            </div>
        @endif
    @endif
</div>
