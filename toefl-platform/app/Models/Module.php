<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'section',
        'difficulty',
        'order_index',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'order_index' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ModuleContent::class);
    }

    public function learningProgresses(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
