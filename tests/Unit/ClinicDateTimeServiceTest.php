<?php

use App\Models\Clinic;
use App\Services\ClinicDateTimeService;

it('converts clinic local time to utc and back', function () {
    $clinic = Clinic::factory()->make([
        'timezone' => 'America/New_York',
    ]);

    $service = new ClinicDateTimeService();

    $utc = $service->parseClinicLocalToUtc($clinic, '2026-03-10 10:00:00');
    expect($utc->format('H:i:s'))->toBe('14:00:00');

    $local = $service->utcToClinicString($clinic, $utc);
    expect($local)->toBe('2026-03-10 10:00:00');
});

it('rejects dst gap local times', function () {
    $clinic = Clinic::factory()->make([
        'timezone' => 'America/New_York',
    ]);

    $service = new ClinicDateTimeService();

    expect(fn () => $service->parseClinicLocalToUtc($clinic, '2026-03-08 02:30:00'))
        ->toThrow(\InvalidArgumentException::class);
});
