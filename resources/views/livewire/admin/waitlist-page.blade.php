<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Waitlist Priority</flux:heading>
                <flux:subheading>Grouped by urgency tier with live priority scores.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, clinic, type..." />
                </div>
                <div class="w-full md:w-56">
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

    @php
        $totalEntries = $entryPaginator instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $entryPaginator->total()
            : (count($entries['urgent']) + count($entries['high']) + count($entries['standard']));
        $hasEntries = $totalEntries > 0;
    @endphp

    @if (! $hasEntries)
        <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500">
            No waitlist entries match the current filters.
        </div>
    @else
        @foreach (['urgent' => 'Urgent', 'high' => 'High', 'standard' => 'Standard'] as $tierKey => $tierLabel)
            <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">{{ $tierLabel }} Tier</flux:heading>
                    <span class="text-xs text-zinc-500 sm:text-right">{{ count($entries[$tierKey] ?? []) }} entries</span>
                </div>

                @if (empty($entries[$tierKey]))
                    <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500">
                        No {{ strtolower($tierLabel) }} waitlist entries.
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($entries[$tierKey] as $entry)
                            <div class="rounded-lg border border-zinc-200 p-3 sm:p-4">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-zinc-900">{{ $entry['patient'] }}</div>
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">
                                        Score {{ $entry['priority_score'] }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-zinc-500">{{ $entry['clinic'] }}</div>
                                <div class="mt-1 text-sm text-zinc-700">{{ $entry['appointment_type'] }}</div>

                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-500">
                                    <span>Preferred: {{ $entry['preferred_datetime'] ?? 'None' }}</span>
                                    <span>Wait: {{ $entry['wait_days'] }} days</span>
                                    <span>No-shows: {{ $entry['no_show_count'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @if ($entryPaginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $entryPaginator->links() }}
            </div>
        @endif
    @endif
</div>
