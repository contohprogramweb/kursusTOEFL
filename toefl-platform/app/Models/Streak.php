<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Streak extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'freezes_used',
        'freeze_reset_date',
    ];

    protected function casts(): array
    {
        return [
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_date' => 'date',
            'freezes_used' => 'integer',
            'freeze_reset_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get streak freezes for this user
     */
    public function freezes()
    {
        return $this->hasMany(StreakFreeze::class, 'user_id', 'user_id');
    }

    /**
     * Check if freeze can be used (max 1x per week)
     */
    public function canUseFreeze(): bool
    {
        $weekAgo = now()->subWeek();
        $freezesThisWeek = StreakFreeze::where('user_id', $this->user_id)
            ->where('created_at', '>=', $weekAgo)
            ->count();

        return $freezesThisWeek < 1;
    }

    /**
     * Use a streak freeze
     */
    public function useFreeze(string $reason, ?string $notes = null): bool
    {
        if (!$this->canUseFreeze()) {
            return false;
        }

        StreakFreeze::create([
            'user_id' => $this->user_id,
            'freeze_date' => now()->toDateString(),
            'reason' => $reason,
            'notes' => $notes,
        ]);

        return true;
    }

    /**
     * Check if today is frozen
     */
    public function isTodayFrozen(): bool
    {
        return StreakFreeze::where('user_id', $this->user_id)
            ->where('freeze_date', now()->toDateString())
            ->exists();
    }

    /**
     * Check if a specific date is frozen
     */
    public function isDateFrozen(string $date): bool
    {
        return StreakFreeze::where('user_id', $this->user_id)
            ->where('freeze_date', $date)
            ->exists();
    }
}
