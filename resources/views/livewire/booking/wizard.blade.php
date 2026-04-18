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
    $hasName = filled(trim((string) $fullName));
    $hasEmail = filled(trim((string) $email));
    $hasDob = filled(trim((string) $dateOfBirth));
    $canCompleteBooking = $hasName && $hasEmail && $hasDob && $emailConsent && $emailVerified && filled($sessionToken);
    $canSubmitWaitlist = $hasName && $hasEmail && $hasDob && $emailConsent && $emailVerified;
    $missingWaitlist = [];
    if (! $hasName) { $missingWaitlist[] = 'full name'; }
    if (! $hasEmail) { $missingWaitlist[] = 'email'; }
    if (! $hasDob) { $missingWaitlist[] = 'date of birth'; }
    if (! $emailConsent) { $missingWaitlist[] = 'email consent'; }
    if (! $emailVerified) { $missingWaitlist[] = 'email verification'; }
@endphp

<div class="mx-auto w-full max-w-5xl overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/50">
    <div class="border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-8 place-items-center rounded-md border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <x-app-logo-icon class="size-5 fill-current text-zinc-700 dark:text-zinc-200" />
                </span>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">SmartBook</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clinic->name }}</p>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Step {{ $step }} of 6</p>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $steps[$step] }}</p>
            </div>
        </div>
        <div class="mt-3 h-2 rounded-full bg-zinc-200 dark:bg-zinc-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
            <div
                wire:key="progress-{{ $step }}"
                class="h-full rounded-full bg-zinc-900 transition-all duration-300 dark:bg-zinc-100"
                style="width: {{ $progressPercent }}%; min-width: 6px; display: block;"
            ></div>
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
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">1. Patient Status</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-right">Step 1 of 6</div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <button
                        type="button"
                        wire:click="$set('isNewPatient', true)"
                        class="relative rounded-xl border p-4 text-left transition {{ $isNewPatient ? 'border-zinc-900! bg-zinc-900! text-white! shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                    >
                        @if ($isNewPatient)
                            <span class="inline-flex size-5 items-center justify-center rounded-full mr-2 bg-white text-zinc-900" aria-label="Selected">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                        <div class="inline-block text-xs uppercase tracking-wide {{ $isNewPatient ? 'text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">New</div>
                        <div class="mt-1 text-lg font-semibold {{ $isNewPatient ? 'text-zinc-200' : 'text-zinc-900 dark:text-zinc-100' }}">I am a NEW patient</div>
                        <p class="mt-2 text-xs {{ $isNewPatient ? 'text-zinc-200' : 'text-zinc-600 dark:text-zinc-300' }}">First visit at this clinic. We will create your profile.</p>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('isNewPatient', false)"
                        class="relative rounded-xl border p-4 text-left transition {{ ! $isNewPatient ? 'border-zinc-900! bg-zinc-900! text-white! shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
                    >
                        @if (! $isNewPatient)
                            <span class="inline-flex size-5 items-center justify-center rounded-full mr-2 bg-white text-zinc-900" aria-label="Selected">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.25a1 1 0 0 1-1.42.004L3.29 9.176a1 1 0 1 1 1.42-1.408l4.09 4.125 6.49-6.537a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                        <div class="inline-block text-xs uppercase tracking-wide {{ ! $isNewPatient ? 'text-zinc-200' : 'text-zinc-500 dark:text-zinc-400' }}">Returning</div>
                        <div class="mt-1 text-lg font-semibold {{ ! $isNewPatient ? 'text-zinc-200' : 'text-zinc-900 dark:text-zinc-100' }}">I am a RETURNING patient</div>
                        <p class="mt-2 text-xs {{ ! $isNewPatient ? 'text-zinc-200' : 'text-zinc-600 dark:text-zinc-300' }}">We will match your existing record before confirming.</p>
                    </button>
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">You can change this selection later before booking is confirmed.</p>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="primary" wire:click="$set('step', 2)">Continue</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 2)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">2. Treatment Selection</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-right">Step 2 of 6</div>
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
                                class="relative rounded-xl border p-4 text-left transition {{ $appointmentTypeId === $type['id'] ? 'border-zinc-900! bg-zinc-900! text-white! shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
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

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="filled" wire:click="$set('step', 1)" wire:loading.attr="disabled" wire:target="chooseAppointmentType">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 3)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">3. Choose Provider</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-right">Step 3 of 6</div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <button
                        type="button"
                        wire:click="chooseProvider('any')"
                        wire:loading.attr="disabled"
                        wire:target="chooseProvider"
                        class="relative rounded-xl border p-4 text-left transition {{ $providerSelection === 'any' ? 'border-zinc-900! bg-zinc-900! text-white! shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
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
                            class="relative rounded-xl border p-4 text-left transition {{ $providerSelection == $provider['id'] ? 'border-zinc-900! bg-zinc-900! text-white! shadow-xs' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-zinc-500' }}"
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

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="filled" wire:click="$set('step', 2)" wire:loading.attr="disabled" wire:target="chooseProvider">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 4)
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">4. Select Date and Time</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-right">Step 4 of 6</div>
                </div>

                <div class="w-full sm:max-w-xs">
                    <flux:input wire:model.live="selectedDate" label="Preferred Date" type="date" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Times below are shown in {{ $clinic->timezone }}.</p>
                    @if ($autoSelectedDate)
                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">Auto-selected earliest available date.</p>
                    @endif
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
                        @if ($slotEmptyReason)
                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $slotEmptyReason }}</div>
                        @endif
                    </div>
                    @if ($canWaitlist)
                        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <flux:button class="w-full sm:w-auto" variant="primary" wire:click="enterWaitlistMode">Join Waitlist</flux:button>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Pick another date to view availability.</p>
                    @endif
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

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="filled" wire:click="$set('step', 3)" wire:loading.attr="disabled" wire:target="reserveSlot,selectedDate">Back</flux:button>
                </div>
            </div>
        @endif

        @if ($step === 5)
            @php
                $slotWithin24h = false;
                $freeCancelUntil = null;
                if ($slotLocalDatetime) {
                    try {
                        $slotLocal = \Carbon\CarbonImmutable::parse($slotLocalDatetime, $clinic->timezone);
                        $slotWithin24h = $slotLocal->lessThanOrEqualTo(now($clinic->timezone)->addHours(24));
                        $standardDeadline = $slotLocal->subHours(24);
                        $minimumWindow = now($clinic->timezone)->addHours(2);
                        $deadline = $standardDeadline->greaterThan($minimumWindow) ? $standardDeadline : $minimumWindow;
                        if ($deadline->greaterThan($slotLocal)) {
                            $deadline = $slotLocal;
                        }
                        if ($slotLocal->lessThanOrEqualTo(now($clinic->timezone)->addHours(26))) {
                            $freeCancelUntil = $deadline->format('Y-m-d H:i');
                        }
                    } catch (\Throwable) {
                        $slotWithin24h = false;
                    }
                }
            @endphp
            <div @if (! $isWaitlistMode) wire:poll.1s="refreshReservationTimer" @endif class="space-y-5 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="lg">5. Contact, Consent and Confirm</flux:heading>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 sm:text-right">Step 5 of 6</div>
                </div>

                @if (! $isWaitlistMode)
                    <div class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">
                        Slot reserved with {{ $assignedProviderName }}. Time remaining:
                        <strong>{{ floor($reservationSecondsRemaining / 60) }}:{{ str_pad((string) ($reservationSecondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}</strong>
                    </div>
                    @if ($slotWithin24h)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                            @if ($freeCancelUntil)
                                You can cancel for free until {{ $freeCancelUntil }} ({{ $clinic->timezone }}). After that, your deposit will be retained.
                            @else
                                This appointment is within 24 hours. Cancellations may retain the deposit and online cancellation may be restricted.
                            @endif
                        </div>
                    @endif
                @else
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        No slots are currently available. Join the waitlist and we'll notify you when a spot opens.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model.live="fullName" label="Full Name" type="text" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Use legal name for patient matching.</p>
                        @error('fullName')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model.live="email" label="Email" type="email" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Confirmation and reminders will be sent here.</p>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model.live="phone" label="Phone" type="text" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Optional, but useful for urgent updates.</p>
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input wire:model.live="dateOfBirth" label="Date of Birth" type="date" required />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Required to avoid duplicate patient records.</p>
                        @error('dateOfBirth')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Verify email before continuing</div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">We send a 6-digit code to confirm the address is correct before secure links and reminders depend on it.</p>
                            @if ($emailVerificationSentTo)
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    Code sent to {{ $emailVerificationSentTo }}
                                    @if ($emailVerificationSentAt)
                                        at {{ $emailVerificationSentAt }}.
                                    @endif
                                </p>
                            @endif
                        </div>
                        @if ($emailVerified && strtolower(trim((string) $email)) === strtolower(trim((string) $verifiedEmail)))
                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">Verified</span>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                        <div>
                            <flux:input wire:model.live="emailVerificationCode" label="Verification Code" type="text" inputmode="numeric" placeholder="Enter 6-digit code" />
                            @error('emailVerificationCode')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2 md:justify-end">
                            <flux:button type="button" variant="filled" wire:click="sendEmailVerificationCode" wire:loading.attr="disabled" wire:target="sendEmailVerificationCode">
                                Send Code
                            </flux:button>
                            <flux:button type="button" variant="primary" wire:click="verifyEmailCode" wire:loading.attr="disabled" wire:target="verifyEmailCode">
                                Verify Email
                            </flux:button>
                        </div>
                    </div>

                    <div wire:loading.flex wire:target="sendEmailVerificationCode,verifyEmailCode" class="mt-3 items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                        Checking email verification...
                    </div>
                </div>

                @if ($requiresInsurance && ! $isWaitlistMode)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Insurance Details (Medical Visits)</div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Needed to start verification immediately after booking.</p>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <flux:input wire:model="insuranceProvider" label="Insurance Provider" type="text" required />
                                @error('insuranceProvider')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insuranceMemberId" label="Member ID" type="text" required />
                                @error('insuranceMemberId')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insuranceGroupId" label="Group ID" type="text" />
                                @error('insuranceGroupId')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insurancePlan" label="Plan Name" type="text" />
                                @error('insurancePlan')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insuranceSubscriberName" label="Subscriber Name" type="text" required />
                                @error('insuranceSubscriberName')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insuranceSubscriberDob" label="Subscriber Date of Birth" type="date" required />
                                @error('insuranceSubscriberDob')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:select wire:model="insuranceRelationship" label="Relationship to Subscriber" required>
                                    <flux:select.option value="self">Self</flux:select.option>
                                    <flux:select.option value="spouse">Spouse</flux:select.option>
                                    <flux:select.option value="child">Child</flux:select.option>
                                    <flux:select.option value="other">Other</flux:select.option>
                                </flux:select>
                                @error('insuranceRelationship')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:input wire:model="insurancePhone" label="Insurance Phone" type="text" />
                                @error('insurancePhone')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <flux:select wire:model="insuranceUrgency" label="Visit Urgency" required>
                                    <flux:select.option value="standard">Standard</flux:select.option>
                                    <flux:select.option value="high">High</flux:select.option>
                                    <flux:select.option value="critical">Critical</flux:select.option>
                                </flux:select>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">High or critical selections alert the admin team immediately.</p>
                                @error('insuranceUrgency')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isWaitlistMode)
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:input wire:model="preferredDate" label="Preferred Date" type="date" />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Optional. Helps us prioritize your request.</p>
                            @error('preferredDate')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <flux:select wire:model="preferredTimeWindow" label="Preferred Time Window" required>
                                <flux:select.option value="any">Any time</flux:select.option>
                                <flux:select.option value="morning">Morning (9am-12pm)</flux:select.option>
                                <flux:select.option value="midday">Midday (12pm-3pm)</flux:select.option>
                                <flux:select.option value="afternoon">Afternoon (3pm-6pm)</flux:select.option>
                                <flux:select.option value="evening">Evening (6pm-9pm)</flux:select.option>
                            </flux:select>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Pick the window that fits best. You can mention more in notes.</p>
                            @error('preferredTimeWindow')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <flux:input wire:model="waitlistNotes" label="Notes (optional)" type="text" placeholder="Add any extra details or flexibility." />
                            @error('waitlistNotes')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:checkbox wire:model.live="emailConsent" label="I consent to receive appointment reminders via email (required)." />
                    <flux:checkbox wire:model.live="emailPhi" label="Allow emails to include appointment details (PHI)." />
                </div>
                @error('emailConsent')
                    <p class="text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
                @if ($isWaitlistMode && ! $canSubmitWaitlist)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Missing: {{ implode(', ', $missingWaitlist) }}.</p>
                @endif

                @error('reservation')
                    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-200">{{ $message }}</div>
                @enderror

                <div wire:loading.flex wire:target="completeBooking,submitWaitlist" class="items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="inline-block size-2 animate-pulse rounded-full bg-zinc-500"></span>
                    Processing request...
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <flux:button class="w-full sm:w-auto" variant="filled" wire:click="$set('step', 4)" wire:loading.attr="disabled" wire:target="completeBooking,submitWaitlist">Back</flux:button>
                    @if ($isWaitlistMode)
                        <flux:button
                            class="w-full sm:w-auto"
                            :disabled="! $canSubmitWaitlist"
                            variant="primary"
                            wire:click="submitWaitlist"
                            wire:loading.attr="disabled"
                            wire:target="submitWaitlist"
                            x-data="{ busy: false }"
                            x-on:click="busy = true"
                            x-bind:disabled="busy || !@js($canSubmitWaitlist)"
                        >
                            <span x-show="!busy" wire:loading.remove wire:target="submitWaitlist">Join Waitlist</span>
                            <span x-show="busy" wire:loading wire:target="submitWaitlist">Joining...</span>
                        </flux:button>
                    @else
                        <flux:button
                            class="w-full sm:w-auto"
                            :disabled="! $canCompleteBooking"
                            variant="primary"
                            wire:click="completeBooking"
                            wire:loading.attr="disabled"
                            wire:target="completeBooking"
                            x-data="{ busy: false }"
                            x-on:click="busy = true"
                            x-bind:disabled="busy || !@js($canCompleteBooking)"
                        >
                            <span x-show="!busy" wire:loading.remove wire:target="completeBooking">Complete Booking</span>
                            <span x-show="busy" wire:loading wire:target="completeBooking">Completing...</span>
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

        @if ($step === 6)
            @if ($isWaitlistMode)
                <div class="space-y-4 rounded-xl border border-amber-300 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20 sm:p-5">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <flux:heading size="lg">6. Waitlist Confirmation</flux:heading>
                        <div class="text-xs text-amber-700 dark:text-amber-200 sm:text-right">Submitted</div>
                    </div>
                    <p class="text-sm text-zinc-800 dark:text-zinc-100">
                        You're on the waitlist. We'll notify you when a spot opens up.
                    </p>
                    <ul class="space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                        <li><strong>Waitlist ID:</strong> {{ $waitlistEntryId }}</li>
                        <li><strong>Priority Tier:</strong> {{ $waitlistTier }}</li>
                        <li><strong>Priority Score:</strong> {{ $waitlistScore }}</li>
                        @if ($preferredDate)
                            <li><strong>Preferred Date:</strong> {{ $preferredDate }}</li>
                        @endif
                        <li>
                            <strong>Preferred Time Window:</strong>
                            @switch($preferredTimeWindow)
                                @case('morning')
                                    Morning (9am-12pm)
                                    @break
                                @case('midday')
                                    Midday (12pm-3pm)
                                    @break
                                @case('afternoon')
                                    Afternoon (3pm-6pm)
                                    @break
                                @case('evening')
                                    Evening (6pm-9pm)
                                    @break
                                @default
                                    Any time
                            @endswitch
                        </li>
                    </ul>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:button class="w-full sm:w-auto" variant="filled" href="{{ route('home') }}" wire:navigate>Back to Home</flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-4 rounded-xl border border-green-300 bg-green-50 p-3 dark:border-green-700 dark:bg-green-900/20 sm:p-5">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <flux:heading size="lg">6. Confirmation</flux:heading>
                        <div class="text-xs text-green-700 dark:text-green-200 sm:text-right">Completed</div>
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
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Enter card details and press Confirm Payment.</p>
                                <flux:button id="payment-submit" as="button" type="submit" class="w-full" variant="primary" :loading="false">
                                    Confirm Payment
                                </flux:button>
                                <div id="payment-message" class="text-xs text-zinc-600 dark:text-zinc-300"></div>
                            </form>
                        </div>
                    @endif
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:button class="w-full sm:w-auto" variant="filled" href="{{ route('home') }}" wire:navigate>Back to Home</flux:button>
                    </div>
                </div>
            @endif
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
                    if (message) {
                        message.textContent = '';
                    }
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        if (!stripe || !elements) {
                            return;
                        }

                        if (form.dataset.completed === 'true') {
                            return;
                        }

                        const submitButton = document.getElementById('payment-submit');
                        if (submitButton) {
                            submitButton.setAttribute('disabled', 'disabled');
                            submitButton.textContent = 'Processing...';
                        }

                        message.textContent = 'Processing payment...';

                        const response = strategy === 'setup_intent'
                            ? await stripe.confirmSetup({ elements, redirect: 'if_required' })
                            : await stripe.confirmPayment({ elements, redirect: 'if_required' });

                        if (response.error) {
                            message.textContent = response.error.message || 'Payment failed. Please try again.';
                            document.dispatchEvent(new CustomEvent('toast', {
                                detail: { type: 'error', message: message.textContent }
                            }));
                            if (submitButton) {
                                submitButton.removeAttribute('disabled');
                                submitButton.textContent = 'Confirm Payment';
                            }
                        } else {
                            const intentStatus = response.paymentIntent?.status || response.setupIntent?.status || '';
                            if (['succeeded', 'requires_capture'].includes(intentStatus)) {
                                message.textContent = 'Payment confirmed. Thank you.';
                                form.dataset.completed = 'true';
                                if (submitButton) {
                                    submitButton.setAttribute('disabled', 'disabled');
                                    submitButton.textContent = 'Payment Confirmed';
                                }
                                document.dispatchEvent(new CustomEvent('toast', {
                                    detail: { type: 'success', message: 'Payment confirmed.' }
                                }));
                            } else {
                                message.textContent = 'Payment submitted. Please wait for confirmation.';
                                document.dispatchEvent(new CustomEvent('toast', {
                                    detail: { type: 'success', message: message.textContent }
                                }));
                                if (submitButton) {
                                    submitButton.removeAttribute('disabled');
                                    submitButton.textContent = 'Confirm Payment';
                                }
                            }
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
