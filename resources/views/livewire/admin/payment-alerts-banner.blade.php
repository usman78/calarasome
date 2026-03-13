@if ($inGraceCount > 0 || $expiredCount > 0)
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <strong>Payment alerts:</strong>
                {{ $inGraceCount }} in grace, {{ $expiredCount }} expired.
                <span class="ml-2 text-xs text-amber-700 dark:text-amber-300">Resolve within 48h to avoid cancellations.</span>
            </div>
            <a href="{{ route('admin.payments') }}" class="text-sm font-semibold text-amber-900 underline dark:text-amber-100">Review payments</a>
        </div>
    </div>
@endif
