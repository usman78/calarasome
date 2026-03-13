<?php

use App\Mail\DeidentifiedAppointmentEmail;
use App\Models\AppointmentAccessToken;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\SlotReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends de-identified email with secure token when phi is not consented', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create(['slug' => 'comms-clinic']);
    $appointmentType = AppointmentType::factory()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Laser Session',
    ]);
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
        'full_name' => 'Email Consent',
        'email' => 'consent@example.com',
        'phone' => '555-3333',
        'date_of_birth' => '1990-01-01',
        'email_consent' => true,
        'email_phi' => false,
    ]);

    $response->assertCreated();

    Mail::assertSent(DeidentifiedAppointmentEmail::class, function (DeidentifiedAppointmentEmail $mail) use ($appointmentType): bool {
        $body = $mail->render();

        if (str_contains($mail->subject ?? '', $appointmentType->name)) {
            return false;
        }

        if (str_contains($body, $appointmentType->name)) {
            return false;
        }

        return str_contains($body, '/appointments/secure/');
    });

    $tokenRecord = AppointmentAccessToken::query()->latest()->first();
    expect($tokenRecord)->not->toBeNull();

    $secureUrl = null;
    Mail::assertSent(DeidentifiedAppointmentEmail::class, function (DeidentifiedAppointmentEmail $mail) use (&$secureUrl): bool {
        $secureUrl = $mail->secureUrl;

        return true;
    });

    $token = $secureUrl ? basename(parse_url($secureUrl, PHP_URL_PATH)) : null;
    expect($token)->not->toBeNull();
    expect(hash('sha256', (string) $token))->toBe($tokenRecord?->token_hash);
    expect($tokenRecord?->expires_at)->not->toBeNull();
    expect($tokenRecord?->expires_at->greaterThan(now()->addHours(23)))->toBeTrue();
    expect($tokenRecord?->expires_at->lessThanOrEqualTo(now()->addHours(24)->addMinute()))->toBeTrue();
});

it('does not send de-identified email when phi is consented', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create(['slug' => 'comms-clinic-phi']);
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
        'full_name' => 'Phi Consent',
        'email' => 'phi@example.com',
        'phone' => '555-7777',
        'date_of_birth' => '1991-02-02',
        'email_consent' => true,
        'email_phi' => true,
    ]);

    $response->assertCreated();
    Mail::assertNothingSent();
    expect(AppointmentAccessToken::query()->count())->toBe(0);
});
