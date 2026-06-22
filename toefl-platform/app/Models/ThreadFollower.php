<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'user_id',
        'is_following',
    ];

    protected function casts(): array
    {
        return [
            'is_following' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if user is following a thread
     */
    public static function isFollowing(int $threadId, int $userId): bool
    {
        return self::where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->where('is_following', true)
            ->exists();
    }

    /**
     * Subscribe user to thread
     */
    public static function subscribe(int $threadId, int $userId): void
    {
        self::updateOrCreate(
            ['thread_id' => $threadId, 'user_id' => $userId],
            ['is_following' => true]
        );
    }

    /**
     * Unsubscribe user from thread
     */
    public static function unsubscribe(int $threadId, int $userId): void
    {
        self::where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->update(['is_following' => false]);
    }
}
