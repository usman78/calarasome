<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Insurance Verification Queue</flux:heading>
                <flux:subheading>Review medical insurance details and verify urgency in priority order.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, email, provider..." />
                </div>
                <div class="w-full md:w-52">
                    <flux:select wire:model.live="clinicFilter" label="Clinic">
                        <flux:select.option value="all">All clinics</flux:select.option>
                        @foreach ($clinics as $clinic)
                            <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-44">
                    <flux:select wire:model.live="urgencyFilter" label="Urgency">
                        <flux:select.option value="all">All</flux:select.option>
                        <flux:select.option value="critical">Critical</flux:select.option>
                        <flux:select.option value="high">High</flux:select.option>
                        <flux:select.option value="standard">Standard</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-44">
                    <flux:select wire:model.live="statusFilter" label="Status">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="verified">Verified</flux:select.option>
                        <flux:select.option value="failed">Failed</flux:select.option>
                        <flux:select.option value="all">All</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-3 sm:gap-3">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pending</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $pendingCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Verified</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $verifiedCount }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 sm:px-4 sm:py-3">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Failed</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $failedCount }}</div>
            </div>
        </div>
    </div>

    @if ($verifications instanceof \Illuminate\Pagination\LengthAwarePaginator ? $verifications->isEmpty() : empty($verifications))
        <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
            No insurance verifications match the current filters.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($verifications as $verification)
                <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-xs text-zinc-500">{{ $verification['clinic'] }}</div>
                            <div class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $verification['patient'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $verification['email'] }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                {{ strtoupper($verification['urgency']) }}
                            </span>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                {{ ucfirst($verification['status']) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-zinc-500">Appointment</div>
                            <div class="text-sm text-zinc-900 dark:text-white">
                                {{ $verification['appointment_type'] }} with {{ $verification['provider'] }}
                            </div>
                            <div class="text-xs text-zinc-500">
                                {{ $verification['slot_local'] }} ({{ $verification['timezone'] }})
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-500">Insurance</div>
                            <div class="text-sm text-zinc-900 dark:text-white">
                                {{ $verification['insurance_provider'] ?? 'Provider unavailable' }}
                            </div>
                            <div class="text-xs text-zinc-500">Member ID: {{ $verification['insurance_member_id'] ?? 'N/A' }}</div>
                        </div>
                    </div>

                    @if ($verification['status'] === 'pending')
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <flux:button class="w-full sm:w-auto" variant="primary" wire:click="markVerified({{ $verification['id'] }})" wire:loading.attr="disabled" wire:target="markVerified">
                                Verify
                            </flux:button>
                            <flux:button
                                class="w-full sm:w-auto"
                                variant="danger"
                                wire:click="markFailed({{ $verification['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="markFailed"
                                onclick="return confirm('Mark this insurance verification as failed and notify the patient?')"
                            >
                                Fail + Notify
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endforeach
            @if ($verifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $verifications->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
