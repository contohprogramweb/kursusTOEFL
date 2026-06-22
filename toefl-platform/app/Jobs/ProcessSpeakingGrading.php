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
use App\Services\GoogleSpeechService;
use App\Services\AwsTranscribeService;
use App\Services\OpenAiGradingService;
use Exception;
use Throwable;

/**
 * Job to process AI grading for Speaking section
 * 
 * SLA: <= 5 minutes (p95)
 */
class ProcessSpeakingGrading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes timeout
    public int $tries = 2;
    
    private SectionResult $sectionResult;
    private string $audioPath;
    private string $questionText;
    private float $confidenceThreshold = 0.70;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sectionResultId, string $audioPath, string $questionText)
    {
        $this->sectionResult = SectionResult::findOrFail($sectionResultId);
        $this->audioPath = $audioPath;
        $this->questionText = $questionText;
    }

    /**
     * Execute the job.
     */
    public function handle(
        GoogleSpeechService $googleSpeech,
        AwsTranscribeService $awsTranscribe,
        OpenAiGradingService $openAiGrading
    ): void {
        $startTime = microtime(true);
        
        Log::info('Starting Speaking AI Grading', [
            'section_result_id' => $this->sectionResult->id,
            'audio_path' => $this->audioPath,
        ]);

        try {
            // Step 1: Transcribe audio using Google Speech-to-Text
            $transcript = $this->transcribeAudio($googleSpeech, $awsTranscribe);
            
            if (empty($transcript)) {
                throw new Exception('Empty transcript from speech-to-text service');
            }

            // Step 2: Grade the transcript using OpenAI GPT-4
            $gradingResult = $openAiGrading->gradeSpeaking(
                $transcript,
                $this->questionText,
                $this->sectionResult->id
            );

            // Step 3: Save grading results to database
            $this->saveGradingResults($gradingResult, $transcript);

            // Step 4: Check confidence and queue for manual review if needed
            if ($gradingResult['confidence'] < $this->confidenceThreshold) {
                $this->queueForManualReview($transcript, $gradingResult, 'low_confidence');
            }

            // Step 5: Update section result status
            $this->sectionResult->update([
                'status' => 'graded',
                'ai_confidence' => $gradingResult['confidence'],
            ]);

            // Calculate total time
            $totalTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('Speaking AI Grading completed successfully', [
                'section_result_id' => $this->sectionResult->id,
                'score' => $gradingResult['score'],
                'confidence' => $gradingResult['confidence'],
                'total_time_ms' => $totalTimeMs,
            ]);

            // Verify SLA
            if ($totalTimeMs > 300000) { // 5 minutes
                Log::warning('Speaking grading exceeded SLA (5 min)', [
                    'section_result_id' => $this->sectionResult->id,
                    'total_time_ms' => $totalTimeMs,
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Speaking AI Grading failed', [
                'section_result_id' => $this->sectionResult->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Queue for manual review on failure
            $this->queueForManualReview(null, [], 'service_down');

            throw $e;
        }
    }

    /**
     * Transcribe audio with fallback from Google to AWS
     */
    private function transcribeAudio(GoogleSpeechService $googleSpeech, AwsTranscribeService $awsTranscribe): string
    {
        try {
            // Try Google Speech-to-Text first
            $result = $googleSpeech->transcribeFromFile($this->audioPath, 'en-US');
            return $result['transcript'];
        } catch (Exception $e) {
            Log::warning('Google Speech-to-Text failed, falling back to AWS Transcribe', [
                'section_result_id' => $this->sectionResult->id,
                'error' => $e->getMessage(),
            ]);

            AIServiceLog::logFallback(
                $this->sectionResult->id,
                'aws_transcribe',
                'transcribe',
                'google_speech',
                $e->getMessage()
            );

            try {
                // Fallback to AWS Transcribe
                $result = $awsTranscribe->transcribeFromFile($this->audioPath, 'en-US');
                
                // AWS is async, so we need to poll for results
                // For simplicity, we'll assume immediate availability in this example
                // In production, implement proper polling with retries
                
                return $result['transcript'] ?? '';
            } catch (Exception $awsException) {
                Log::error('AWS Transcribe also failed', [
                    'section_result_id' => $this->sectionResult->id,
                    'error' => $awsException->getMessage(),
                ]);

                throw new Exception('Both speech-to-text services failed: ' . $awsException->getMessage());
            }
        }
    }

    /**
     * Save grading results to database
     */
    private function saveGradingResults(array $gradingResult, string $transcript): void
    {
        DB::transaction(function () use ($gradingResult, $transcript) {
            // Save overall score to section result
            $this->sectionResult->update([
                'score' => $gradingResult['score'],
                'raw_score' => round($gradingResult['score']),
            ]);

            // Save dimension-level results
            foreach ($gradingResult['dimensions'] as $dimensionName => $dimensionData) {
                AIGradingResult::create([
                    'section_result_id' => $this->sectionResult->id,
                    'dimension' => $dimensionName,
                    'score' => $dimensionData['score'],
                    'max_score' => 30,
                    'feedback' => $dimensionData['feedback'] ?? null,
                    'highlights' => json_encode($gradingResult['highlights'] ?? []),
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
     * Queue item for manual review
     */
    private function queueForManualReview(?string $transcript, array $gradingResult, string $reason): void
    {
        AIGradingQueue::create([
            'section_result_id' => $this->sectionResult->id,
            'type' => 'speaking',
            'reason' => $reason,
            'transcript' => $transcript,
            'ai_response' => $gradingResult,
            'ai_confidence' => $gradingResult['confidence'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Speaking response queued for manual review', [
            'section_result_id' => $this->sectionResult->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Speaking AI Grading job failed permanently', [
            'section_result_id' => $this->sectionResult->id ?? null,
            'error' => $exception->getMessage(),
        ]);

        // Ensure it's queued for manual review
        if (isset($this->sectionResult)) {
            AIGradingQueue::create([
                'section_result_id' => $this->sectionResult->id,
                'type' => 'speaking',
                'reason' => 'service_down',
                'transcript' => null,
                'ai_response' => [],
                'ai_confidence' => null,
                'status' => 'pending',
            ]);
        }
    }
}
