<?php

use App\Models\SlotReservation;
use App\Services\AppointmentPaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reservations:release-expired', function (): void {
    $released = SlotReservation::query()
        ->whereNull('released_at')
        ->where('expires_at', '<=', now())
        ->update(['released_at' => now()]);

    $this->info("Released {$released} expired reservation(s).");
})->purpose('Release expired slot reservations.');

Schedule::command('reservations:release-expired')->everyMinute();

Artisan::command('payments:authorize-holds', function (AppointmentPaymentService $paymentService): void {
    $count = $paymentService->authorizeScheduledHolds();
    $this->info("Authorized {$count} scheduled deposit(s).");
})->purpose('Authorize scheduled deposit holds (T-7).');

Schedule::command('payments:authorize-holds')->hourly();

Artisan::command('payments:cancel-expired-grace', function (AppointmentPaymentService $paymentService): void {
    $count = $paymentService->cancelExpiredGrace();
    $this->info("Cancelled {$count} appointment(s) after grace period.");
})->purpose('Cancel appointments after payment grace period expires.');

Schedule::command('payments:cancel-expired-grace')->hourly();
