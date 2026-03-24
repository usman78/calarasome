<?php

use App\Models\SlotReservation;
use App\Models\WaitlistEntry;
use App\Services\AppointmentPaymentService;
use Carbon\CarbonImmutable;
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

Artisan::command('waitlist:expire', function (): void {
    $today = CarbonImmutable::today();

    $expired = WaitlistEntry::query()
        ->where('status', 'active')
        ->whereNotNull('preferred_datetime')
        ->whereDate('preferred_datetime', '<', $today->toDateString())
        ->update(['status' => 'expired']);

    $this->info("Expired {$expired} waitlist entr".($expired === 1 ? 'y' : 'ies').'.');
})->purpose('Expire waitlist entries whose preferred date has passed.');

Schedule::command('waitlist:expire')->dailyAt('01:00');

Artisan::command('waitlist:notify', function (\App\Services\WaitlistNotificationService $service): void {
    $sent = $service->dispatchDueNotifications();

    $this->info("Dispatched {$sent} waitlist notification batch".($sent === 1 ? '' : 'es').'.');
})->purpose('Send staggered waitlist notifications for open slots.');

Schedule::command('waitlist:notify')->everyFiveMinutes();

Artisan::command('insurance:daily-summary', function (\App\Services\InsuranceVerificationService $service): void {
    $sent = $service->sendDailySummaryForStandardUrgency();

    $this->info("Sent {$sent} insurance verification summary email(s).");
})->purpose('Send daily summary for standard-urgency insurance verifications due tomorrow.');

Schedule::command('insurance:daily-summary')->dailyAt('08:00');
