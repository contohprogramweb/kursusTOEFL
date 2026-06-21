<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_code',
        'user_id',
        'package_id',
        'promo_code_id',
        'referral_id',
        'status',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'payment_method',
        'midtrans_transaction_id',
        'midtrans_order_id',
        'settlement_time',
        'expiry_time',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'settlement_time' => 'datetime',
            'expiry_time' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getFormattedTotalAmountAttribute($value)
    {
        return 'Rp ' . number_format($this->total_amount ?? 0, 0, ',', '.');
    }

    public function getFormattedFinalAmountAttribute($value)
    {
        return 'Rp ' . number_format($this->final_amount ?? 0, 0, ',', '.');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CoursePackage::class, 'package_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'order_id');
    }
}
