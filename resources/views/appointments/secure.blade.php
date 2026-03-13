<x-layouts::auth :title="__('Secure Appointment Details')">
    <div class="flex flex-col gap-2 text-center">
        <h1 class="text-2xl font-semibold">Secure Appointment Details</h1>
        <p class="text-sm text-zinc-500">Verify your date of birth to view appointment details.</p>
    </div>

    @if (($tokenStatus ?? 'valid') === 'invalid')
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            This link is invalid.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'expired')
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
            This link has expired. Please request a new secure email.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'locked')
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            This link is locked due to too many failed attempts.
        </div>
    @elseif (($tokenStatus ?? 'valid') === 'invalid_dob')
        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            Verification failed. Please check your date of birth.
        </div>
    @endif

    @if (($verified ?? false) && ! empty($details))
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
    @endif

    @if (($tokenStatus ?? 'valid') === 'valid' && ! ($verified ?? false))
        <form method="POST" action="{{ route('appointments.secure.verify', ['token' => request()->route('token')]) }}" class="space-y-4">
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

            <button type="submit" class="w-full rounded-lg bg-black px-4 py-2 text-sm font-medium text-white">
                Verify
            </button>
        </form>
    @endif
</x-layouts::auth>
