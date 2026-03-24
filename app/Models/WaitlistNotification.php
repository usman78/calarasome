<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaitlistNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'appointment_type_id',
        'provider_id',
        'source_appointment_id',
        'slot_datetime',
        'status',
        'current_round',
        'next_round_at',
        'last_notified_at',
        'claimed_by_waitlist_entry_id',
        'claimed_appointment_id',
        'claimed_at',
    ];

    protected $casts = [
        'slot_datetime' => 'datetime',
        'next_round_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function sourceAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'source_appointment_id');
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class, 'claimed_by_waitlist_entry_id');
    }

    public function claimedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'claimed_appointment_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WaitlistNotificationRecipient::class);
    }
}
