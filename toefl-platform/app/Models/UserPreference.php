<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPreference extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'dark_mode',
        'font_size',
        'high_contrast',
        'animations',
        'screen_reader_opt',
        'language',
        'dnd_enabled',
        'dnd_start',
        'dnd_end',
        'dnd_days',
    ];

    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
            'high_contrast' => 'boolean',
            'animations' => 'boolean',
            'screen_reader_opt' => 'boolean',
            'dnd_enabled' => 'boolean',
            'dnd_days' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
