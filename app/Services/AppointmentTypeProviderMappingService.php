<?php

namespace App\Services;

use App\Models\Provider;

class AppointmentTypeProviderMappingService
{
    public function syncClinicProviders(int $clinicId, int $appointmentTypeId, array $providerIds): void
    {
        $selectedProviderIds = collect($providerIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        Provider::query()
            ->where('clinic_id', $clinicId)
            ->get()
            ->each(function (Provider $provider) use ($appointmentTypeId, $selectedProviderIds): void {
                $types = collect($provider->default_appointment_types ?? []);

                $types = in_array($provider->id, $selectedProviderIds, true)
                    ? $types->push($appointmentTypeId)
                    : $types->reject(fn (int $id): bool => $id === $appointmentTypeId);

                $provider->update([
                    'default_appointment_types' => $types->unique()->values()->all(),
                ]);
            });
    }
}
