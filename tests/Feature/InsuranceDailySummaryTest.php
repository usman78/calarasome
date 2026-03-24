<?php

use App\Mail\InsuranceVerificationDailySummaryEmail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends daily summary for standard urgency verifications due tomorrow', function () {
    Mail::fake();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-24 10:00:00'));

    $clinic = Clinic::factory()->create(['timezone' => 'America/New_York']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id, 'is_medical' => true]);
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

    $slotUtc = CarbonImmutable::now('America/New_York')
        ->addDay()
        ->setTime(9, 0)
        ->utc();

    $appointment = Appointment::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'patient_id' => $patient->id,
        'slot_datetime' => $slotUtc,
    ]);

    InsuranceVerification::query()->create([
        'clinic_id' => $clinic->id,
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'status' => 'pending',
        'urgency' => 'standard',
        'insurance_data' => [
            'provider' => 'Aetna',
            'member_id' => 'MEM900',
        ],
    ]);

    User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);

    Artisan::call('insurance:daily-summary');

    Mail::assertSent(InsuranceVerificationDailySummaryEmail::class, function ($mail) {
        return $mail->hasTo('admin@example.com')
            && ! empty($mail->payload['items']);
    });
});
