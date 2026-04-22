<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'full_name',
        'title',
        'specialization',
        'email',
        'phone',
        'default_appointment_types',
        'booking_buffer_minutes',
        'is_active',
        'is_accepting_new_patients',
        'display_order',
        'last_auto_assigned_at',
    ];

    protected $casts = [
        'default_appointment_types' => 'array',
        'is_active' => 'boolean',
        'is_accepting_new_patients' => 'boolean',
        'last_auto_assigned_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ProviderSchedule::class);
    }

    public function blockedTimes(): HasMany
    {
        return $this->hasMany(ProviderBlockedTime::class);
    }
}



