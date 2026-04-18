<div class="mx-auto w-full max-w-7xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Email Delivery</flux:heading>
                <flux:subheading>Review failed, skipped, and sent emails with the likely reason and recommended next action.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Email, reason, message..." />
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
                    <flux:select wire:model.live="statusFilter" label="Status">
                        <flux:select.option value="failed">Failed</flux:select.option>
                        <flux:select.option value="skipped">Skipped</flux:select.option>
                        <flux:select.option value="sent">Sent</flux:select.option>
                        <flux:select.option value="all">All</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-xs dark:border-red-900/40 dark:bg-red-900/20">
            <div class="text-xs uppercase tracking-wide text-red-700 dark:text-red-200">Open Failures</div>
            <div class="mt-1 text-2xl font-semibold text-red-900 dark:text-red-100">{{ $failedCount }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Sent</div>
            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $sentCount }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-xs dark:border-amber-900/40 dark:bg-amber-900/20">
            <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-200">Skipped</div>
            <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ $skippedCount }}</div>
        </div>
    </div>

    <div wire:loading.flex wire:target="clinicFilter,statusFilter,search,markResolved" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
        Updating delivery logs...
    </div>

    <div class="space-y-3">
        @forelse ($logs as $log)
            <div wire:key="email-log-{{ $log['id'] }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ $log['recipient_email'] ?: 'No email address' }}</span>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ ucfirst($log['status']) }}</span>
                            <span class="text-xs text-zinc-500">{{ $log['created_at'] }}</span>
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $log['clinic'] }} • {{ $log['context'] }} • {{ $log['mailable'] }}
                            @if ($log['patient'])
                                • Patient: {{ $log['patient'] }}
                            @endif
                        </div>
                        @if ($log['failure_reason'])
                            <div class="text-sm text-zinc-700 dark:text-zinc-200">
                                <span class="font-medium">Reason:</span> {{ str_replace('_', ' ', $log['failure_reason']) }}
                            </div>
                        @endif
                        @if ($log['failure_message'])
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                                {{ $log['failure_message'] }}
                            </div>
                        @endif
                        @if ($log['suggested_action'])
                            <div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-100">
                                <span class="font-medium">Suggested action:</span> {{ $log['suggested_action'] }}
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col items-start gap-2 md:items-end">
                        @if ($log['resolved_at'])
                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">Resolved {{ $log['resolved_at'] }}</span>
                        @elseif ($log['status'] === 'failed')
                            <flux:button size="sm" variant="primary" wire:click="markResolved({{ $log['id'] }})" wire:loading.attr="disabled" wire:target="markResolved">
                                Mark Resolved
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-8 text-center text-sm text-zinc-500">
                No email delivery records match this filter.
            </div>
        @endforelse
    </div>

    @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
