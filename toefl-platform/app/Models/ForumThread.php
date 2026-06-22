<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumThread extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category',
        'title',
        'content',
        'author_id',
        'is_pinned',
        'is_locked',
        'view_count',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'is_flagged' => 'boolean',
            'view_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'flagged_at' => 'datetime',
        ];
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'thread_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(ThreadFollower::class, 'thread_id');
    }

    public function attachments(): HasMany
    {
        return $this->morphMany(ForumAttachment::class, 'attachable');
    }

    /**
     * Get root replies (level 0 only)
     */
    public function rootReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'thread_id')->whereNull('parent_reply_id');
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Check if user is following this thread
     */
    public function isFollowedBy(int $userId): bool
    {
        return $this->followers()->where('user_id', $userId)->where('is_following', true)->exists();
    }

    /**
     * Available categories
     */
    public static function getCategories(): array
    {
        return [
            'umum' => 'Umum',
            'reading' => 'Reading',
            'listening' => 'Listening',
            'speaking' => 'Speaking',
            'writing' => 'Writing',
            'simulasi' => 'Simulasi',
            'institusi' => 'Institusi',
        ];
    }
}
