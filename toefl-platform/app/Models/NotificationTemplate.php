<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title_template',
        'message_template',
        'channels',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'type', 'type');
    }
}
