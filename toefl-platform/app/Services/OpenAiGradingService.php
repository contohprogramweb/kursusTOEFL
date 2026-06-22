<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AIServiceLog;
use Exception;
use Throwable;

/**
 * Service for OpenAI GPT-4 API integration for AI grading
 */
class OpenAiGradingService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/chat/completions';
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = config('services.openai.model', 'gpt-4');
    }

    /**
     * Grade speaking response using OpenAI GPT-4
     * 
     * @param string $transcript The transcribed text from speech-to-text
     * @param string $questionText The original question prompt
     * @param int|null $sectionResultId Optional section result ID for logging
     * @return array ['score' => float, 'dimensions' => array, 'feedback' => string, 'highlights' => array, 'confidence' => float]
     * @throws Exception
     */
    public function gradeSpeaking(string $transcript, string $questionText, ?int $sectionResultId = null): array
    {
        $startTime = microtime(true);
        
        // Check cache first
        $cacheKey = 'openai_speaking:' . md5($transcript . $questionText);
        if (Cache::has($cacheKey)) {
            AIServiceLog::logSuccess(
                $sectionResultId,
                'openai',
                'grade_speaking',
                0,
                [],
                Cache::get($cacheKey),
                $this->model,
                null,
                true
            );
            return Cache::get($cacheKey);
        }

        try {
            $prompt = $this->buildSpeakingPrompt($transcript, $questionText);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert TOEFL iBT Speaking evaluator. Analyze the response and provide scores for Delivery, Language Use, and Topic Development on a scale of 0-30.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1500,
                'response_format' => ['type' => 'json_object'],
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                $gradingResult = json_decode($content, true);
                
                if (!is_array($gradingResult)) {
                    throw new Exception('Invalid JSON response from OpenAI');
                }

                // Parse and validate the response
                $result = $this->parseSpeakingResponse($gradingResult, $transcript);

                // Cache for 7 days
                Cache::put($cacheKey, $result, 604800);

                AIServiceLog::logSuccess(
                    $sectionResultId,
                    'openai',
                    'grade_speaking',
                    $responseTimeMs,
                    ['model' => $this->model, 'transcript_length' => strlen($transcript)],
                    $data,
                    $this->model,
                    $result['confidence']
                );

                Log::info('OpenAI Speaking grading successful', [
                    'section_result_id' => $sectionResultId,
                    'score' => $result['score'],
                    'confidence' => $result['confidence'],
                    'response_time_ms' => $responseTimeMs,
                ]);

                return $result;
            }

            throw new Exception('OpenAI API error: ' . $response->body(), $response->status());
        } catch (Throwable $e) {
            $responseTimeMs = isset($responseTimeMs) ? $responseTimeMs : (int) ((microtime(true) - $startTime) * 1000);
            
            AIServiceLog::logError(
                $sectionResultId,
                'openai',
                'grade_speaking',
                $e->getMessage(),
                $responseTimeMs
            );

            Log::error('OpenAI Speaking grading failed', [
                'section_result_id' => $sectionResultId,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTimeMs,
            ]);

            throw $e;
        }
    }

    /**
     * Grade writing essay using OpenAI GPT-4
     * 
     * @param string $essayText The essay text
     * @param string $questionText The original question prompt
     * @param int|null $sectionResultId Optional section result ID for logging
     * @return array ['score' => float, 'dimensions' => array, 'feedback' => string, 'highlights' => array, 'confidence' => float]
     * @throws Exception
     */
    public function gradeWriting(string $essayText, string $questionText, ?int $sectionResultId = null): array
    {
        $startTime = microtime(true);
        
        // Check cache first
        $cacheKey = 'openai_writing:' . md5($essayText . $questionText);
        if (Cache::has($cacheKey)) {
            AIServiceLog::logSuccess(
                $sectionResultId,
                'openai',
                'grade_writing',
                0,
                [],
                Cache::get($cacheKey),
                $this->model,
                null,
                true
            );
            return Cache::get($cacheKey);
        }

        try {
            $prompt = $this->buildWritingPrompt($essayText, $questionText);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert TOEFL iBT Writing evaluator. Analyze the essay and provide scores for Grammar & Mechanics (25%), Organization (25%), Development (30%), and Vocabulary (20%) on a scale of 0-30. Also provide inline highlights with position, type, message, and suggestion.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2500,
                'response_format' => ['type' => 'json_object'],
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                $gradingResult = json_decode($content, true);
                
                if (!is_array($gradingResult)) {
                    throw new Exception('Invalid JSON response from OpenAI');
                }

                // Parse and validate the response
                $result = $this->parseWritingResponse($gradingResult, $essayText);

                // Cache for 7 days
                Cache::put($cacheKey, $result, 604800);

                AIServiceLog::logSuccess(
                    $sectionResultId,
                    'openai',
                    'grade_writing',
                    $responseTimeMs,
                    ['model' => $this->model, 'essay_length' => strlen($essayText)],
                    $data,
                    $this->model,
                    $result['confidence']
                );

                Log::info('OpenAI Writing grading successful', [
                    'section_result_id' => $sectionResultId,
                    'score' => $result['score'],
                    'confidence' => $result['confidence'],
                    'response_time_ms' => $responseTimeMs,
                ]);

                return $result;
            }

            throw new Exception('OpenAI API error: ' . $response->body(), $response->status());
        } catch (Throwable $e) {
            $responseTimeMs = isset($responseTimeMs) ? $responseTimeMs : (int) ((microtime(true) - $startTime) * 1000);
            
            AIServiceLog::logError(
                $sectionResultId,
                'openai',
                'grade_writing',
                $e->getMessage(),
                $responseTimeMs
            );

            Log::error('OpenAI Writing grading failed', [
                'section_result_id' => $sectionResultId,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTimeMs,
            ]);

            throw $e;
        }
    }

    /**
     * Build prompt for speaking evaluation
     */
    private function buildSpeakingPrompt(string $transcript, string $questionText): string
    {
        return <<<PROMPT
Question: {$questionText}

Student Response (transcribed):
{$transcript}

Please evaluate this speaking response based on TOEFL iBT Speaking rubrics:

1. **Delivery** (pronunciation, intonation, pacing, fluency)
2. **Language Use** (grammar accuracy, vocabulary range and appropriateness)
3. **Topic Development** (completeness, coherence, progression of ideas)

Return your response in the following JSON format:
{
    "dimensions": {
        "delivery": {"score": 0-30, "feedback": "detailed feedback"},
        "language_use": {"score": 0-30, "feedback": "detailed feedback"},
        "topic_development": {"score": 0-30, "feedback": "detailed feedback"}
    },
    "overall_score": 0-30,
    "overall_feedback": "comprehensive feedback summarizing strengths and areas for improvement",
    "highlights": [
        {"position": 0, "type": "strength|weakness|suggestion", "message": "description", "suggestion": "improvement if applicable"}
    ],
    "confidence": 0.0-1.0
}

Note: Position in highlights refers to character position in the transcript.
PROMPT;
    }

    /**
     * Build prompt for writing evaluation
     */
    private function buildWritingPrompt(string $essayText, string $questionText): string
    {
        return <<<PROMPT
Essay Question: {$questionText}

Student Essay:
{$essayText}

Please evaluate this essay based on TOEFL iBT Writing rubrics with the following weight distribution:
- Grammar & Mechanics: 25%
- Organization: 25%
- Development: 30%
- Vocabulary: 20%

Return your response in the following JSON format:
{
    "dimensions": {
        "grammar_mechanics": {"score": 0-30, "weight": 0.25, "feedback": "detailed feedback"},
        "organization": {"score": 0-30, "weight": 0.25, "feedback": "detailed feedback"},
        "development": {"score": 0-30, "weight": 0.30, "feedback": "detailed feedback"},
        "vocabulary": {"score": 0-30, "weight": 0.20, "feedback": "detailed feedback"}
    },
    "overall_score": 0-30,
    "overall_feedback": "comprehensive feedback summarizing strengths and areas for improvement",
    "highlights": [
        {"position": 0, "type": "grammar|vocabulary|organization|development|strength|weakness", "message": "description", "suggestion": "corrected form or improvement"}
    ],
    "confidence": 0.0-1.0
}

Note: Position in highlights refers to character position in the essay text.
PROMPT;
    }

    /**
     * Parse and validate speaking response from OpenAI
     */
    private function parseSpeakingResponse(array $response, string $transcript): array
    {
        $dimensions = $response['dimensions'] ?? [];
        $overallScore = $response['overall_score'] ?? 0;
        $overallFeedback = $response['overall_feedback'] ?? '';
        $highlights = $response['highlights'] ?? [];
        $confidence = $response['confidence'] ?? 0.5;

        // Validate scores are within range
        foreach ($dimensions as &$dimension) {
            $dimension['score'] = max(0, min(30, (float) ($dimension['score'] ?? 0)));
        }

        $overallScore = max(0, min(30, (float) $overallScore));
        $confidence = max(0, min(1, (float) $confidence));

        // Calculate overall score from dimensions if not provided
        if ($overallScore === 0 && count($dimensions) > 0) {
            $totalDimensionScore = 0;
            foreach ($dimensions as $dim) {
                $totalDimensionScore += (float) ($dim['score'] ?? 0);
            }
            $overallScore = round($totalDimensionScore / count($dimensions), 2);
        }

        // Format highlights with proper structure
        $formattedHighlights = [];
        foreach ($highlights as $highlight) {
            $formattedHighlights[] = [
                'position' => (int) ($highlight['position'] ?? 0),
                'type' => $highlight['type'] ?? 'note',
                'message' => $highlight['message'] ?? '',
                'suggestion' => $highlight['suggestion'] ?? null,
            ];
        }

        return [
            'score' => round($overallScore, 2),
            'dimensions' => $dimensions,
            'feedback' => $overallFeedback,
            'highlights' => $formattedHighlights,
            'confidence' => round($confidence, 4),
            'model_version' => $this->model,
        ];
    }

    /**
     * Parse and validate writing response from OpenAI
     */
    private function parseWritingResponse(array $response, string $essayText): array
    {
        $dimensions = $response['dimensions'] ?? [];
        $overallScore = $response['overall_score'] ?? 0;
        $overallFeedback = $response['overall_feedback'] ?? '';
        $highlights = $response['highlights'] ?? [];
        $confidence = $response['confidence'] ?? 0.5;

        // Validate scores are within range
        foreach ($dimensions as &$dimension) {
            $dimension['score'] = max(0, min(30, (float) ($dimension['score'] ?? 0)));
            if (isset($dimension['weight'])) {
                $dimension['weight'] = (float) $dimension['weight'];
            }
        }

        $overallScore = max(0, min(30, (float) $overallScore));
        $confidence = max(0, min(1, (float) $confidence));

        // Calculate weighted overall score from dimensions if not provided
        if ($overallScore === 0 && count($dimensions) > 0) {
            $weightedSum = 0;
            $weightTotal = 0;
            foreach ($dimensions as $dim) {
                $weight = (float) ($dim['weight'] ?? 0.25);
                $weightedSum += ((float) ($dim['score'] ?? 0)) * $weight;
                $weightTotal += $weight;
            }
            $overallScore = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : 0;
        }

        // Format highlights with proper structure
        $formattedHighlights = [];
        foreach ($highlights as $highlight) {
            $formattedHighlights[] = [
                'position' => (int) ($highlight['position'] ?? 0),
                'type' => $highlight['type'] ?? 'note',
                'message' => $highlight['message'] ?? '',
                'suggestion' => $highlight['suggestion'] ?? null,
            ];
        }

        return [
            'score' => round($overallScore, 2),
            'dimensions' => $dimensions,
            'feedback' => $overallFeedback,
            'highlights' => $formattedHighlights,
            'confidence' => round($confidence, 4),
            'model_version' => $this->model,
        ];
    }
}
