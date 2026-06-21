<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'badge_type',
        'badge_name',
        'badge_description',
        'badge_icon',
        'awarded_at',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
