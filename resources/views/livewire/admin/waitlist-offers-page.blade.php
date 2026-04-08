<div class="mx-auto w-full max-w-6xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Waitlist Offers</flux:heading>
                <flux:subheading>Review open slots and offer them to eligible waitlist entries.</flux:subheading>
            </div>
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row">
                <div class="w-full md:w-56">
                    <flux:select wire:model.live="clinicFilter" label="Clinic">
                        <flux:select.option value="all">All clinics</flux:select.option>
                        @foreach ($clinics as $clinic)
                            <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="w-full md:w-44">
                    <flux:select wire:model.live="statusFilter" label="Status">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="claimed">Claimed</flux:select.option>
                        <flux:select.option value="expired">Expired</flux:select.option>
                        <flux:select.option value="all">All</flux:select.option>
                    </flux:select>
                </div>
                <div class="w-full md:w-56">
                    <flux:input wire:model.live="search" label="Search" type="text" placeholder="Clinic, provider, type..." />
                </div>
            </div>
        </div>
    </div>

    <div wire:loading.flex wire:target="clinicFilter,statusFilter,search" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
        <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
        Loading offers...
    </div>

    @if ($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator ? $notifications->isEmpty() : empty($notifications))
        <div class="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-center text-sm text-zinc-500">
            No open waitlist offers right now.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($notifications as $notification)
                <div wire:key="waitlist-offer-{{ $notification['id'] }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-xs text-zinc-500">{{ $notification['clinic'] }}</div>
                            <div class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $notification['appointment_type'] }} ({{ $notification['slot_duration'] }} min)
                            </div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $notification['slot_local'] }} ({{ $notification['timezone'] }})
                            </div>
                            <div class="text-xs text-zinc-500">Provider: {{ $notification['provider'] }}</div>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ ucfirst($notification['status']) }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @if ($notification['status'] === 'claimed')
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
                                This slot has been claimed.
                                @if ($notification['claimed_by_entry_id'])
                                    <div class="mt-1 text-xs text-emerald-700">Waitlist Entry ID: {{ $notification['claimed_by_entry_id'] }}</div>
                                @endif
                                @if ($notification['claimed_appointment_id'])
                                    <div class="text-xs text-emerald-700">Appointment ID: {{ $notification['claimed_appointment_id'] }}</div>
                                @endif
                            </div>
                        @else
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Eligible Waitlist Entries</div>
                            @if (empty($notification['eligible_entries']))
                                <div class="rounded-lg border border-dashed border-zinc-200 px-3 py-3 text-sm text-zinc-500">
                                    No eligible waitlist entries match this slot. Check treatment duration, provider, or date preferences.
                                </div>
                            @else
                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach ($notification['eligible_entries'] as $entry)
                                        <div wire:key="waitlist-offer-entry-{{ $notification['id'] }}-{{ $entry['id'] }}" class="rounded-lg border border-zinc-200 p-3">
                                            <div class="flex items-center justify-between">
                                                <div class="font-semibold text-zinc-900">{{ $entry['patient'] }}</div>
                                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">
                                                    Score {{ $entry['priority_score_display'] }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-[11px] text-zinc-500">Entry ID: {{ $entry['id'] }}</div>
                                            <div class="mt-1 text-xs text-zinc-500">{{ $entry['appointment_type'] }} ({{ $entry['duration'] }} min)</div>
                                            <div class="mt-2 text-xs text-zinc-500">
                                                @if ($entry['email'])
                                                    <div>{{ $entry['email'] }}</div>
                                                @endif
                                                @if ($entry['phone'])
                                                    <div>{{ $entry['phone'] }}</div>
                                                @endif
                                            </div>
                                            @if (! $entry['date_match'])
                                                <div class="mt-2 text-xs text-amber-700">Preferred date: {{ $entry['preferred_date'] }} (different from slot date)</div>
                                            @endif
                                            <div class="mt-2 text-xs text-zinc-500">
                                                Window: {{ $entry['preferred_time_window'] }}
                                            </div>
                                            @if ($entry['notes'])
                                                <div class="mt-1 text-xs text-zinc-500">Notes: {{ $entry['notes'] }}</div>
                                            @endif
                                            <div class="mt-3 flex flex-col gap-2">
                                                @if ($entry['offer_status'])
                                                    <div class="text-xs text-zinc-500">
                                                        Offer: {{ ucfirst($entry['offer_status']) }}
                                                        @if ($entry['offer_sent_at'])
                                                            <span>• Sent {{ $entry['offer_sent_at'] }}</span>
                                                        @endif
                                                        @if ($entry['offer_expires_at'])
                                                            <span>• Expires {{ $entry['offer_expires_at'] }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                <flux:button
                                                    size="sm"
                                                    variant="primary"
                                                    wire:click="createOffer({{ $notification['id'] }}, {{ $entry['id'] }})"
                                                    wire:loading.attr="disabled"
                                                >
                                                    {{ $entry['offer_status'] ? 'Create New Offer Link' : 'Create Offer Link' }}
                                                </flux:button>
                                                @if (! empty($offerLinks[$entry['id']]))
                                                    <a href="{{ $offerLinks[$entry['id']] }}" target="_blank" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-700 break-all underline">
                                                        {{ $offerLinks[$entry['id']] }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</div>
