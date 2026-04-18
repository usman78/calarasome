<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @if (auth()->user()?->is_admin)
                        <flux:sidebar.item icon="users" :href="route('admin.providers')" :current="request()->routeIs('admin.providers')" wire:navigate>
                            {{ __('Providers') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="book-open-text" :href="route('admin.appointment-types')" :current="request()->routeIs('admin.appointment-types')" wire:navigate>
                            {{ __('Appointment Types') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('admin.appointments')" :current="request()->routeIs('admin.appointments')" wire:navigate>
                            {{ __('Appointments') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="credit-card" :href="route('admin.payments')" :current="request()->routeIs('admin.payments')" wire:navigate>
                            {{ __('Payments') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.insurance-verifications')" :current="request()->routeIs('admin.insurance-verifications')" wire:navigate>
                            {{ __('Insurance Queue') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.waitlist')" :current="request()->routeIs('admin.waitlist')" wire:navigate>
                            {{ __('Waitlist') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="gift" :href="route('admin.waitlist-offers')" :current="request()->routeIs('admin.waitlist-offers')" wire:navigate>
                            {{ __('Waitlist Offers') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="envelope" :href="route('admin.email-delivery')" :current="request()->routeIs('admin.email-delivery')" wire:navigate>
                            {{ __('Email Delivery') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="exclamation-triangle" :href="route('admin.patient-match-alerts')" :current="request()->routeIs('admin.patient-match-alerts')" wire:navigate>
                            {{ __('Match Alerts') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document" :href="route('admin.patient-merge-audit')" :current="request()->routeIs('admin.patient-merge-audit')" wire:navigate>
                            {{ __('Merge Audit') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> --}}

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @include('partials.toast')
        @livewireScripts
        @fluxScripts
    </body>
</html>
