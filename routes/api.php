<?php

use App\Http\Controllers\Api\AdminAppointmentTypeController;
use App\Http\Controllers\Api\AdminProviderController;
use App\Http\Controllers\Api\PublicBookingController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/providers', [AdminProviderController::class, 'index']);
    Route::post('/providers', [AdminProviderController::class, 'store']);
    Route::put('/providers/{provider}', [AdminProviderController::class, 'update']);
    Route::delete('/providers/{provider}', [AdminProviderController::class, 'destroy']);

    Route::get('/providers/{provider}/schedule', [AdminProviderController::class, 'scheduleIndex']);
    Route::put('/providers/{provider}/schedule', [AdminProviderController::class, 'scheduleUpdate']);

    Route::post('/providers/{provider}/block-time', [AdminProviderController::class, 'blockStore']);
    Route::delete('/providers/{provider}/block-time/{block}', [AdminProviderController::class, 'blockDestroy']);

    Route::get('/appointment-types', [AdminAppointmentTypeController::class, 'index']);
    Route::post('/appointment-types', [AdminAppointmentTypeController::class, 'store']);
    Route::put('/appointment-types/{appointmentType}', [AdminAppointmentTypeController::class, 'update']);
    Route::delete('/appointment-types/{appointmentType}', [AdminAppointmentTypeController::class, 'destroy']);
});

Route::prefix('public')->group(function (): void {
    Route::get('/clinics/{clinic:slug}/providers', [PublicBookingController::class, 'providers']);
    Route::post('/clinics/{clinic:slug}/triage', [PublicBookingController::class, 'triage']);
    Route::post('/clinics/{clinic:slug}/slots/reserve', [PublicBookingController::class, 'reserve']);
    Route::post('/clinics/{clinic:slug}/appointments', [PublicBookingController::class, 'createAppointment']);
});

Route::post('/stripe/webhook', StripeWebhookController::class);
