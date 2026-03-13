@php
    $steps = [
        1 => 'Patient Status',
        2 => 'Treatment',
        3 => 'Provider',
        4 => 'Time Slot',
        5 => 'Details',
        6 => 'Confirmation',
    ];

    $stepDescriptions = [
        1 => 'Start by choosing whether you are a new or returning patient.',
        2 => 'Choose the appointment type that best fits your visit.',
        3 => 'Pick a specific provider or let us assign the next available one.',
        4 => 'Select your preferred date and available appointment time.',
        5 => 'Enter contact details, confirm consent, and finalize the booking.',
        6 => 'Review your confirmation details.',
    ];

    $progressPercent = (int) round(($step / 6) * 100);
    $canCompleteBooking = filled($fullName) && filled($email) && filled($dateOfBirth) && $emailConsent && filled($sessionToken);
@endphp

<div class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/50">
    <div class="border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="grid size-8 place-items-center rounded-md border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <x-app-logo-icon class="size-5 fill-current text-zinc-700 dark:text-zinc-200" />
                </span>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">SmartBook</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clinic->name }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Step {{ $step }} of 6</p>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $steps[$step] }}</p>
            </div>
        </div>
        <div class="mt-3 h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-zinc-900 transition-all duration-300 dark:bg-zinc-100" style="width: {{ $progressPercent }}%"></div>
        </div>
    </div>

    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6 lg:p-8">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl lg:text-4xl">Book an Appointment</h1>
            <p class="mt-2 max-w-3xl text-sm text-zinc-600 dark:text-zinc-300">{{ $stepDescriptions[$step] }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Timezone {{ $clinic->timezone }}</p>
        </div>

        @if ($step === 1)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">1. Patient Status</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Step 1 of 6</div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <button
                        type="button"
                        wire:click="$set('isNewPatient', true)"
                        class="relative rounded-xl border p-4 text-left transition {{ $isNewPatient ? '!border-zinc-900 !bg-zinc-900 !text-white shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                    >
                        @if ($isNewPatient)
                            <span class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="Selected">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                        <div class="text-xs uppercase tracking-wide {{ $isNewPatient ? 'text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">New</div>
                        <div class="mt-1 text-lg font-semibold {{ $isNewPatient ? 'text-white' : 'text-zinc-900 dark:text-zinc-100' }}">I am a NEW patient</div>
                        <p class="mt-2 text-xs {{ $isNewPatient ? 'text-zinc-200' : 'text-zinc-600 dark:text-zinc-300' }}">First visit at this clinic. We will create your profile.</p>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('isNewPatient', false)"
                        class="relative rounded-xl border p-4 text-left transition {{ ! $isNewPatient ? '!border-zinc-900 !bg-zinc-900 !text-white shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                    >
                        @if (! $isNewPatient)
                            <span class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="Selected">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                        <div class="text-xs uppercase tracking-wide {{ ! $isNewPatient ? 'text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">Returning</div>
                        <div class="mt-1 text-lg font-semibold {{ ! $isNewPatient ? 'text-white' : 'text-zinc-900 dark:text-zinc-100' }}">I am a RETURNING patient</div>
                        <p class="mt-2 text-xs {{ ! $isNewPatient ? 'text-zinc-200' : 'text-zinc-600 dark:text-zinc-300' }}">We will match your existing record before confirming.</p>
                    </button>
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">You can change this selection later before booking is confirmed.</p>

                <div class="flex justify-end">
                    <flux:button variant="primary" wire:click="$set('step', 2)">Continue</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 2)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">2. Treatment Selection</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Step 2 of 6</div>
                </div>

                @if ($appointmentTypes === [])
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        No appointment types are active for this clinic yet.
                    </div>
                @else
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($appointmentTypes as $type)
                            <button
                                type="button"
                                wire:click="chooseAppointmentType({{ $type['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="chooseAppointmentType"
                                class="relative rounded-xl border p-4 text-left transition {{ $appointmentTypeId === $type['id'] ? '!border-zinc-900 !bg-zinc-900 !text-white shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                            >
                                @if ($appointmentTypeId === $type['id'])
                                    <span class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="Selected">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif
                                <div class="inline-flex rounded-full border border-zinc-300 {{ $appointmentTypeId === $type['id'] ? 'bg-zinc-800 text-zinc-300 border-zinc-700' : 'bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300' }} px-2 py-0.5 text-[10px] uppercase tracking-wide">
                                    Appointment Type
                                </div>
                                <div class="mt-3 text-base font-semibold {{ $appointmentTypeId === $type['id'] ? 'text-white' : 'text-zinc-900 dark:text-white' }}">{{ $type['name'] }}</div>
                                <div class="mt-1 text-xs {{ $appointmentTypeId === $type['id'] ? 'text-zinc-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $type['duration_minutes'] }} minutes</div>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div wire:loading.flex wire:target="chooseAppointmentType" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Loading providers...
                </div>

                <div class="mt-5 flex justify-end">
                    <flux:button variant="filled" wire:click="$set('step', 1)" wire:loading.attr="disabled" wire:target="chooseAppointmentType">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 3)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">3. Choose Provider</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Step 3 of 6</div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <button
                        type="button"
                        wire:click="chooseProvider('any')"
                        wire:loading.attr="disabled"
                        wire:target="chooseProvider"
                        class="relative rounded-xl border p-4 text-left transition {{ $providerSelection === 'any' ? '!border-zinc-900 !bg-zinc-900 !text-white shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                    >
                        @if ($providerSelection === 'any')
                            <span class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="Selected">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                        <div class="inline-flex rounded-full border border-zinc-300 {{ $providerSelection === 'any' ? 'bg-zinc-800 text-zinc-300 border-zinc-700' : 'bg-white text-zinc-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300' }} px-2 py-0.5 text-[10px] uppercase tracking-wide">
                            Recommended
                        </div>
                        <div class="mt-3 text-base font-semibold {{ $providerSelection === 'any' ? 'text-white' : 'text-zinc-900 dark:text-white' }}">Any Available</div>
                        <p class="mt-1 text-xs {{ $providerSelection === 'any' ? 'text-zinc-300' : 'text-zinc-500 dark:text-zinc-400' }}">Balanced assignment at reservation time.</p>
                    </button>

                    @foreach ($providers as $provider)
                        <button
                            type="button"
                            wire:click="chooseProvider('{{ $provider['id'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="chooseProvider"
                            class="relative rounded-xl border p-4 text-left transition {{ $providerSelection == $provider['id'] ? '!border-zinc-900 !bg-zinc-900 !text-white shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                        >
                            @if ($providerSelection == $provider['id'])
                                <span class="absolute right-3 top-3 inline-flex size-5 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="Selected">
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            @endif
                            <div class="text-base font-semibold {{ $providerSelection == $provider['id'] ? 'text-white' : 'text-zinc-900 dark:text-white' }}">{{ $provider['full_name'] }}</div>
                            @if ($provider['title'])
                                <div class="mt-1 text-xs {{ $providerSelection == $provider['id'] ? 'text-zinc-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $provider['title'] }}</div>
                            @endif
                        </button>
                    @endforeach
                </div>

                @if ($providers === [])
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        No matching providers found for this appointment type.
                    </div>
                @endif

                <div wire:loading.flex wire:target="chooseProvider" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Loading available slots...
                </div>

                <div class="mt-5 flex justify-end">
                    <flux:button variant="filled" wire:click="$set('step', 2)" wire:loading.attr="disabled" wire:target="chooseProvider">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 4)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">4. Select Date and Time</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Step 4 of 6</div>
                </div>

                <div class="max-w-xs">
                    <flux:input wire:model.live="selectedDate" label="Preferred Date" type="date" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Times below are shown in {{ $clinic->timezone }}.</p>
                </div>

                @error('slot')
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
                @enderror
                @error('reservation')
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
                @enderror

                @if ($availableSlots === [])
                    <div class="rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        No available slots for this date.
                    </div>
                @else
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($availableSlots as $slot)
                            <button
                                type="button"
                                wire:key="slot-{{ md5($slot['slotLocal'].'-'.$slot['providerId']) }}"
                                wire:click="reserveSlot('{{ $slot['slotLocal'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="reserveSlot,selectedDate"
                                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-800 transition hover:border-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                            >
                                {{ \Carbon\CarbonImmutable::parse($slot['slotLocal'], $clinic->timezone)->format('g:i A') }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div wire:loading.flex wire:target="selectedDate,reserveSlot" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Refreshing available slots...
                </div>

                <div class="mt-5 flex justify-end">
                    <flux:button variant="filled" wire:click="$set('step', 3)" wire:loading.attr="disabled" wire:target="reserveSlot,selectedDate">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 5)
            <div wire:poll.1s="refreshReservationTimer" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">5. Contact, Consent and Confirm</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Step 5 of 6</div>
                </div>

                <div class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">
                    Slot reserved with {{ $assignedProviderName }}. Time remaining:
                    <strong>{{ floor($reservationSecondsRemaining / 60) }}:{{ str_pad((string) ($reservationSecondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}</strong>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="fullName" label="Full Name" type="text" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Use legal name for patient matching.</p>
                        @error('fullName')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="email" label="Email" type="email" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Confirmation and reminders will be sent here.</p>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="phone" label="Phone" type="text" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Optional, but useful for urgent updates.</p>
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model="dateOfBirth" label="Date of Birth" type="date" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Required to avoid duplicate patient records.</p>
                        @error('dateOfBirth')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:checkbox wire:model="emailConsent" label="I consent to receive appointment reminders via email (required)." />
                    <flux:checkbox wire:model="emailPhi" label="Allow emails to include appointment details (PHI)." />
                </div>
                @error('emailConsent')
                    <p class="text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror

                @error('reservation')
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
                @enderror

                <div wire:loading.flex wire:target="completeBooking" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Confirming appointment...
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="filled" wire:click="$set('step', 4)" wire:loading.attr="disabled" wire:target="completeBooking">Back</flux:button>
                    <flux:button :disabled="! $canCompleteBooking" variant="primary" wire:click="completeBooking" wire:loading.attr="disabled" wire:target="completeBooking">
                        <span wire:loading.remove wire:target="completeBooking">Complete Booking</span>
                        <span wire:loading wire:target="completeBooking">Completing...</span>
                    </flux:button>
                </div>
            </div>
        @endif

        @if ($step === 6)
            <div class="space-y-4 rounded-xl border border-green-300 bg-green-50 p-3 dark:border-green-700 dark:bg-green-900/20 sm:p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">6. Confirmation</flux:heading>
                    <div class="text-xs text-green-700 dark:text-green-200">Completed</div>
                </div>
                <p class="text-sm text-zinc-800 dark:text-zinc-100">
                    Your appointment is confirmed.
                </p>
                <ul class="space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                    <li><strong>Appointment ID:</strong> {{ $appointmentId }}</li>
                    <li><strong>Provider:</strong> {{ $assignedProviderName }}</li>
                    <li><strong>Time:</strong> {{ $confirmedSlotLocal }} ({{ $clinic->timezone }})</li>
                </ul>
                @if ($paymentStrategy)
                    <div class="rounded-lg border border-green-200 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-green-700 dark:bg-zinc-900 dark:text-zinc-200">
                        <strong>Payment:</strong> {{ $paymentStatus ?? 'pending' }} ({{ $paymentStrategy }})
                    </div>
                @endif
                @if ($paymentStrategy && $paymentStrategy !== 'skip' && $paymentClientSecret)
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-950">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Complete Deposit</div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Your deposit is required to finalize this appointment.</p>

                        <form id="payment-form" class="mt-4 space-y-3">
                            <div
                                id="payment-container"
                                data-client-secret="{{ $paymentClientSecret }}"
                                data-payment-strategy="{{ $paymentStrategy }}"
                            >
                                <div id="payment-element" wire:ignore></div>
                            </div>
                            <button id="payment-submit" type="submit" class="w-full rounded-lg bg-black px-4 py-2 text-sm font-medium text-white">
                                Confirm Payment
                            </button>
                            <div id="payment-message" class="text-xs text-zinc-600 dark:text-zinc-300"></div>
                        </form>
                    </div>
                @endif
                <div class="flex gap-2">
                    <flux:button variant="filled" href="{{ route('home') }}" wire:navigate>Back to Home</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>

@once
    <script src="https://js.stripe.com/v3"></script>
    <script>
        (() => {
            let stripe = null;
            let elements = null;

            const initPayment = () => {
                const container = document.getElementById('payment-container');
                if (!container) {
                    return;
                }

                const clientSecret = container.dataset.clientSecret;
                const strategy = container.dataset.paymentStrategy;
                const publishableKey = @js(config('services.stripe.publishable'));

                if (!clientSecret || !publishableKey) {
                    return;
                }

                const elementMount = document.getElementById('payment-element');
                if (!elementMount) {
                    return;
                }

                stripe = stripe || Stripe(publishableKey);
                elements = stripe.elements({ clientSecret });
                elementMount.innerHTML = '';
                elements.create('payment').mount('#payment-element');

                const form = document.getElementById('payment-form');
                const message = document.getElementById('payment-message');

                if (form && form.dataset.bound !== 'true') {
                    form.dataset.bound = 'true';
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        if (!stripe || !elements) {
                            return;
                        }

                        message.textContent = 'Processing payment...';

                        const response = strategy === 'setup_intent'
                            ? await stripe.confirmSetup({ elements, redirect: 'if_required' })
                            : await stripe.confirmPayment({ elements, redirect: 'if_required' });

                        if (response.error) {
                            message.textContent = response.error.message || 'Payment failed. Please try again.';
                        } else {
                            message.textContent = 'Payment confirmed. Thank you.';
                        }
                    });
                }
            };

            window.addEventListener('payment-ready', initPayment);
            document.addEventListener('payment-ready', initPayment);
            document.addEventListener('DOMContentLoaded', initPayment);
        })();
    </script>
@endonce
