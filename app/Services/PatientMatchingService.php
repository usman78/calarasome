<?php

namespace App\Services;

use App\Models\PatientMatchAlert;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PatientMatchingService
{
    public function findOrCreate(array $data, int $clinicId): Patient
    {
        $email = Str::lower(trim($data['email']));
        $name = $this->normalizeName($data['full_name']);
        $dob = $data['date_of_birth'];
        $phone = $data['phone'] ?? null;

        // Step 1: strict primary key match (email + DOB).
        $emailDobMatch = $this->findByEmailAndDob($clinicId, $email, $dob);
        if ($emailDobMatch) {
            return tap($emailDobMatch, fn (Patient $p) => $this->refreshContact($p, $data));
        }

        // Step 2: fallback by phone + DOB (email can change over time).
        $phoneDobMatch = $this->findByPhoneAndDob($clinicId, $phone, $dob);
        if ($phoneDobMatch) {
            return tap($phoneDobMatch, fn (Patient $p) => $this->refreshContact($p, $data));
        }

        // Step 3: fallback by unique normalized-name + DOB.
        $nameDobMatch = $this->findUniqueByNameAndDob($clinicId, $name, $dob);
        if ($nameDobMatch) {
            return tap($nameDobMatch, fn (Patient $p) => $this->refreshContact($p, $data));
        }

        // Step 4: legacy compatibility, same email + same normalized name + missing DOB.
        $legacyEmailNameMissingDob = $this->findLegacyEmailNameMissingDob($clinicId, $email, $name);
        if ($legacyEmailNameMissingDob) {
            return tap($legacyEmailNameMissingDob, fn (Patient $p) => $this->refreshContact($p, $data));
        }

        // Step 5: create new patient; if email exists with different DOB, flag + alert.
        return $this->createPatientWithSharedEmailCheck($clinicId, $data, $email, $dob);
    }

    private function refreshContact(Patient $patient, array $data): void
    {
        $patient->update([
            'full_name' => $data['full_name'] ?? $patient->full_name,
            'email' => Str::lower(trim($data['email'] ?? $patient->email)),
            'phone' => $data['phone'] ?? $patient->phone,
            'date_of_birth' => $data['date_of_birth'] ?? $patient->date_of_birth,
            'communication_consent' => $data['communication_consent'] ?? $patient->communication_consent,
        ]);
    }

    private function findByEmailAndDob(int $clinicId, string $email, string $dob): ?Patient
    {
        return Patient::query()
            ->where('clinic_id', $clinicId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereDate('date_of_birth', $dob)
            ->first();
    }

    private function findByPhoneAndDob(int $clinicId, ?string $phone, string $dob): ?Patient
    {
        if (! $phone) {
            return null;
        }

        return Patient::query()
            ->where('clinic_id', $clinicId)
            ->where('phone', $phone)
            ->whereDate('date_of_birth', $dob)
            ->first();
    }

    private function findUniqueByNameAndDob(int $clinicId, string $normalizedName, string $dob): ?Patient
    {
        $candidates = Patient::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('date_of_birth', $dob)
            ->get()
            ->filter(fn (Patient $candidate): bool => $this->normalizeName($candidate->full_name) === $normalizedName)
            ->values();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    private function findLegacyEmailNameMissingDob(int $clinicId, string $email, string $normalizedName): ?Patient
    {
        return Patient::query()
            ->where('clinic_id', $clinicId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('date_of_birth')
            ->get()
            ->first(function (Patient $candidate) use ($normalizedName): bool {
                return $this->normalizeName($candidate->full_name) === $normalizedName;
            });
    }

    private function createPatientWithSharedEmailCheck(int $clinicId, array $data, string $email, string $dob): Patient
    {
        $emailMatches = Patient::query()
            ->where('clinic_id', $clinicId)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();

        $patient = Patient::query()->create([
            'clinic_id' => $clinicId,
            'full_name' => $data['full_name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $dob,
            'is_shared_email_account' => $emailMatches->isNotEmpty(),
            'communication_consent' => $data['communication_consent'] ?? null,
        ]);

        if ($emailMatches->isNotEmpty()) {
            $this->recordSharedEmailMismatchAlert($clinicId, $patient, $emailMatches, $dob);
        }

        return $patient;
    }

    private function recordSharedEmailMismatchAlert(int $clinicId, Patient $patient, Collection $emailMatches, string $dob): void
    {
        PatientMatchAlert::query()->create([
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'alert_type' => 'shared_email_mismatch',
            'payload' => [
                'email' => $patient->email,
                'newPatientId' => $patient->id,
                'newPatientDob' => $dob,
                'existingPatientIds' => $emailMatches->pluck('id')->all(),
            ],
        ]);
    }

    private function normalizeName(string $name): string
    {
        $normalized = Str::lower($name);
        $normalized = preg_replace('/\b(dr|mr|mrs|ms)\b\.?/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
