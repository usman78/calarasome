<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceVerification extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'appointment_id',
        'patient_id',
        'status',
        'urgency',
        'insurance_data',
        'alerted_at',
        'verified_at',
        'failed_at',
    ];

    protected $casts = [
        'insurance_data' => 'array',
        'alerted_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}



