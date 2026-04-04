<x-layouts::auth :title="__('Waitlist Claim')">
    <div class="flex flex-col gap-2 text-center">
        <h1 class="text-2xl font-semibold">Claim a Waitlist Slot</h1>
        <p class="text-sm text-zinc-500">Verify your date of birth to confirm this appointment.</p>
    </div>

    @if (($tokenStatus ?? 'valid') === 'invalid')
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            This link is invalid.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'expired')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
            This link has expired. Please wait for the next available slot.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'claimed')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            This slot has been claimed.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'invalid_dob')
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            Verification failed. Please check your date of birth.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'unavailable')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
            This slot is no longer available.
        </div>
    @endif

    @if (($tokenStatus ?? 'valid') === 'claimed' && ! empty($details))
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
            <div class="text-sm text-zinc-500">Clinic</div>
            <div class="font-medium">{{ $details['clinic'] }}</div>

            <div class="mt-3 text-sm text-zinc-500">Provider</div>
            <div class="font-medium">{{ $details['provider'] }}</div>

            <div class="mt-3 text-sm text-zinc-500">Treatment</div>
            <div class="font-medium">{{ $details['appointment_type'] }}</div>

            <div class="mt-3 text-sm text-zinc-500">Appointment Time</div>
            <div class="font-medium">{{ $details['slot_local'] }} ({{ $details['timezone'] }})</div>
        </div>
        <p class="text-xs text-zinc-500">
            We will follow up by email with any additional steps (including payment if required).
        </p>
    @endif

    @if (($tokenStatus ?? 'valid') === 'valid')
        <form method="POST" action="{{ route('waitlist.claim.verify', ['token' => request()->route('token')]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium" for="date_of_birth">Date of Birth</label>
                <input
                    id="date_of_birth"
                    name="date_of_birth"
                    type="date"
                    value="{{ old('date_of_birth') }}"
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                    required
                >
                @error('date_of_birth')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <flux:button variant="primary" class="w-full" type="submit">
                Confirm Appointment
            </flux:button>
        </form>
    @endif
</x-layouts::auth>
