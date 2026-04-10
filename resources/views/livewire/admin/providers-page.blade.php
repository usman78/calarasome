<div x-data class="mx-auto w-full max-w-7xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Provider Management</flux:heading>
                <flux:subheading>Manage provider profiles, schedules, and blocked periods.</flux:subheading>
            </div>
            <div class="w-full md:w-80">
                <flux:select wire:model.live="clinicId" label="Clinic">
                    @foreach ($clinics as $clinic)
                        <flux:select.option value="{{ $clinic['id'] }}">{{ $clinic['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error('clinicId')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    @error('provider')
        <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-3 xl:col-span-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Providers</flux:heading>
                        <p class="text-xs text-zinc-500">{{ $providers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $providers->total() : count($providers) }} total</p>
                    </div>
                    <flux:button size="sm" variant="filled" wire:click="newProvider" wire:loading.attr="disabled" wire:target="newProvider,saveProvider,deleteProvider,selectProvider">New</flux:button>
                </div>

                <div class="mb-4">
                    <flux:input wire:model.live="search" label="Search Providers" type="text" placeholder="Search name, title, email..." />
                </div>

                <div class="space-y-2">
                    @forelse ($providers as $provider)
                        <button
                            type="button"
                            wire:click="selectProvider({{ $provider['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="selectProvider,saveProvider,deleteProvider"
                            class="w-full rounded-lg border p-3 text-left transition {{ $selectedProviderId === $provider['id'] ? 'border-zinc-900 bg-zinc-100 dark:border-white dark:bg-zinc-800' : 'border-zinc-300 bg-white hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500 dark:hover:bg-zinc-800' }}"
                        >
                            <div class="font-medium">{{ $provider['full_name'] }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $provider['title'] ?: 'No title' }} | order {{ $provider['display_order'] }}
                            </div>
                            <div class="mt-1 text-xs {{ $provider['is_active'] ? 'text-green-600' : 'text-zinc-500' }}">
                                {{ $provider['is_active'] ? 'Active' : 'Inactive' }}
                            </div>
                        </button>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700">
                            No providers in this clinic.
                        </div>
                    @endforelse
                </div>

                <div wire:loading.flex wire:target="selectProvider,newProvider,saveProvider,deleteProvider" class="mt-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Updating providers...
                </div>

                @if ($providers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="mt-3">
                        {{ $providers->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6 xl:col-span-8">
            @php
                $hasProviders = $providers instanceof \Illuminate\Pagination\LengthAwarePaginator
                    ? $providers->total() > 0
                    : $providers !== [];
            @endphp
            @if (! $selectedProviderId && $hasProviders)
                <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-3 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-300">
                    Select a provider from the left panel to edit profile, schedules, and blocked times.
                </div>
            @endif

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">Provider Profile</flux:heading>
                    @if ($selectedProviderId)
                        <flux:button type="button" class="w-full sm:w-auto" variant="danger" size="sm" x-on:click.prevent="if (confirm('Delete or deactivate this provider? Existing appointments keep history.')) { $wire.deleteProvider({{ $selectedProviderId }}) }" wire:loading.attr="disabled" wire:target="deleteProvider,saveProvider">Delete / Deactivate</flux:button>
                    @endif
                </div>

                @error('isActive')
                    <p class="text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror

                <div class="grid gap-3 sm:gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="fullName" label="Full Name" type="text" required />
                        @error('fullName')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="title" label="Title" type="text" />
                        @error('title')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="specialization" label="Specialization" type="text" />
                        @error('specialization')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="email" label="Email" type="email" />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="phone" label="Phone" type="text" />
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="displayOrder" label="Display Order" type="number" min="0" />
                        @error('displayOrder')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="bookingBufferMinutes" label="Buffer Minutes" type="number" min="0" max="240" />
                        @error('bookingBufferMinutes')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-3 sm:gap-4 md:grid-cols-2">
                    <flux:switch wire:model="isActive" label="Active" />
                    <flux:switch wire:model="isAcceptingNewPatients" label="Accepting New Patients" />
                </div>

                <div wire:loading.flex wire:target="saveProvider,deleteProvider" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Saving provider changes...
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button type="button" class="w-full sm:w-auto" variant="primary" wire:click="saveProvider" wire:loading.attr="disabled" wire:target="saveProvider,deleteProvider">Save Provider</flux:button>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">Schedules</flux:heading>
                    <flux:button class="w-full sm:w-auto" variant="filled" size="sm" wire:click="addScheduleRow" wire:loading.attr="disabled" wire:target="addScheduleRow,removeScheduleRow,saveSchedules">Add Row</flux:button>
                </div>

                @error('schedules')
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
                @enderror

                @php($days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
                <div class="space-y-3">
                    @forelse ($schedules as $idx => $schedule)
                        <div class="grid gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 md:gap-3" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) auto auto;" wire:key="schedule-{{ $idx }}">
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Day</label>
                                <flux:select wire:model="schedules.{{ $idx }}.day_of_week">
                                    @foreach ($days as $dayIndex => $dayLabel)
                                        <flux:select.option value="{{ $dayIndex }}">{{ $dayLabel }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error("schedules.$idx.day_of_week")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Start</label>
                                <flux:input wire:model="schedules.{{ $idx }}.start_time" type="time" step="1" />
                                @error("schedules.$idx.start_time")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">End</label>
                                <flux:input wire:model="schedules.{{ $idx }}.end_time" type="time" step="1" />
                                @error("schedules.$idx.end_time")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">From</label>
                                <flux:input wire:model="schedules.{{ $idx }}.effective_from" type="date" />
                                @error("schedules.$idx.effective_from")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Until</label>
                                <flux:input wire:model="schedules.{{ $idx }}.effective_until" type="date" />
                                @error("schedules.$idx.effective_until")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:switch wire:model="schedules.{{ $idx }}.is_active" />
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Active</span>
                            </div>
                            <div class="flex items-center">
                                <flux:button variant="ghost" size="sm" wire:click="removeScheduleRow({{ $idx }})" wire:loading.attr="disabled" wire:target="addScheduleRow,removeScheduleRow,saveSchedules">Remove</flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700">
                            No schedules added. Click "Add Row" to create one.
                        </div>
                    @endforelse
                </div>

                <div wire:loading.flex wire:target="addScheduleRow,removeScheduleRow,saveSchedules" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Updating schedules...
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="primary" wire:click="saveSchedules" wire:loading.attr="disabled" wire:target="addScheduleRow,removeScheduleRow,saveSchedules">Save Schedules</flux:button>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-3 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-4">
                <flux:heading size="lg">Blocked Times</flux:heading>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <flux:input wire:model="blockStartDateTime" type="datetime-local" label="Start" />
                        @error('blockStartDateTime')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="blockEndDateTime" type="datetime-local" label="End" />
                        @error('blockEndDateTime')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="blockReason" type="text" label="Reason" />
                        @error('blockReason')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="filled" wire:click="addBlockedTime" wire:loading.attr="disabled" wire:target="addBlockedTime,deleteBlockedTime">Add Block</flux:button>
                </div>

                <div wire:loading.flex wire:target="addBlockedTime,deleteBlockedTime" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Saving blocked time changes...
                </div>

                <div class="space-y-2">
                    @forelse ($blockedTimes as $block)
                        <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between" wire:key="block-{{ $block['id'] }}">
                            <div>
                                <div>{{ $block['start_datetime'] }} to {{ $block['end_datetime'] }}</div>
                                <div class="text-zinc-500">{{ $block['reason'] ?: 'No reason' }}</div>
                            </div>
                            <flux:button class="w-full sm:w-auto" variant="ghost" size="sm" x-on:click.prevent="if (confirm('Delete this blocked time period?')) { $wire.deleteBlockedTime({{ $block['id'] }}) }" wire:loading.attr="disabled" wire:target="deleteBlockedTime,addBlockedTime">Delete</flux:button>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700">
                            No blocked periods for this provider.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
