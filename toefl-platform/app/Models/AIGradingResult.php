<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIGradingResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_result_id',
        'dimension',
        'score',
        'max_score',
        'feedback',
        'highlights',
        'confidence',
        'model_version',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'confidence' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sectionResult(): BelongsTo
    {
        return $this->belongsTo(SectionResult::class, 'section_result_id');
    }
}
