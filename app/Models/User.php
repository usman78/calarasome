<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'password',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class, 'owner_id');
    }

    public function hasClinicManagementAccess(): bool
    {
        return $this->is_admin && Clinic::query()
            ->withoutGlobalScopes()
            ->where(function ($query): void {
                $query->where('owner_id', $this->id)->orWhereNull('owner_id');
            })
            ->exists();
    }

    public function canManageClinicId(?int $clinicId): bool
    {
        if (! $this->is_admin || ! $clinicId) {
            return false;
        }

        return Clinic::query()
            ->withoutGlobalScopes()
            ->whereKey($clinicId)
            ->where(function ($query): void {
                $query->where('owner_id', $this->id)->orWhereNull('owner_id');
            })
            ->exists();
    }
}

