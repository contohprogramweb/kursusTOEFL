<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id',
        'parent_reply_id',
        'nesting_level',
        'content',
        'author_id',
        'is_hidden',
        'hide_reason',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'is_flagged' => 'boolean',
            'nesting_level' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'flagged_at' => 'datetime',
        ];
    }

    const MAX_NESTING_LEVEL = 3;

    public function scopeHidden($query)
    {
        return $query->where('is_hidden', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeRootReplies($query)
    {
        return $query->whereNull('parent_reply_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parentReply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'parent_reply_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'parent_reply_id')
            ->orderBy('created_at', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->morphMany(ForumAttachment::class, 'attachable');
    }

    /**
     * Check if this reply can have children (max 3 levels)
     */
    public function canHaveReplies(): bool
    {
        return $this->nesting_level < self::MAX_NESTING_LEVEL;
    }

    /**
     * Get all ancestors of this reply
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parentReply;
        
        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parentReply;
        }
        
        return array_reverse($ancestors);
    }

    /**
     * Auto-flag detection for spam
     */
    public static function detectSpam(int $userId, string $content): ?string
    {
        // Check for repetitive content (same user posted similar content in last hour)
        $similarPosts = self::where('author_id', $userId)
            ->where('created_at', '>', now()->subHour())
            ->where('content', 'LIKE', '%' . substr($content, 0, 50) . '%')
            ->count();

        if ($similarPosts >= 3) {
            return 'repetitive_content';
        }

        // Check for suspicious links
        if (preg_match_all('/https?:\/\/[^\s]+/', $content, $matches)) {
            $suspiciousDomains = ['bit.ly', 'tinyurl', 'goo.gl', 'shorturl'];
            foreach ($matches[0] as $link) {
                foreach ($suspiciousDomains as $domain) {
                    if (strpos($link, $domain) !== false) {
                        return 'suspicious_link';
                    }
                }
            }
        }

        return null;
    }
}
