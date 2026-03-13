<div x-data class="mx-auto w-full max-w-7xl space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 sm:p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">Appointment Type Management</flux:heading>
                <flux:subheading>Manage treatments and provider mapping for booking availability.</flux:subheading>
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

    @if (session('appointment_types_status'))
        <div class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-200">
            {{ session('appointment_types_status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-3 xl:col-span-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Treatments</flux:heading>
                        <p class="text-xs text-zinc-500">{{ count($appointmentTypes) }} total</p>
                    </div>
                    <flux:button
                        size="sm"
                        variant="filled"
                        wire:click="newAppointmentType"
                        wire:loading.attr="disabled"
                        wire:target="newAppointmentType,selectAppointmentType,saveAppointmentType,deleteAppointmentType"
                    >
                        New
                    </flux:button>
                </div>

                <div class="space-y-2">
                    @forelse ($appointmentTypes as $appointmentType)
                        <button
                            type="button"
                            wire:click="selectAppointmentType({{ $appointmentType['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="newAppointmentType,selectAppointmentType,saveAppointmentType,deleteAppointmentType"
                            class="w-full rounded-lg border p-3 text-left transition {{ $selectedAppointmentTypeId === $appointmentType['id'] ? 'border-zinc-900 bg-zinc-100 dark:border-white dark:bg-zinc-800' : 'border-zinc-300 bg-white hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500 dark:hover:bg-zinc-800' }}"
                        >
                            <div class="font-medium">{{ $appointmentType['name'] }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $appointmentType['duration_minutes'] }} min | {{ $appointmentType['provider_count'] }} provider{{ $appointmentType['provider_count'] === 1 ? '' : 's' }}
                            </div>
                            <div class="mt-1 text-xs {{ $appointmentType['is_active'] ? 'text-green-600' : 'text-zinc-500' }}">
                                {{ $appointmentType['is_active'] ? 'Active' : 'Inactive' }}
                            </div>
                        </button>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700">
                            No appointment types in this clinic.
                        </div>
                    @endforelse
                </div>

                <div wire:loading.flex wire:target="newAppointmentType,selectAppointmentType,saveAppointmentType,deleteAppointmentType,clinicId" class="mt-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Updating treatments...
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-8">
            @if (! $selectedAppointmentTypeId && $appointmentTypes !== [])
                <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-3 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-300">
                    Select a treatment from the left panel to edit details and provider mapping.
                </div>
            @endif

            <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Treatment Profile</flux:heading>
                    @if ($selectedAppointmentTypeId)
                        <flux:button
                            variant="danger"
                            size="sm"
                            x-on:click.prevent="if (confirm('Delete this appointment type? Provider mappings will be removed.')) { $wire.deleteAppointmentType({{ $selectedAppointmentTypeId }}) }"
                            wire:loading.attr="disabled"
                            wire:target="deleteAppointmentType,saveAppointmentType"
                        >
                            Delete
                        </flux:button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="name" label="Name" type="text" required />
                        <p class="mt-1 text-xs text-zinc-500">Example: Initial Consultation, Acne Follow-up, Laser Session.</p>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="durationMinutes" label="Duration (Minutes)" type="number" min="5" max="240" required />
                        <p class="mt-1 text-xs text-zinc-500">Used for slot generation and booking availability.</p>
                        @error('durationMinutes')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input
                            wire:model="depositAmountCents"
                            label="Deposit Amount (Cents)"
                            type="number"
                            min="0"
                            :disabled="$isMedical"
                        />
                        <p class="mt-1 text-xs text-zinc-500">
                            @if ($isMedical)
                                Disabled for medical visits. Deposits are skipped.
                            @else
                                Set to 0 for no deposit.
                            @endif
                        </p>
                        @error('depositAmountCents')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input
                            wire:model="depositCurrency"
                            label="Deposit Currency"
                            type="text"
                            :disabled="$isMedical"
                        />
                        <p class="mt-1 text-xs text-zinc-500">
                            @if ($isMedical)
                                Disabled for medical visits. Currency is ignored.
                            @else
                                Example: usd, eur, gbp.
                            @endif
                        </p>
                        @error('depositCurrency')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:switch wire:model="isActive" label="Active" />
                    <flux:switch wire:model="isMedical" label="Medical Visit (skip deposit)" />
                </div>
                @error('isActive')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
                @error('isMedical')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror

                <div class="space-y-2">
                    <flux:heading size="sm">Provider Mapping</flux:heading>
                    <p class="text-xs text-zinc-500">
                        Select which providers can deliver this treatment. Leaving all unchecked means this treatment will not be bookable.
                    </p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    @forelse ($providers as $provider)
                        <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input
                                type="checkbox"
                                wire:model="selectedProviderIds"
                                value="{{ $provider['id'] }}"
                                class="checkbox checkbox-sm"
                            >
                            <span class="flex-1">
                                <span class="block font-medium">{{ $provider['full_name'] }}</span>
                                <span class="block text-xs {{ $provider['is_active'] ? 'text-zinc-500' : 'text-amber-600 dark:text-amber-300' }}">
                                    {{ $provider['is_active'] ? 'Active provider' : 'Inactive provider' }}
                                </span>
                            </span>
                        </label>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 px-3 py-4 text-sm text-zinc-500 dark:border-zinc-700 sm:col-span-2">
                            No providers available in this clinic.
                        </div>
                    @endforelse
                </div>
                @error('selectedProviderIds')
                    <p class="text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
                @error('selectedProviderIds.*')
                    <p class="text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror

                <div wire:loading.flex wire:target="saveAppointmentType,deleteAppointmentType,selectedProviderIds" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Saving treatment changes...
                </div>

                <div class="flex justify-end">
                    <flux:button
                        variant="primary"
                        wire:click="saveAppointmentType"
                        wire:loading.attr="disabled"
                        wire:target="saveAppointmentType,deleteAppointmentType"
                    >
                        {{ $selectedAppointmentTypeId ? 'Save Treatment' : 'Create Treatment' }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
