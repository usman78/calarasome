<?php

use App\Models\Clinic;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('book/{clinic:slug}', function (Clinic $clinic) {
    return view('booking.wizard', ['clinic' => $clinic]);
})->name('booking.wizard');

Route::get('appointments/secure/{token}', [\App\Http\Controllers\PublicAppointmentAccessController::class, 'show'])
    ->name('appointments.secure');
Route::post('appointments/secure/{token}', [\App\Http\Controllers\PublicAppointmentAccessController::class, 'verify'])
    ->name('appointments.secure.verify');

Route::get('waitlist/claim/{token}', [\App\Http\Controllers\PublicWaitlistClaimController::class, 'show'])
    ->name('waitlist.claim');
Route::post('waitlist/claim/{token}', [\App\Http\Controllers\PublicWaitlistClaimController::class, 'verify'])
    ->name('waitlist.claim.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('admin/providers', 'admin.providers')->name('admin.providers');
    Route::view('admin/appointment-types', 'admin.appointment-types')->name('admin.appointment-types');
    Route::view('admin/appointments', 'admin.appointments')->name('admin.appointments');
    Route::view('admin/patient-match-alerts', 'admin.patient-match-alerts')->name('admin.patient-match-alerts');
    Route::view('admin/patient-merge-audit', 'admin.patient-merge-audit')->name('admin.patient-merge-audit');
    Route::view('admin/payments', 'admin.payments')->name('admin.payments');
    Route::view('admin/waitlist', 'admin.waitlist')->name('admin.waitlist');
    Route::view('admin/waitlist-offers', 'admin.waitlist-offers')->name('admin.waitlist-offers');
    Route::view('admin/insurance-verifications', 'admin.insurance-verifications')->name('admin.insurance-verifications');
    Route::view('admin/email-delivery', 'admin.email-delivery')->name('admin.email-delivery');
});

require __DIR__.'/settings.php';
