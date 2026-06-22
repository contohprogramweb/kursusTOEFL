<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumReply extends Model
{
    use HasFactory;

    const MAX_NESTING_LEVEL = 3;

    protected $fillable = [
        'thread_id',
        'user_id',
        'parent_id',
        'nesting_level',
        'content',
        'is_hidden',
        'hidden_reason',
        'hidden_by',
        'hidden_at',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'hidden_at' => 'datetime',
        'is_flagged' => 'boolean',
        'flagged_at' => 'datetime',
        'nesting_level' => 'integer',
    ];

    /**
     * Get the thread that owns the reply.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    /**
     * Get the author of the reply.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'parent_id');
    }

    /**
     * Get child replies (nested).
     */
    public function children(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the user who hidden the reply.
     */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /**
     * Get attachments for the reply.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ForumAttachment::class, 'attachable');
    }

    /**
     * Check if can have nested replies.
     */
    public function canHaveReplies(): bool
    {
        return $this->nesting_level < self::MAX_NESTING_LEVEL;
    }

    /**
     * Scope to get visible replies only.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Detect spam: check for repetitive content.
     */
    public static function detectSpam(int $userId, string $content, int $timeWindowMinutes = 60): bool
    {
        // Check for duplicate content from same user in time window
        $duplicateCount = self::where('user_id', $userId)
            ->where('content', $content)
            ->where('created_at', '>=', now()->subMinutes($timeWindowMinutes))
            ->count();

        if ($duplicateCount > 2) {
            return true;
        }

        // Check for suspicious links
        if (ForumThread::containsSuspiciousLinks($content)) {
            return true;
        }

        return false;
    }

    /**
     * Auto-flag reply if spam detected.
     */
    public function autoFlagIfSpam(): bool
    {
        if (self::detectSpam($this->user_id, $this->content)) {
            $this->update([
                'is_flagged' => true,
                'flag_reason' => 'Suspected spam: repetitive content or suspicious links',
                'flagged_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get all ancestors of this reply.
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent;
        }

        return array_reverse($ancestors);
    }

    /**
     * Recursively load nested replies up to max level.
     */
    public static function loadNestedReplies($threadId, $parentId = null, $currentLevel = 0)
    {
        if ($currentLevel >= self::MAX_NESTING_LEVEL) {
            return [];
        }

        $replies = self::where('thread_id', $threadId)
            ->where('parent_id', $parentId)
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc')
            ->with(['author', 'children'])
            ->get();

        foreach ($replies as $reply) {
            $reply->children = self::loadNestedReplies($threadId, $reply->id, $currentLevel + 1);
        }

        return $replies;
    }
}
