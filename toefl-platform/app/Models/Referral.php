<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'referrer_id',
        'referee_id',
        'referral_code',
        'tracking_url',
        'status',
        'conversion_date',
        'reward_amount',
        'expiry_date',
        'fraud_flags',
    ];

    protected function casts(): array
    {
        return [
            'conversion_date' => 'datetime',
            'reward_amount' => 'decimal:2',
            'expiry_date' => 'datetime',
            'fraud_flags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getFormattedRewardAmountAttribute($value)
    {
        return 'Rp ' . number_format($this->reward_amount ?? 0, 0, ',', '.');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'referral_id');
    }
}
