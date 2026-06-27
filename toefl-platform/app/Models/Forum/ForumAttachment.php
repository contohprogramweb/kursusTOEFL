<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumAttachment extends Model
{
    use HasFactory;

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const RESIZE_WIDTH = 800;

    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'storage_path',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get the parent attachable model.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the URL of the attachment.
     */
    public function getUrlAttribute(): string
    {
        return storage_url($this->storage_path);
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
