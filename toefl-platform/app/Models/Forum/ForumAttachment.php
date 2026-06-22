<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumAttachment extends Model
{
    use HasFactory;

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const RESIZE_WIDTH = 800;

    protected $fillable = [
        'thread_id',
        'reply_id',
        'user_id',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'path',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get the thread that owns the attachment.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    /**
     * Get the reply that owns the attachment.
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class);
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the URL of the attachment.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Validate file size.
     */
    public static function validateFileSize(int $size): bool
    {
        return $size <= self::MAX_FILE_SIZE;
    }

    /**
     * Get allowed mime types for images.
     */
    public static function getAllowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    /**
     * Check if mime type is allowed.
     */
    public static function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, self::getAllowedMimeTypes());
    }
}
