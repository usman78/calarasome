<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppointmentAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'email',
        'token_hash',
        'expires_at',
        'failed_attempts',
        'locked_until',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return array{token:string,record:AppointmentAccessToken} */
    public static function issue(Appointment $appointment, Patient $patient): array
    {
        $token = Str::random(64);

        $record = self::query()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'email' => $patient->email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(24),
            'last_sent_at' => now(),
        ]);

        return ['token' => $token, 'record' => $record];
    }
}


