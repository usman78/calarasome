<x-layouts::auth :title="__('Secure Appointment Details')">
    <meta name="appointment-cancel-token" content="{{ $cancelToken ?? '' }}">
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
        @php
            $appointmentStatus = $details['status'] ?? 'pending';
            $canCancel = ! in_array($appointmentStatus, ['cancelled_by_patient', 'cancelled_by_clinic', 'no_show'], true);
            $cancelMessage = "Cancel this appointment? This will apply the clinic's cancellation policy and may retain your deposit if within 24 hours.";
        @endphp

        <div
            x-data="{
                status: @js($appointmentStatus),
                canCancel: @js($canCancel),
                isLoading: false,
                message: '',
                isError: false,
                statusLabels: {
                    confirmed: 'Confirmed',
                    pending: 'Pending',
                    cancelled_by_patient: 'Canceled (Patient)',
                    cancelled_by_clinic: 'Canceled (Clinic)',
                    no_show: 'No-Show',
                },
                statusClasses: {
                    confirmed: 'bg-emerald-100 text-emerald-800',
                    pending: 'bg-amber-100 text-amber-800',
                    cancelled_by_patient: 'bg-zinc-200 text-zinc-700',
                    cancelled_by_clinic: 'bg-zinc-200 text-zinc-700',
                    no_show: 'bg-red-100 text-red-800',
                },
                cancel() {
                    if (!confirm(@js($cancelMessage))) return;
                    const token = document.querySelector('meta[name=appointment-cancel-token]')?.content || '';
                    this.isLoading = true;
                    this.message = '';
                    this.isError = false;
                    fetch(@js(url("/api/public/appointments/{$details['appointment_id']}/cancel")), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        },
                        body: JSON.stringify({ token }),
                    }).then(async response => {
                        const payload = await response.json().catch(() => ({}));
                        if (response.ok) {
                            const policy = payload.policy || '';
                            const action = payload.payment_action || 'none';
                            let outcome = 'Appointment canceled. No charge.';
                            if (policy === 'deposit_retained') {
                                outcome = 'Appointment canceled. Deposit retained per policy.';
                            } else if (policy === 'refund_issued') {
                                outcome = 'Appointment canceled. Deposit refunded.';
                            }

                            const actionLabelMap = {
                                voided: 'Payment hold voided.',
                                captured: 'Deposit captured.',
                                refunded: 'Deposit refunded.',
                                none: 'No payment action required.',
                            };
                            const actionDetail = actionLabelMap[action] || `Payment action: ${action}.`;
                            this.message = `${outcome} ${actionDetail}`;
                            this.isError = false;
                            this.status = payload.appointment_status || 'cancelled_by_patient';
                            this.canCancel = false;
                            return;
                        }
                        this.message = payload.message || 'Unable to cancel appointment.';
                        this.isError = true;
                    }).catch(() => {
                        this.message = 'Unable to cancel appointment.';
                        this.isError = true;
                    }).finally(() => {
                        this.isLoading = false;
                    });
                }
            }"
            class="space-y-4"
        >
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-sm text-zinc-500">Clinic</div>
                        <div class="font-medium">{{ $details['clinic'] }}</div>
                    </div>
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="statusClasses[status] || 'bg-zinc-100 text-zinc-700'"
                        x-text="statusLabels[status] || status"
                    ></span>
                </div>

                <div class="mt-3 text-sm text-zinc-500">Provider</div>
                <div class="font-medium">{{ $details['provider'] }}</div>

                <div class="mt-3 text-sm text-zinc-500">Treatment</div>
                <div class="font-medium">{{ $details['appointment_type'] }}</div>

                <div class="mt-3 text-sm text-zinc-500">Appointment Time</div>
                <div class="font-medium">{{ $details['slot_local'] }} ({{ $details['timezone'] }})</div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
                <template x-if="message">
                    <div
                        class="mb-3 rounded-lg border px-3 py-2 text-sm"
                        :class="isError ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                        x-text="message"
                    ></div>
                </template>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-3">
                    <div>
                        <div class="text-sm font-medium text-zinc-900">Need to cancel?</div>
                        <div class="text-xs text-zinc-500">Cancels this appointment and applies the clinic policy.</div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isLoading || !canCancel"
                        @click="cancel()"
                    >
                        <span x-show="!isLoading">Cancel Appointment</span>
                        <span x-show="isLoading">Canceling...</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-zinc-500" x-show="canCancel">
                    You can cancel online up to the appointment time. Cancellations within 24 hours may retain the deposit.
                </p>
                <p class="mt-2 text-xs text-zinc-500" x-show="!canCancel">
                    This appointment can no longer be cancelled online.
                </p>
            </div>
        </div>
    @endif

    @if (($tokenStatus ?? 'valid') === 'valid' && ! ($verified ?? false))
        <form method="POST" action="{{ route('appointments.secure.verify', ['token' => request()->route('token')]) }}" class="space-y-4" x-data>
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
                Verify
            </flux:button>
        </form>
    @endif
</x-layouts::auth>
