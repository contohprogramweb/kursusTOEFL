<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class ForumThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'author_id',
        'title',
        'content',
        'is_pinned',
        'is_locked',
        'is_hidden',
        'hidden_reason',
        'hidden_by_id',
        'hidden_at',
        'view_count',
        'reply_count',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_hidden' => 'boolean',
        'hidden_at' => 'datetime',
        'flagged_at' => 'datetime',
        'view_count' => 'integer',
        'reply_count' => 'integer',
        'is_flagged' => 'boolean',
    ];

    /**
     * Get the category that owns the thread.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class);
    }

    /**
     * Get the author of the thread.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the user who hidden the thread.
     */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_id');
    }

    /**
     * Get all replies for the thread.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get all replies including nested ones.
     */
    public function allReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get followers of this thread.
     */
    public function followers(): HasMany
    {
        return $this->hasMany(ThreadFollower::class);
    }

    /**
     * Get attachments for the thread.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ForumAttachment::class, 'attachable');
    }

    /**
     * Check if user is following this thread.
     */
    public function isFollowedBy(int $userId): bool
    {
        return $this->followers()->where('user_id', $userId)->exists();
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Scope to get visible threads only.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope to get pinned threads first.
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $slug)
    {
        return $query->whereHas('category', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });
    }

    /**
     * Detect spam: check for repetitive content.
     */
    public static function detectSpam(int $userId, string $content, int $timeWindowMinutes = 60): bool
    {
        // Check for duplicate content from same user in time window
        $duplicateCount = self::where('author_id', $userId)
            ->where('content', $content)
            ->where('created_at', '>=', now()->subMinutes($timeWindowMinutes))
            ->count();

        if ($duplicateCount > 2) {
            return true;
        }

        // Check for suspicious links
        if (self::containsSuspiciousLinks($content)) {
            return true;
        }

        return false;
    }

    /**
     * Check if content contains suspicious links.
     */
    public static function containsSuspiciousLinks(string $content): bool
    {
        $suspiciousPatterns = [
            '/bit\.ly/i',
            '/tinyurl\.com/i',
            '/shorturl\.at/i',
            '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', // IP addresses
            '/[a-z0-9]{8,}\.[a-z]{2,4}\/[a-z0-9]{5,}/i', // Random looking domains
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auto-flag thread if spam detected.
     */
    public function autoFlagIfSpam(): bool
    {
        if (self::containsSuspiciousLinks($this->content)) {
            $this->update([
                'is_flagged' => true,
                'flag_reason' => 'Suspected spam: suspicious links detected',
                'flagged_at' => now(),
            ]);
            return true;
        }

        return false;
    }
}
