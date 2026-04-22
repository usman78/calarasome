<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'provider_id',
        'appointment_type_id',
        'patient_id',
        'slot_datetime',
        'status',
        'no_show_previous_status',
        'no_show_marked_at',
        'no_show_reversible_until',
        'no_show_reversed_at',
        'triage_data',
    ];

    protected $casts = [
        'slot_datetime' => 'datetime',
        'no_show_marked_at' => 'datetime',
        'no_show_reversible_until' => 'datetime',
        'no_show_reversed_at' => 'datetime',
        'triage_data' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(AppointmentPayment::class);
    }

    public function insuranceVerification(): HasOne
    {
        return $this->hasOne(InsuranceVerification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}



