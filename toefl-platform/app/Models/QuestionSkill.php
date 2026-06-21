<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'question_skills';

    protected $fillable = [
        'question_id',
        'skill_id',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(MicroSkill::class, 'skill_id');
    }
}
