<?php

use App\Livewire\Booking\Wizard;
use App\Mail\EmailVerificationCodeEmail;
use App\Mail\WaitlistJoinedEmail;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\EmailDeliveryLog;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use App\Services\EmailDeliveryService;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('logs sent verification emails in the database', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create();

    $result = app(EmailVerificationService::class)->sendBookingCode($clinic, 'verify@example.com');

    expect($result['sent'])->toBeTrue();

    $log = EmailDeliveryLog::query()->first();

    expect($log)->not->toBeNull();
    expect($log?->status)->toBe('sent');
    expect($log?->context_type)->toBe('email_verification');
    expect($log?->recipient_email)->toBe('verify@example.com');
});

it('logs failed email sends with a suggested admin action', function () {
    Mail::shouldReceive('to')->once()->with('broken@example.com')->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Connection timed out while talking to SMTP host'));

    $clinic = Clinic::factory()->create();

    $log = app(EmailDeliveryService::class)->sendToAddress(
        $clinic,
        null,
        'broken@example.com',
        new WaitlistJoinedEmail('Clinic', 'Botox', null, null),
        'diagnostic',
        123
    );

    expect($log)->not->toBeNull();
    expect($log?->status)->toBe('failed');
    expect($log?->failure_reason)->toBe('timeout');
    expect($log?->suggested_action)->toContain('retry');
});

it('requires email verification before waitlist submission and allows submit after code confirmation', function () {
    Mail::fake();

    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $component = Livewire::test(Wizard::class, ['clinic' => $clinic])
        ->set('appointmentTypeId', $appointmentType->id)
        ->set('providerSelection', 'any')
        ->set('fullName', 'Verified Waitlist')
        ->set('email', 'verified@example.com')
        ->set('dateOfBirth', '1992-02-02')
        ->set('emailConsent', true)
        ->call('enterWaitlistMode')
        ->call('submitWaitlist')
        ->assertHasErrors('emailVerificationCode')
        ->call('sendEmailVerificationCode');

    $code = null;

    Mail::assertSent(EmailVerificationCodeEmail::class, function (EmailVerificationCodeEmail $mail) use (&$code): bool {
        $code = $mail->code;

        return true;
    });

    expect($code)->not->toBeNull();

    $component
        ->set('emailVerificationCode', $code)
        ->call('verifyEmailCode')
        ->call('submitWaitlist')
        ->assertSet('step', 6)
        ->assertSet('emailVerified', true)
        ->assertSet('waitlistTier', 'standard');

    expect(WaitlistEntry::query()->count())->toBe(1);
    expect(Patient::query()->count())->toBe(1);
});
