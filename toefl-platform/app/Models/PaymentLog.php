<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'event_type',
        'midtrans_payload',
        'status_code',
        'status_message',
        'signature_valid',
    ];

    protected function casts(): array
    {
        return [
            'midtrans_payload' => 'array',
            'signature_valid' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeErrors($query)
    {
        return $query->where('event_type', 'error');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
