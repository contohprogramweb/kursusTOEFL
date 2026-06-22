<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'code',
        'name',
        'icon_path',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withPivot('earned_at')
                    ->withTimestamps();
    }

    /**
     * Scope to get badges earned by a specific user
     */
    public function scopeEarnedBy($query, $userId)
    {
        return $query->join('user_badges', 'badges.id', '=', 'user_badges.badge_id')
                    ->where('user_badges.user_id', $userId);
    }
}
