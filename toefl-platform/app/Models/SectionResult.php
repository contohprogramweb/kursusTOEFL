<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'result_id',
        'section',
        'score',
        'raw_score',
        'duration_seconds',
        'status',
        'ai_confidence',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'raw_score' => 'decimal:2',
            'duration_seconds' => 'integer',
            'ai_confidence' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function simulationResult(): BelongsTo
    {
        return $this->belongsTo(SimulationResult::class, 'result_id');
    }

    public function questionResponses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class, 'section_result_id');
    }

    public function aiGradingResults(): HasMany
    {
        return $this->hasMany(AIGradingResult::class, 'section_result_id');
    }
}
