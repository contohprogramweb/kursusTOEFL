<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Simulation extends Model
{
    protected $fillable = [
        'user_id', 'mode', 'total_score', 'reading_score', 'listening_score', 
        'speaking_score', 'writing_score', 'micro_skills', 'time_analysis', 
        'common_errors', 'recommendations', 'duration_seconds', 'completed_at'
    ];

    protected $casts = [
        'micro_skills' => 'array',
        'time_analysis' => 'array',
        'common_errors' => 'array',
        'recommendations' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SimulationAnswer::class)->orderBy('section')->orderBy('question_number');
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getScorePercentageAttribute(): float
    {
        return $this->total_score > 0 ? round(($this->total_score / 120) * 100, 1) : 0;
    }
}
