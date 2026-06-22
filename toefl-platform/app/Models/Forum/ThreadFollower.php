<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'user_id',
        'receives_notifications',
    ];

    protected $casts = [
        'receives_notifications' => 'boolean',
    ];

    /**
     * Get the thread being followed.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    /**
     * Get the user who is following.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active followers only.
     */
    public function scopeActive($query)
    {
        return $query->where('receives_notifications', true);
    }
}
