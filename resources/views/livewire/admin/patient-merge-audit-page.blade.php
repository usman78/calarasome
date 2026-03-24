<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Patient Merge Audit Log</flux:heading>
                <flux:subheading>Irreversible merges recorded for compliance review.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Patient, email, admin..." />
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

    @if ($merges instanceof \Illuminate\Pagination\LengthAwarePaginator ? $merges->isEmpty() : empty($merges))
        <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
            No patient merges recorded for this filter.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($merges as $merge)
                <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <div>
                            <div class="text-xs text-zinc-500">{{ $merge['clinic'] }}</div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Merged by {{ $merge['merged_by'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $merge['created_at'] }}</div>
                        </div>
                        <div class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 sm:self-start">
                            Merge ID {{ $merge['id'] }}
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-xs uppercase tracking-wide text-zinc-500">Source</div>
                            <div class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ $merge['source']['name'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $merge['source']['email'] ?? 'Email n/a' }}</div>
                            <div class="text-xs text-zinc-500">DOB {{ $merge['source']['dob'] ?? 'n/a' }}</div>
                            <div class="text-[11px] text-zinc-400">ID {{ $merge['source']['id'] }}</div>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/40 dark:bg-emerald-900/20">
                            <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-200">Target</div>
                            <div class="mt-1 text-sm font-semibold text-emerald-900 dark:text-white">{{ $merge['target']['name'] }}</div>
                            <div class="text-xs text-emerald-700 dark:text-emerald-200">{{ $merge['target']['email'] ?? 'Email n/a' }}</div>
                            <div class="text-xs text-emerald-700 dark:text-emerald-200">DOB {{ $merge['target']['dob'] ?? 'n/a' }}</div>
                            <div class="text-[11px] text-emerald-500">ID {{ $merge['target']['id'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($merges instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $merges->links() }}
            </div>
        @endif
    @endif
</div>
