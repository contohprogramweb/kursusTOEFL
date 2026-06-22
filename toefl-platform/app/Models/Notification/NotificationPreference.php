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
        'in_app',
        'email',
        'push',
        'sms',
        'whatsapp',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'email' => 'boolean',
        'push' => 'boolean',
        'sms' => 'boolean',
        'whatsapp' => 'boolean',
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

        if ($this->in_app) {
            $channels[] = 'database';
        }

        if ($this->email) {
            $channels[] = 'mail';
        }

        if ($this->push) {
            $channels[] = \App\Channels\FcmChannel::class;
        }

        if ($this->sms) {
            $channels[] = \App\Channels\SmsChannel::class;
        }

        if ($this->whatsapp) {
            $channels[] = \App\Channels\WhatsAppChannel::class;
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
    public static function getOrCreate(int $userId, string $category, string $eventType = null): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'category' => $category,
            ],
            [
                'in_app' => true,
                'email' => true,
                'push' => true,
                'sms' => false,
                'whatsapp' => false,
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
