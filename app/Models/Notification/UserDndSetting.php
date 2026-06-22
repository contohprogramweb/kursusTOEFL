<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserDndSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_enabled',
        'start_time',
        'end_time',
        'allowed_categories',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_categories' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the user that owns the DND setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if DND is currently active for this user.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        $now = Carbon::now();
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        // Handle overnight DND (e.g., 22:00 to 07:00)
        if ($startTime > $endTime) {
            // Overnight: active if now >= start OR now <= end
            return $now->format('H:i') >= $startTime->format('H:i') 
                || $now->format('H:i') <= $endTime->format('H:i');
        } else {
            // Same day: active if now is between start and end
            return $now->format('H:i') >= $startTime->format('H:i') 
                && $now->format('H:i') <= $endTime->format('H:i');
        }
    }

    /**
     * Check if category is allowed during DND (urgent).
     */
    public function isCategoryAllowed(string $category): bool
    {
        if (empty($this->allowed_categories)) {
            return false;
        }

        return in_array($category, $this->allowed_categories);
    }

    /**
     * Get or create DND setting for user.
     */
    public static function getOrCreate(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'is_enabled' => false,
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'allowed_categories' => ['payment'], // Payment notifications are urgent
            ]
        );
    }

    /**
     * Enable DND for user.
     */
    public static function enable(int $userId, array $settings = []): self
    {
        $dnd = self::getOrCreate($userId);
        $dnd->update(array_merge($settings, ['is_enabled' => true]));
        return $dnd;
    }

    /**
     * Disable DND for user.
     */
    public static function disable(int $userId): self
    {
        $dnd = self::getOrCreate($userId);
        $dnd->update(['is_enabled' => false]);
        return $dnd;
    }

    /**
     * Process queued notifications after DND ends.
     */
    public function processQueuedNotifications(): void
    {
        // This will be handled by a scheduled command
        DndQueue::where('user_id', $this->user_id)
            ->where('scheduled_send_at', '<=', now())
            ->whereNull('sent_at')
            ->each(function ($queue) {
                // Dispatch notification job
                // NotificationJob::dispatch($queue);
                $queue->update(['sent_at' => now()]);
            });
    }
}
