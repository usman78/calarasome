<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WaitlistNotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'waitlist_notification_id',
        'waitlist_entry_id',
        'token_hash',
        'expires_at',
        'status',
        'notified_at',
        'claimed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'notified_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(WaitlistNotification::class, 'waitlist_notification_id');
    }

    public function waitlistEntry(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class);
    }

    /** @return array{token:string,record:WaitlistNotificationRecipient} */
    public static function issue(
        WaitlistNotification $notification,
        WaitlistEntry $entry,
        CarbonInterface $expiresAt
    ): array {
        $token = Str::random(64);

        $record = self::query()->create([
            'waitlist_notification_id' => $notification->id,
            'waitlist_entry_id' => $entry->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'status' => 'sent',
            'notified_at' => now(),
        ]);

        return ['token' => $token, 'record' => $record];
    }
}
