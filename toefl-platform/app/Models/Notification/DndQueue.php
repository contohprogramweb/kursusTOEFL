<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DndQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notifiable_id',
        'notifiable_type',
        'notification_type',
        'data',
        'category',
        'is_urgent',
        'scheduled_send_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_urgent' => 'boolean',
        'scheduled_send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the user for the queued notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get non-urgent notifications.
     */
    public function scopeNonUrgent($query)
    {
        return $query->where('is_urgent', false);
    }

    /**
     * Scope to get pending notifications.
     */
    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * Scope to get notifications ready to send.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('scheduled_send_at', '<=', now())
            ->whereNull('sent_at');
    }

    /**
     * Queue a notification during DND.
     */
    public static function queue(
        int $userId,
        string $notificationType,
        array $data,
        string $category,
        bool $isUrgent = false,
        ?string $scheduledSendAt = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'notification_type' => $notificationType,
            'data' => $data,
            'category' => $category,
            'is_urgent' => $isUrgent,
            'scheduled_send_at' => $scheduledSendAt ?? now()->addHours(8), // Default: send after 8 hours
        ]);
    }
}
