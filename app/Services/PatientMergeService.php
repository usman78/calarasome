<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentAccessToken;
use App\Models\AppointmentPayment;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\PatientMatchAlert;
use App\Models\PatientMergeLog;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PatientMergeService
{
    public function mergePatients(Patient $source, Patient $target, int $mergedByUserId): Patient
    {
        if ($source->id === $target->id) {
            throw new RuntimeException('Cannot merge a patient into itself.');
        }

        if ($source->clinic_id !== $target->clinic_id) {
            throw new RuntimeException('Patients must belong to the same clinic.');
        }

        return DB::transaction(function () use ($source, $target, $mergedByUserId): Patient {
            $payload = [
                'source' => array_merge(
                    ['id' => $source->id],
                    $source->only(['full_name', 'email', 'phone', 'date_of_birth', 'is_shared_email_account'])
                ),
                'target' => array_merge(
                    ['id' => $target->id],
                    $target->only(['full_name', 'email', 'phone', 'date_of_birth', 'is_shared_email_account'])
                ),
            ];

            Appointment::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);
            AppointmentPayment::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);
            AppointmentAccessToken::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);
            InsuranceVerification::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);
            WaitlistEntry::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);
            PatientMatchAlert::query()->where('patient_id', $source->id)->update(['patient_id' => $target->id]);

            $target->update([
                'phone' => $target->phone ?: $source->phone,
                'is_shared_email_account' => $target->is_shared_email_account || $source->is_shared_email_account,
            ]);

            PatientMergeLog::query()->create([
                'clinic_id' => $target->clinic_id,
                'source_patient_id' => $source->id,
                'target_patient_id' => $target->id,
                'merged_by_user_id' => $mergedByUserId,
                'payload' => $payload,
            ]);

            $source->delete();

            return $target->fresh();
        });
    }
}
