<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950">
        <header class="border-b border-zinc-200 bg-white/95 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-950/90">
            <div class="mx-auto flex h-14 w-full max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white" wire:navigate>
                    <x-app-logo-icon class="size-5 fill-current" />
                    <span class="font-medium">{{ config('app.name', 'SmartBook') }}</span>
                </a>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <livewire:booking.wizard :clinic="$clinic" />
        </main>

        @include('partials.toast')
        @fluxScripts
    </body>
</html>
