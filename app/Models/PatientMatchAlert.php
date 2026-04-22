<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMatchAlert extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'alert_type',
        'payload',
        'resolved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}



