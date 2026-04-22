<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'timezone', 'min_booking_notice_hours', 'owner_id'];

    protected $casts = [
        'min_booking_notice_hours' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('admin_owner_access', function (Builder $builder): void {
            $user = auth()->user();

            if (! $user instanceof User || ! $user->is_admin) {
                return;
            }

            $builder->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)->orWhereNull('owner_id');
            });
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
