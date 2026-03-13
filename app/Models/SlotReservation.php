<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'provider_id',
        'appointment_type_id',
        'slot_datetime',
        'session_token',
        'reserved_at',
        'expires_at',
        'converted_to_appointment_id',
        'released_at',
    ];

    protected $casts = [
        'slot_datetime' => 'datetime',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
    ];
}
