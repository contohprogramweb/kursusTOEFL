<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'event_type',
        'channel_in_app',
        'channel_email',
        'channel_push',
        'channel_sms',
        'channel_whatsapp',
    ];

    protected $casts = [
        'channel_in_app' => 'boolean',
        'channel_email' => 'boolean',
        'channel_push' => 'boolean',
        'channel_sms' => 'boolean',
        'channel_whatsapp' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get enabled channels for this preference.
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->channel_in_app) {
            $channels[] = 'database';
        }

        if ($this->channel_email) {
            $channels[] = 'mail';
        }

        if ($this->channel_push) {
            $channels[] = 'fcm';
        }

        if ($this->channel_sms) {
            $channels[] = 'sms';
        }

        if ($this->channel_whatsapp) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    /**
     * Check if channel is enabled.
     */
    public function isChannelEnabled(string $channel): bool
    {
        return in_array($channel, $this->getEnabledChannels());
    }

    /**
     * Get or create preference for user.
     */
    public static function getOrCreate(int $userId, string $category, string $eventType): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'category' => $category,
                'event_type' => $eventType,
            ],
            [
                'channel_in_app' => true,
                'channel_email' => true,
                'channel_push' => true,
                'channel_sms' => false,
                'channel_whatsapp' => false,
            ]
        );
    }

    /**
     * Scope to get preferences by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get preferences by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
