<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSchedule extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'provider_id',
        'day_of_week',
        'start_time',
        'end_time',
        'appointment_type_ids',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'appointment_type_ids' => 'array',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}



