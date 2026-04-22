<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAdminClinicScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMergeLog extends Model
{
    use HasFactory, BelongsToAdminClinicScope;

    protected $fillable = [
        'clinic_id',
        'source_patient_id',
        'target_patient_id',
        'merged_by_user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function sourcePatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'source_patient_id');
    }

    public function targetPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'target_patient_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }
}



