<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\SectionResult;
use App\Models\AIGradingResult;
use App\Models\AIGradingQueue;
use App\Services\OpenAiGradingService;
use Exception;
use Throwable;

/**
 * Job to process AI grading for Writing section
 * 
 * SLA: <= 3 minutes (p95)
 */
class ProcessWritingGrading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180; // 3 minutes timeout
    public int $tries = 2;
    
    private SectionResult $sectionResult;
    private string $essayText;
    private string $questionText;
    private float $confidenceThreshold = 0.70;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sectionResultId, string $essayText, string $questionText)
    {
        $this->sectionResult = SectionResult::findOrFail($sectionResultId);
        $this->essayText = $essayText;
        $this->questionText = $questionText;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiGradingService $openAiGrading): void
    {
        $startTime = microtime(true);
        
        Log::info('Starting Writing AI Grading', [
            'section_result_id' => $this->sectionResult->id,
            'essay_length' => strlen($this->essayText),
        ]);

        try {
            // Step 1: Grade the essay using OpenAI GPT-4
            $gradingResult = $openAiGrading->gradeWriting(
                $this->essayText,
                $this->questionText,
                $this->sectionResult->id
            );

            // Step 2: Save grading results to database
            $this->saveGradingResults($gradingResult);

            // Step 3: Check confidence and queue for manual review if needed
            if ($gradingResult['confidence'] < $this->confidenceThreshold) {
                $this->queueForManualReview($gradingResult, 'low_confidence');
            }

            // Step 4: Update section result status
            $this->sectionResult->update([
                'status' => 'graded',
                'ai_confidence' => $gradingResult['confidence'],
            ]);

            // Calculate total time
            $totalTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('Writing AI Grading completed successfully', [
                'section_result_id' => $this->sectionResult->id,
                'score' => $gradingResult['score'],
                'confidence' => $gradingResult['confidence'],
                'total_time_ms' => $totalTimeMs,
            ]);

            // Verify SLA
            if ($totalTimeMs > 180000) { // 3 minutes
                Log::warning('Writing grading exceeded SLA (3 min)', [
                    'section_result_id' => $this->sectionResult->id,
                    'total_time_ms' => $totalTimeMs,
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Writing AI Grading failed', [
                'section_result_id' => $this->sectionResult->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Queue for manual review on failure
            $this->queueForManualReview([], 'service_down');

            throw $e;
        }
    }

    /**
     * Save grading results to database
     */
    private function saveGradingResults(array $gradingResult): void
    {
        DB::transaction(function () use ($gradingResult) {
            // Save overall score to section result
            $this->sectionResult->update([
                'score' => $gradingResult['score'],
                'raw_score' => round($gradingResult['score']),
            ]);

            // Save dimension-level results with proper weights
            foreach ($gradingResult['dimensions'] as $dimensionName => $dimensionData) {
                AIGradingResult::create([
                    'section_result_id' => $this->sectionResult->id,
                    'dimension' => $dimensionName,
                    'score' => $dimensionData['score'],
                    'max_score' => 30,
                    'feedback' => $dimensionData['feedback'] ?? null,
                    'highlights' => json_encode($this->formatHighlights($gradingResult['highlights'] ?? [], $dimensionName)),
                    'confidence' => $gradingResult['confidence'],
                    'model_version' => $gradingResult['model_version'] ?? 'gpt-4',
                ]);
            }

            // Save overall feedback as a separate entry
            AIGradingResult::create([
                'section_result_id' => $this->sectionResult->id,
                'dimension' => 'overall',
                'score' => $gradingResult['score'],
                'max_score' => 30,
                'feedback' => $gradingResult['feedback'] ?? null,
                'highlights' => json_encode($gradingResult['highlights'] ?? []),
                'confidence' => $gradingResult['confidence'],
                'model_version' => $gradingResult['model_version'] ?? 'gpt-4',
            ]);
        });
    }

    /**
     * Format highlights by dimension for inline display
     */
    private function formatHighlights(array $highlights, string $dimension): array
    {
        $formattedHighlights = [];
        foreach ($highlights as $highlight) {
            $formattedHighlights[] = [
                'position' => (int) ($highlight['position'] ?? 0),
                'type' => $highlight['type'] ?? 'note',
                'message' => $highlight['message'] ?? '',
                'suggestion' => $highlight['suggestion'] ?? null,
                'dimension' => $dimension,
            ];
        }
        return $formattedHighlights;
    }

    /**
     * Queue item for manual review
     */
    private function queueForManualReview(array $gradingResult, string $reason): void
    {
        AIGradingQueue::create([
            'section_result_id' => $this->sectionResult->id,
            'type' => 'writing',
            'reason' => $reason,
            'essay_text' => $this->essayText,
            'ai_response' => $gradingResult,
            'ai_confidence' => $gradingResult['confidence'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Writing essay queued for manual review', [
            'section_result_id' => $this->sectionResult->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Writing AI Grading job failed permanently', [
            'section_result_id' => $this->sectionResult->id ?? null,
            'error' => $exception->getMessage(),
        ]);

        // Ensure it's queued for manual review
        if (isset($this->sectionResult)) {
            AIGradingQueue::create([
                'section_result_id' => $this->sectionResult->id,
                'type' => 'writing',
                'reason' => 'service_down',
                'essay_text' => $this->essayText,
                'ai_response' => [],
                'ai_confidence' => null,
                'status' => 'pending',
            ]);
        }
    }
}
