<?php

use App\Mail\DeidentifiedAppointmentEmail;
use App\Mail\PhiAppointmentEmail;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\SlotReservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends PHI email with appointment details when phi is consented', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create([
        'slug' => 'phi-clinic',
        'timezone' => 'America/New_York',
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Dermatology Visit',
    ]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'full_name' => 'Dr. Phi',
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $slotUtc = CarbonImmutable::parse('2026-04-01 14:30:00', 'UTC');
    $expectedLocal = $slotUtc->setTimezone($clinic->timezone)->format('Y-m-d H:i:s');

    $reservation = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_datetime' => $slotUtc,
        'expires_at' => now()->addMinutes(15),
        'released_at' => null,
    ]);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => $reservation->session_token,
        'full_name' => 'Phi Patient',
        'email' => 'phi@example.com',
        'phone' => '555-8888',
        'date_of_birth' => '1989-03-03',
        'email_consent' => true,
        'email_phi' => true,
    ]);

    $response->assertCreated();

    Mail::assertSent(PhiAppointmentEmail::class, function (PhiAppointmentEmail $mail) use ($appointmentType, $provider, $expectedLocal): bool {
        $body = $mail->render();

        return str_contains($body, $appointmentType->name)
            && str_contains($body, $provider->full_name)
            && str_contains($body, $expectedLocal);
    });

    Mail::assertNotSent(DeidentifiedAppointmentEmail::class);
});

it('does not send PHI email when phi is not consented', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create(['slug' => 'no-phi-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $reservation = SlotReservation::factory()->create([
        'clinic_id' => $clinic->id,
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'expires_at' => now()->addMinutes(15),
        'released_at' => null,
    ]);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/appointments', [
        'session_token' => $reservation->session_token,
        'full_name' => 'No Phi',
        'email' => 'nophi@example.com',
        'phone' => '555-9999',
        'date_of_birth' => '1985-01-01',
        'email_consent' => true,
        'email_phi' => false,
    ]);

    $response->assertCreated();

    Mail::assertNotSent(PhiAppointmentEmail::class);
});
