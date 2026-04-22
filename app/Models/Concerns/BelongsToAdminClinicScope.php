<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToAdminClinicScope
{
    public static function bootBelongsToAdminClinicScope(): void
    {
        static::addGlobalScope('admin_clinic_access', function (Builder $builder): void {
            $user = auth()->user();

            if (! $user instanceof User || ! $user->is_admin) {
                return;
            }

            $clinicIds = Clinic::query()
                ->withoutGlobalScopes()
                ->where(function (Builder $query) use ($user): void {
                    $query->where('owner_id', $user->id)->orWhereNull('owner_id');
                })
                ->pluck('id')
                ->all();

            $table = $builder->getModel()->getTable();

            if ($clinicIds === []) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->whereIn($table.'.clinic_id', $clinicIds);
        });
    }
}
