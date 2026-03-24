<?php

namespace App\Services;

use App\Models\AppointmentType;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\WaitlistEntry;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class WaitlistEntryService
{
    public function __construct(
        private readonly PatientMatchingService $patientMatchingService,
        private readonly ClinicDateTimeService $clinicDateTimeService,
        private readonly WaitlistPriorityService $priorityService,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function createEntry(Clinic $clinic, array $payload): WaitlistEntry
    {
        $appointmentType = AppointmentType::query()
            ->where('clinic_id', $clinic->id)
            ->findOrFail((int) $payload['appointment_type_id']);

        $providerId = $payload['provider_id'] ?? null;
        if ($providerId) {
            $provider = Provider::query()->where('clinic_id', $clinic->id)->find($providerId);
            if (! $provider) {
                throw new InvalidArgumentException('Provider must belong to clinic.');
            }
        }

        $patient = $this->patientMatchingService->findOrCreate([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'date_of_birth' => $payload['date_of_birth'],
            'communication_consent' => [
                'emailConsent' => true,
                'emailPHI' => (bool) ($payload['email_phi'] ?? false),
                'consentedAt' => now()->toIso8601String(),
                'consentIP' => request()->ip(),
            ],
        ], $clinic->id);

        $preferredDate = $payload['preferred_date'] ?? null;
        $preferredTime = $payload['preferred_time'] ?? null;
        $preferredDatetime = null;

        if ($preferredDate) {
            $time = $preferredTime ?: '00:00';
            $local = $preferredDate.' '.$time.':00';
            $preferredDatetime = $this->clinicDateTimeService->parseClinicLocalToUtc($clinic, $local);
        }

        $entry = WaitlistEntry::query()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $appointmentType->id,
            'provider_id' => $providerId,
            'preferred_datetime' => $preferredDatetime,
            'triage_data' => $payload['triage_data'] ?? [],
            'priority_score' => 0,
            'tier' => 'standard',
            'status' => 'active',
        ]);

        return $this->priorityService->refreshEntry($entry);
    }
}
