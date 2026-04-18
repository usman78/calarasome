<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'context_type',
        'context_id',
        'mailable',
        'recipient_email',
        'status',
        'failure_reason',
        'failure_class',
        'failure_message',
        'suggested_action',
        'meta',
        'sent_at',
        'failed_at',
        'resolved_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
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
