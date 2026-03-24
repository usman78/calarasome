<div class="grid gap-2 md:grid-cols-2 sm:gap-3">
    @if ($insuranceUrgent > 0)
        <a href="{{ route('admin.insurance-verifications') }}" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 shadow-xs hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-100 sm:p-4">
            <div class="font-semibold">Insurance Urgency Alerts</div>
            <div class="mt-1">{{ $insuranceUrgent }} high/critical verification(s) awaiting review.</div>
        </a>
    @endif

    @if ($paymentsInGrace > 0 || $paymentsExpired > 0)
        <a href="{{ route('admin.payments') }}" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 shadow-xs hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-100 sm:p-4">
            <div class="font-semibold">Payment Alerts</div>
            <div class="mt-1">
                {{ $paymentsInGrace }} in grace, {{ $paymentsExpired }} expired.
            </div>
        </a>
    @endif

    @if ($matchAlerts > 0)
        <a href="{{ route('admin.patient-match-alerts') }}" class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800 shadow-xs hover:bg-sky-100 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-100 sm:p-4">
            <div class="font-semibold">Shared Email Alerts</div>
            <div class="mt-1">{{ $matchAlerts }} unresolved patient match alert(s).</div>
        </a>
    @endif

    @if ($insuranceUrgent === 0 && $paymentsInGrace === 0 && $paymentsExpired === 0 && $matchAlerts === 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-3 text-sm text-zinc-500 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 sm:p-4">
            No urgent alerts right now.
        </div>
    @endif
</div>
