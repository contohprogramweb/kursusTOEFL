<?php

namespace App\Services;

use App\Models\SimulationResult;
use App\Models\SectionResult;
use App\Models\QuestionResponse;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGradingService
{
    /**
     * Grade Reading or Listening section automatically
     * SLA: <= 1 detik
     * 
     * @param SectionResult $sectionResult
     * @return array ['raw_score' => int, 'score' => float, 'correct_count' => int, 'total_count' => int]
     */
    public function gradeSection(SectionResult $sectionResult): array
    {
        $startTime = microtime(true);
        
        $section = $sectionResult->section;
        
        // Hanya support Reading dan Listening untuk auto-grading
        if (!in_array($section, ['reading', 'listening'])) {
            throw new \InvalidArgumentException("Auto-grading only supported for reading and listening sections");
        }
        
        // Get all question responses for this section
        $responses = $sectionResult->questionResponses()
            ->with(['question.options'])
            ->get();
        
        $totalQuestions = $responses->count();
        $correctCount = 0;
        $rawScore = 0;
        
        DB::transaction(function () use ($responses, &$correctCount, &$rawScore) {
            foreach ($responses as $response) {
                $isCorrect = $this->gradeQuestion($response);
                
                if ($isCorrect) {
                    $correctCount++;
                    $rawScore++;
                }
                
                // Update response with grading result
                $response->update([
                    'is_correct' => $isCorrect
                ]);
            }
        });
        
        // Convert raw score to scaled score (0-30)
        $scaledScore = $this->convertToScaledScore($rawScore, $totalQuestions, $section);
        
        // Update section result
        $sectionResult->update([
            'raw_score' => $rawScore,
            'score' => $scaledScore,
            'status' => 'completed'
        ]);
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000; // milliseconds
        
        Log::info('Auto-grading completed', [
            'section_result_id' => $sectionResult->id,
            'section' => $section,
            'raw_score' => $rawScore,
            'scaled_score' => $scaledScore,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'processing_time_ms' => round($processingTime, 2)
        ]);
        
        return [
            'raw_score' => $rawScore,
            'score' => $scaledScore,
            'correct_count' => $correctCount,
            'total_count' => $totalQuestions,
            'processing_time_ms' => round($processingTime, 2)
        ];
    }
    
    /**
     * Grade a single question by comparing selected_option_id with QuestionOption.is_correct
     * 
     * @param QuestionResponse $response
     * @return bool
     */
    public function gradeQuestion(QuestionResponse $response): bool
    {
        // Jika tidak ada jawaban yang dipilih, dianggap salah
        if (!$response->selected_option_id) {
            return false;
        }
        
        // Check if the selected option is correct
        $selectedOption = QuestionOption::find($response->selected_option_id);
        
        if (!$selectedOption) {
            return false;
        }
        
        return $selectedOption->is_correct === true;
    }
    
    /**
     * Convert raw score to scaled score (0-30)
     * Menggunakan linear scaling sederhana
     * 
     * @param int $rawScore
     * @param int $totalQuestions
     * @param string $section
     * @return float
     */
    public function convertToScaledScore(int $rawScore, int $totalQuestions, string $section): float
    {
        if ($totalQuestions === 0) {
            return 0.0;
        }
        
        // Persentase jawaban benar
        $percentage = $rawScore / $totalQuestions;
        
        // TOEFL iBT scoring conversion (simplified linear mapping)
        // Reading: 0-30 questions → 0-30 points
        // Listening: 0-28 questions → 0-30 points
        // Dalam implementasi nyata, bisa menggunakan lookup table ETS
        
        $scaledScore = $percentage * 30;
        
        // Round to 2 decimal places
        return round($scaledScore, 2);
    }
    
    /**
     * Grade all Reading and Listening sections for a simulation
     * 
     * @param SimulationResult $simulation
     * @return array
     */
    public function gradeSimulation(SimulationResult $simulation): array
    {
        $results = [];
        
        $sectionResults = $simulation->sectionResults()
            ->whereIn('section', ['reading', 'listening'])
            ->get();
        
        foreach ($sectionResults as $sectionResult) {
            try {
                $result = $this->gradeSection($sectionResult);
                $results[$sectionResult->section] = $result;
            } catch (\Exception $e) {
                Log::error('Failed to grade section', [
                    'section_result_id' => $sectionResult->id,
                    'section' => $sectionResult->section,
                    'error' => $e->getMessage()
                ]);
                $results[$sectionResult->section] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Calculate total score
        $totalScore = 0;
        $gradedCount = 0;
        
        foreach ($results as $section => $result) {
            if (isset($result['score'])) {
                $totalScore += $result['score'];
                $gradedCount++;
            }
        }
        
        // Update simulation total score (hanya R+L untuk saat ini)
        if ($gradedCount > 0) {
            $simulation->update([
                'total_score' => $totalScore
            ]);
        }
        
        return [
            'sections' => $results,
            'total_score' => $totalScore,
            'graded_sections_count' => $gradedCount
        ];
    }
}
