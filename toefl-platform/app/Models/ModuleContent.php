<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module_id',
        'content_type',
        'title',
        'content_data',
        'order_index',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'content_data' => 'array',
            'order_index' => 'integer',
            'duration_minutes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
