<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'timezone', 'min_booking_notice_hours'];

    protected $casts = [
        'min_booking_notice_hours' => 'integer',
    ];

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
