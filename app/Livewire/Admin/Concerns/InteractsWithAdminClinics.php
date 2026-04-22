<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithAdminClinics
{
    private function adminClinicUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function accessibleClinicsQuery(): Builder
    {
        $user = $this->adminClinicUser();

        abort_unless($user?->is_admin, 403, 'Admin access required.');

        return Clinic::query()->where('owner_id', $user->id);
    }

    /** @return array<int, array{id:int,name:string,timezone?:string}> */
    private function accessibleClinics(bool $includeTimezone = false): array
    {
        $columns = ['id', 'name'];
        if ($includeTimezone) {
            $columns[] = 'timezone';
        }

        return $this->accessibleClinicsQuery()
            ->orderBy('name')
            ->get($columns)
            ->map(function (Clinic $clinic) use ($includeTimezone): array {
                $payload = [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                ];

                if ($includeTimezone) {
                    $payload['timezone'] = $clinic->timezone ?? 'UTC';
                }

                return $payload;
            })
            ->all();
    }

    /** @return list<int> */
    private function accessibleClinicIds(): array
    {
        return $this->accessibleClinicsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function ensureClinicAccess(?int $clinicId): void
    {
        abort_unless($this->adminClinicUser()?->canManageClinicId($clinicId), 403, 'Clinic access denied.');
    }

    private function normalizeClinicSelection(?int $requestedClinicId, bool $allowAll = false): string|int|null
    {
        $clinicIds = $this->accessibleClinicIds();

        if ($clinicIds === []) {
            return $allowAll ? 'all' : null;
        }

        if ($requestedClinicId && in_array($requestedClinicId, $clinicIds, true)) {
            return $requestedClinicId;
        }

        return $allowAll ? 'all' : $clinicIds[0];
    }

    private function applyAccessibleClinicScope(Builder $query, string $column = 'clinic_id'): Builder
    {
        return $query->whereIn($column, $this->accessibleClinicIds());
    }
}
