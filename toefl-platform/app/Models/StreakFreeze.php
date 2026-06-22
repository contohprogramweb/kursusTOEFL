<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StreakFreeze extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'freeze_date',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'freeze_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const REASONS = [
        'sick' => 'Sakit',
        'urgent' => 'Urgent',
        'holiday' => 'Libur',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
