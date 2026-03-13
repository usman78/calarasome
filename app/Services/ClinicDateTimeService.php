<?php

namespace App\Services;

use App\Models\Clinic;
use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

class ClinicDateTimeService
{
    public function assertValidTimezone(string $timezone): void
    {
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('Invalid clinic timezone.');
        }
    }

    public function parseClinicLocalToUtc(Clinic $clinic, string $localDateTime): CarbonImmutable
    {
        $this->assertValidTimezone($clinic->timezone);

        $format = 'Y-m-d H:i:s';
        $local = CarbonImmutable::createFromFormat($format, $localDateTime, $clinic->timezone);

        if (! $local || $local->setTimezone($clinic->timezone)->format($format) !== $localDateTime) {
            throw new InvalidArgumentException('Invalid local datetime for clinic timezone.');
        }

        return $local->utc();
    }

    public function utcToClinicString(Clinic $clinic, CarbonImmutable $utcDateTime): string
    {
        return $utcDateTime->setTimezone($clinic->timezone)->format('Y-m-d H:i:s');
    }
}
