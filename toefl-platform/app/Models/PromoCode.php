<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_usage',
        'usage_count',
        'max_usage_per_user',
        'valid_from',
        'valid_until',
        'applicable_packages',
        'stackable',
        'eligibility_rules',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_usage' => 'integer',
            'usage_count' => 'integer',
            'max_usage_per_user' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'applicable_packages' => 'array',
            'stackable' => 'boolean',
            'eligibility_rules' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getFormattedDiscountValueAttribute($value)
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }
        return 'Rp ' . number_format($this->discount_value ?? 0, 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_usage')
                    ->orWhereRaw('usage_count < max_usage');
            });
    }

    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'promo_code_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
