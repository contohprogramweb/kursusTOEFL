<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationAnswer extends Model
{
    protected $fillable = [
        'simulation_id', 'question_number', 'section', 'question_text', 
        'user_answer', 'correct_answer', 'is_correct', 'explanation', 
        'ai_feedback', 'time_spent_seconds'
    ];

    protected $casts = [
        'ai_feedback' => 'array',
        'is_correct' => 'boolean',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function getFormattedTimeSpentAttribute(): string
    {
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
