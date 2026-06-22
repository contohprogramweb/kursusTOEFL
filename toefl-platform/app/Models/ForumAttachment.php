<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'original_width',
        'original_height',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const RESIZE_WIDTH = 800;

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get file URL
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Validate file upload
     */
    public static function validateFile($file): ?string
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return 'File size exceeds maximum limit of 5MB';
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return 'Only image files are allowed (JPEG, PNG, GIF, WebP)';
        }

        return null;
    }
}
