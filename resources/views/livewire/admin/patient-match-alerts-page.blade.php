<div class="mx-auto w-full max-w-7xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Patient Match Alerts</flux:heading>
                <flux:subheading>Read-only feed for shared-email mismatch alerts created by patient matching.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, email, type..." />
                </div>
                <div class="w-full md:w-64">
                    <flux:select wire:model.live="clinicFilter" label="Clinic">
                        <flux:select.option value="all">All clinics</flux:select.option>
                        @foreach ($clinics as $clinic)
                            <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Open Alerts</div>
            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $openAlertsCount }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Resolved Alerts</div>
            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $resolvedAlertsCount }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
        <div wire:loading.flex wire:target="clinicFilter,markResolved,search" class="mb-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
            <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
            Updating alerts...
        </div>

        <div class="overflow-auto -mx-4 sm:mx-0 max-h-[70vh] rounded-lg border border-zinc-200/60 bg-white dark:border-zinc-800/70 dark:bg-zinc-950">
            <div class="w-full">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                    <tr class="border-b border-zinc-200 text-left text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Created At</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Clinic</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Patient</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Email</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Type</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Existing IDs</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Merge</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Status</th>
                        <th class="sticky top-0 z-10 bg-white/95 px-2.5 py-2 font-medium backdrop-blur dark:bg-zinc-950/95">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($alerts as $alert)
                        <tr class="border-b border-zinc-100 align-top dark:border-zinc-800">
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $alert['created_at'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $alert['clinic_name'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                <div class="font-medium">{{ $alert['patient_name'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $alert['patient_dob'] ?: 'DOB unavailable' }}</div>
                            </td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $alert['email'] ?: 'n/a' }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">{{ $alert['alert_type'] }}</td>
                            <td class="px-2.5 py-2 text-zinc-700 dark:text-zinc-200">
                                @if ($alert['existing_patient_ids'] === [])
                                    <span class="text-zinc-500">none</span>
                                @else
                                    {{ implode(', ', $alert['existing_patient_ids']) }}
                                @endif
                            </td>
                            <td class="px-2.5 py-2">
                                @if (! $alert['resolved_at'] && ! empty($alert['existing_patients']))
                                    <div class="flex flex-col gap-2">
                                        <select class="rounded-lg border border-zinc-200 px-2 py-1 text-sm"
                                                wire:model.live="mergeTargetId">
                                            <option value="">Select target</option>
                                            @foreach ($alert['existing_patients'] as $patient)
                                                <option value="{{ $patient['id'] }}">
                                                    {{ $patient['name'] }} ({{ $patient['dob'] ?? 'n/a' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                                            onclick="return confirm('Merge this patient record? This action is irreversible.')"
                                            wire:click="mergePatient({{ $alert['id'] }}, {{ $alert['patient_id'] ?? 0 }}, {{ $mergeTargetId ?? 0 }})"
                                            @if (empty($mergeTargetId)) disabled @endif
                                        >
                                            Merge
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-500">-</span>
                                @endif
                            </td>
                            <td class="px-2.5 py-2">
                                @if ($alert['resolved_at'])
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">Resolved</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">Open</span>
                                @endif
                            </td>
                            <td class="px-2.5 py-2">
                                @if (! $alert['resolved_at'])
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        x-on:click.prevent="if (confirm('Mark this alert as resolved?')) { $wire.markResolved({{ $alert['id'] }}) }"
                                        wire:loading.attr="disabled"
                                        wire:target="markResolved,clinicFilter"
                                    >
                                        Mark Resolved
                                    </flux:button>
                                @else
                                    <span class="text-xs text-zinc-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No patient match alerts found for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        @if ($alerts instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $alerts->links() }}
            </div>
        @endif
    </div>
</div>
