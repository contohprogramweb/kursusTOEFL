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
        'content',
        'author_id',
        'is_hidden',
        'hide_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeHidden($query)
    {
        return $query->where('is_hidden', true);
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
        return $this->hasMany(ForumReply::class, 'parent_reply_id');
    }
}
