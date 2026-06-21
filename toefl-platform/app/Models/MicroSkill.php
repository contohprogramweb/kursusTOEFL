<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MicroSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'section',
        'description',
    ];

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_skills', 'skill_id', 'question_id')
            ->withPivot('weight')
            ->withTimestamps();
    }
}
