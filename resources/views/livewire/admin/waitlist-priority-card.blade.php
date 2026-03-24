<div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
    <div class="flex items-start justify-between gap-3">
        <div>
            <flux:heading size="lg">Waitlist Priority</flux:heading>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Active entries grouped by urgency tier.</p>
        </div>
        <flux:button variant="ghost" href="{{ route('admin.waitlist') }}" wire:navigate>View</flux:button>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 dark:border-rose-900/40 dark:bg-rose-900/20">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-200">Urgent</div>
            <div class="mt-1 text-2xl font-semibold text-rose-900 dark:text-white">{{ $urgentCount }}</div>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/40 dark:bg-amber-900/20">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-200">High</div>
            <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-white">{{ $highCount }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Standard</div>
            <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $standardCount }}</div>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
        <span>Total active: {{ $activeCount }}</span>
        <span>Updated {{ now()->format('M d, Y g:i A') }}</span>
    </div>

    <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-800">
        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Top urgent</div>
        @if (empty($topUrgent))
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">No urgent waitlist entries right now.</div>
        @else
            <div class="mt-2 space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                @foreach ($topUrgent as $entry)
                    <div class="flex items-center justify-between">
                        <span>{{ $entry['patient'] }}</span>
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                            Score {{ $entry['score'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
