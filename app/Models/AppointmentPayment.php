<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        'voided_at',
        'refund_id',
        'refunded_at',
        'failed_at',
        'grace_started_at',
        'grace_expires_at',
    ];

    protected $casts = [
        'auth_scheduled_for' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'voided_at' => 'datetime',
        'refunded_at' => 'datetime',
        'failed_at' => 'datetime',
        'grace_started_at' => 'datetime',
        'grace_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('admin_clinic_access', function (Builder $builder): void {
            $user = auth()->user();

            if (! $user instanceof User || ! $user->is_admin) {
                return;
            }

            $clinicIds = $user->clinics()->pluck('id')->all();

            if ($clinicIds === []) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->whereHas('appointment', fn (Builder $appointmentQuery) => $appointmentQuery->whereIn('clinic_id', $clinicIds));
        });
    }

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


