<?php

use App\Livewire\Booking\Wizard;
use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a waitlist entry via public API', function () {
    $clinic = Clinic::factory()->create(['slug' => 'waitlist-clinic']);
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);

    $payload = [
        'appointment_type_id' => $appointmentType->id,
        'full_name' => 'Waitlist Patient',
        'email' => 'waitlist@example.com',
        'phone' => '123456789',
        'date_of_birth' => '1990-01-01',
        'preferred_date' => now()->addDays(3)->format('Y-m-d'),
        'preferred_time' => '09:00',
        'triage_data' => ['urgency_flag' => true],
    ];

    $this->postJson("/api/public/clinics/{$clinic->slug}/waitlist", $payload)
        ->assertCreated()
        ->assertJsonFragment(['tier' => 'urgent']);

    $entry = WaitlistEntry::query()->first();
    expect($entry)->not->toBeNull();
    expect($entry?->priority_score)->toBeGreaterThanOrEqual(100);
});

it('allows booking wizard to submit waitlist request', function () {
    $clinic = Clinic::factory()->create();
    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    Provider::factory()->create(['clinic_id' => $clinic->id]);

    Livewire::test(Wizard::class, ['clinic' => $clinic])
        ->set('appointmentTypeId', $appointmentType->id)
        ->set('providerSelection', 'any')
        ->set('fullName', 'Waitlist Wizard')
        ->set('email', 'wizard@example.com')
        ->set('dateOfBirth', '1992-02-02')
        ->set('emailConsent', true)
        ->call('enterWaitlistMode')
        ->set('preferredDate', now()->addDays(2)->format('Y-m-d'))
        ->call('submitWaitlist')
        ->assertSet('step', 6)
        ->assertSet('isWaitlistMode', true)
        ->assertSet('waitlistTier', 'standard');

    expect(WaitlistEntry::query()->count())->toBe(1);
    expect(Patient::query()->count())->toBe(1);
});
