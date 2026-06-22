<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AIServiceLog;
use Exception;
use Throwable;

/**
 * Service for AWS Transcribe API integration (fallback for Google Speech)
 */
class AwsTranscribeService
{
    private string $accessKeyId;
    private string $secretAccessKey;
    private string $region;

    public function __construct()
    {
        $this->accessKeyId = config('services.aws.access_key_id', '');
        $this->secretAccessKey = config('services.aws.secret_access_key', '');
        $this->region = config('services.aws.region', 'us-east-1');
    }

    /**
     * Transcribe audio file using AWS Transcribe
     * 
     * @param string $audioContent Base64 encoded audio content
     * @param string $languageCode Language code (e.g., 'en-US')
     * @return array ['transcript' => string, 'confidence' => float]
     * @throws Exception
     */
    public function transcribe(string $audioContent, string $languageCode = 'en-US'): array
    {
        $startTime = microtime(true);
        
        // Check cache first
        $cacheKey = 'aws_transcribe:' . md5($audioContent . $languageCode);
        if (Cache::has($cacheKey)) {
            AIServiceLog::logSuccess(
                null,
                'aws_transcribe',
                'transcribe',
                0,
                [],
                Cache::get($cacheKey),
                'cached',
                null,
                true
            );
            return Cache::get($cacheKey);
        }

        try {
            // AWS Transcribe uses a different approach - we need to upload to S3 first
            // For simplicity, we'll use the StartTranscriptionJob API
            // In production, you would upload the audio to S3 and reference it
            
            $jobName = 'transcribe_' . uniqid() . '_' . time();
            
            // Decode base64 and save temporarily
            $tempFile = tempnam(sys_get_temp_dir(), 'aws_transcribe_');
            file_put_contents($tempFile, base64_decode($audioContent));
            
            // In a real implementation, upload to S3 here
            // For now, we'll simulate the API call structure
            $mediaUri = "s3://your-bucket/{$jobName}.wav";
            
            $response = Http::withHeaders([
                'X-Amz-Target' => 'Transcribe.StartTranscriptionJob',
                'Content-Type' => 'application/x-amz-json-1.1',
            ])->post("https://transcribe.{$this->region}.amazonaws.com/", [
                'TranscriptionJobName' => $jobName,
                'LanguageCode' => $this->mapLanguageCode($languageCode),
                'MediaFormat' => 'wav',
                'Media' => [
                    'MediaFileUri' => $mediaUri,
                ],
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if ($response->successful()) {
                $data = $response->json();
                
                // AWS Transcribe is async - in production, you'd poll for completion
                // For this implementation, we'll return a placeholder
                // Real implementation would wait for job completion
                
                Log::info('AWS Transcribe job started', [
                    'job_name' => $jobName,
                    'response_time_ms' => $responseTimeMs,
                ]);

                // This is a simplified response - real implementation needs polling
                $result = [
                    'transcript' => '', // Would get from completed job
                    'confidence' => 0.0,
                    'job_name' => $jobName,
                    'status' => 'IN_PROGRESS',
                ];

                AIServiceLog::logSuccess(
                    null,
                    'aws_transcribe',
                    'transcribe',
                    $responseTimeMs,
                    ['jobName' => $jobName],
                    $data,
                    'aws-transcribe-v1'
                );

                return $result;
            }

            throw new Exception('AWS Transcribe API error: ' . $response->body(), $response->status());
        } catch (Throwable $e) {
            $responseTimeMs = isset($responseTimeMs) ? $responseTimeMs : (int) ((microtime(true) - $startTime) * 1000);
            
            AIServiceLog::logError(
                null,
                'aws_transcribe',
                'transcribe',
                $e->getMessage(),
                $responseTimeMs
            );

            Log::error('AWS Transcribe transcription failed', [
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTimeMs,
            ]);

            throw $e;
        }
    }

    /**
     * Get transcription job result
     * 
     * @param string $jobName
     * @return array
     * @throws Exception
     */
    public function getTranscriptionResult(string $jobName): array
    {
        try {
            $response = Http::withHeaders([
                'X-Amz-Target' => 'Transcribe.GetTranscriptionJob',
                'Content-Type' => 'application/x-amz-json-1.1',
            ])->post("https://transcribe.{$this->region}.amazonaws.com/", [
                'TranscriptionJobName' => $jobName,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (($data['TranscriptionJob']['TranscriptionJobStatus'] ?? '') === 'COMPLETED') {
                    $transcriptFileUri = $data['TranscriptionJob']['Transcript']['TranscriptFileUri'] ?? '';
                    
                    // Fetch the transcript JSON
                    $transcriptResponse = Http::get($transcriptFileUri);
                    $transcriptData = $transcriptResponse->json();
                    
                    $transcript = $transcriptData['results']['transcripts'][0]['transcript'] ?? '';
                    
                    return [
                        'transcript' => $transcript,
                        'confidence' => 1.0, // AWS doesn't provide confidence in the same way
                        'status' => 'COMPLETED',
                    ];
                }
                
                return [
                    'transcript' => '',
                    'confidence' => 0.0,
                    'status' => $data['TranscriptionJob']['TranscriptionJobStatus'] ?? 'UNKNOWN',
                ];
            }

            throw new Exception('AWS Transcribe API error: ' . $response->body());
        } catch (Throwable $e) {
            Log::error('AWS Transcribe get result failed', [
                'job_name' => $jobName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Map language code to AWS format
     */
    private function mapLanguageCode(string $languageCode): string
    {
        $mapping = [
            'en-US' => 'en-US',
            'en-GB' => 'en-GB',
            'es-ES' => 'es-US',
            'fr-FR' => 'fr-FR',
            'de-DE' => 'de-DE',
            'it-IT' => 'it-IT',
            'pt-BR' => 'pt-BR',
            'ja-JP' => 'ja-JP',
            'ko-KR' => 'ko-KR',
            'zh-CN' => 'zh-CN',
        ];

        return $mapping[$languageCode] ?? 'en-US';
    }

    /**
     * Transcribe audio from file path
     */
    public function transcribeFromFile(string $filePath, string $languageCode = 'en-US'): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("Audio file not found: {$filePath}");
        }

        $audioContent = base64_encode(file_get_contents($filePath));
        return $this->transcribe($audioContent, $languageCode);
    }
}
