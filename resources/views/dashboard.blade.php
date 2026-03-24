<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @if (auth()->user()?->is_admin)
            <livewire:admin.dashboard-alerts />
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <livewire:admin.waitlist-priority-card />
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Insurance Queue</div>
                    <div class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">
                        Review high/critical insurance verifications and standard items due tomorrow.
                    </div>
                    <div class="mt-4">
                        <flux:button variant="primary" href="{{ route('admin.insurance-verifications') }}" wire:navigate>Open Queue</flux:button>
                    </div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="text-xs uppercase tracking-wide text-zinc-500">Payments Monitor</div>
                    <div class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">
                        Track deposits, disputes, grace periods, and cancellations.
                    </div>
                    <div class="mt-4">
                        <flux:button variant="primary" href="{{ route('admin.payments') }}" wire:navigate>Open Monitor</flux:button>
                    </div>
                </div>
            </div>
            <livewire:admin.dashboard-page />
        @else
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm text-zinc-600 shadow-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
                This dashboard is reserved for administrators. Please contact your clinic admin for access.
            </div>
        @endif
    </div>
</x-layouts::app>
