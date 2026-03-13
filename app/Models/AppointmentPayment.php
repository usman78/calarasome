<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'appointment_type_id',
        'strategy',
        'status',
        'amount_cents',
        'currency',
        'auth_scheduled_for',
        'stripe_payment_intent_id',
        'stripe_setup_intent_id',
        'stripe_payment_method_id',
        'authorized_at',
        'captured_at',
        'failed_at',
        'grace_started_at',
        'grace_expires_at',
    ];

    protected $casts = [
        'auth_scheduled_for' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'grace_started_at' => 'datetime',
        'grace_expires_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }
}
