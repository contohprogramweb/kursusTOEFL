<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class NotificationRateLimit extends Model
{
    use HasFactory;

    const LIMITS = [
        'push_daily' => 10,           // Max 10 push notifications per day
        'payment_daily' => 5,         // Max 5 payment notifications per day
        'email_daily' => 50,          // Max 50 emails per day
        'sms_daily' => 10,            // Max 10 SMS per day
        'whatsapp_daily' => 10,       // Max 10 WhatsApp messages per day
    ];

    protected $fillable = [
        'user_id',
        'limit_type',
        'count',
        'reset_date',
    ];

    protected $casts = [
        'count' => 'integer',
        'reset_date' => 'date',
    ];

    /**
     * Get the user for the rate limit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if rate limit allows sending.
     */
    public static function canSend(int $userId, string $limitType): bool
    {
        $today = Carbon::today();
        $maxLimit = self::LIMITS[$limitType] ?? 100;

        $rateLimit = self::where('user_id', $userId)
            ->where('limit_type', $limitType)
            ->where('reset_date', $today)
            ->first();

        if (!$rateLimit) {
            return true;
        }

        return $rateLimit->count < $maxLimit;
    }

    /**
     * Increment the rate limit counter.
     */
    public static function increment(int $userId, string $limitType): void
    {
        $today = Carbon::today();
        $maxLimit = self::LIMITS[$limitType] ?? 100;

        $rateLimit = self::where('user_id', $userId)
            ->where('limit_type', $limitType)
            ->where('reset_date', $today)
            ->first();

        if ($rateLimit) {
            if ($rateLimit->count < $maxLimit) {
                $rateLimit->increment('count');
            }
        } else {
            self::create([
                'user_id' => $userId,
                'limit_type' => $limitType,
                'count' => 1,
                'reset_date' => $today,
            ]);
        }
    }

    /**
     * Get current count for a limit type.
     */
    public static function getCount(int $userId, string $limitType): int
    {
        $today = Carbon::today();

        $rateLimit = self::where('user_id', $userId)
            ->where('limit_type', $limitType)
            ->where('reset_date', $today)
            ->first();

        return $rateLimit ? $rateLimit->count : 0;
    }

    /**
     * Get remaining count for a limit type.
     */
    public static function getRemaining(int $userId, string $limitType): int
    {
        $maxLimit = self::LIMITS[$limitType] ?? 100;
        $currentCount = self::getCount($userId, $limitType);

        return max(0, $maxLimit - $currentCount);
    }

    /**
     * Reset rate limits for a user.
     */
    public static function resetForUser(int $userId, ?string $limitType = null): void
    {
        $query = self::where('user_id', $userId);

        if ($limitType) {
            $query->where('limit_type', $limitType);
        }

        $query->delete();
    }

    /**
     * Clean up old rate limit records (older than 7 days).
     */
    public static function cleanupOldRecords(): void
    {
        self::where('reset_date', '<', Carbon::today()->subDays(7))->delete();
    }
}
