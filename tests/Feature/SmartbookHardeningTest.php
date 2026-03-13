<?php

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for admin api routes', function () {
    $clinic = Clinic::factory()->create();

    $response = $this->getJson('/api/admin/providers?clinic_id='.$clinic->id);

    $response->assertStatus(401);
});

it('forbids non-admin users on admin api routes', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->getJson('/api/admin/providers?clinic_id='.$clinic->id);

    $response->assertStatus(403);
});

it('rejects overlapping provider schedules', function () {
    $admin = User::factory()->admin()->create();
    $clinic = Clinic::factory()->create();
    $provider = Provider::factory()->create(['clinic_id' => $clinic->id]);

    $response = $this->actingAs($admin)->putJson('/api/admin/providers/'.$provider->id.'/schedule', [
        'schedules' => [
            [
                'day_of_week' => 1,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'effective_from' => '2026-03-01',
                'effective_until' => null,
            ],
            [
                'day_of_week' => 1,
                'start_time' => '11:00:00',
                'end_time' => '14:00:00',
                'effective_from' => '2026-03-01',
                'effective_until' => null,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('schedules');
});

it('rejects reservations inside the minimum booking notice window', function () {
    $clinic = Clinic::factory()->create([
        'slug' => 'notice-clinic',
        'min_booking_notice_hours' => 24,
    ]);

    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $slotLocal = now()->addHours(2)->setTimezone($clinic->timezone)->format('Y-m-d H:i:s');

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/slots/reserve', [
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_local_datetime' => $slotLocal,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Slot violates minimum booking notice.');
});

it('rejects reservations on dst gap local times', function () {
    $clinic = Clinic::factory()->create([
        'slug' => 'dst-clinic',
        'timezone' => 'America/New_York',
        'min_booking_notice_hours' => 0,
    ]);

    $appointmentType = AppointmentType::factory()->create(['clinic_id' => $clinic->id]);
    $provider = Provider::factory()->create([
        'clinic_id' => $clinic->id,
        'default_appointment_types' => [$appointmentType->id],
    ]);

    $response = $this->postJson('/api/public/clinics/'.$clinic->slug.'/slots/reserve', [
        'provider_id' => $provider->id,
        'appointment_type_id' => $appointmentType->id,
        'slot_local_datetime' => '2026-03-08 02:30:00',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Invalid local datetime for clinic timezone (possible DST gap).');
});
