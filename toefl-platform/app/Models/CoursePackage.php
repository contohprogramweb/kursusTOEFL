<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'course_type',
        'duration_weeks',
        'price',
        'compare_price',
        'features',
        'image_url',
        'max_students',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'course_type' => 'string',
            'duration_weeks' => 'integer',
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'features' => 'array',
            'max_students' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getFormattedPriceAttribute($value)
    {
        return 'Rp ' . number_format($this->price ?? 0, 0, ',', '.');
    }

    public function getFormattedComparePriceAttribute($value)
    {
        if (!$this->compare_price) {
            return null;
        }
        return 'Rp ' . number_format($this->compare_price, 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('course_type', $type);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'package_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
