<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
        'duration_minutes',
        'is_active',
        'is_medical',
        'deposit_amount_cents',
        'deposit_currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_medical' => 'boolean',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
