<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'is_shared_email_account',
        'communication_consent',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_shared_email_account' => 'boolean',
        'communication_consent' => 'array',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function matchAlerts(): HasMany
    {
        return $this->hasMany(PatientMatchAlert::class);
    }
}
