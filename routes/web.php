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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('admin/providers', 'admin.providers')->name('admin.providers');
    Route::view('admin/appointment-types', 'admin.appointment-types')->name('admin.appointment-types');
    Route::view('admin/patient-match-alerts', 'admin.patient-match-alerts')->name('admin.patient-match-alerts');
    Route::view('admin/payments', 'admin.payments')->name('admin.payments');
});

require __DIR__.'/settings.php';
