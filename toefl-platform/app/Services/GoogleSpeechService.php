<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\AIServiceLog;
use Exception;
use Throwable;

/**
 * Service for Google Cloud Speech-to-Text API integration
 */
class GoogleSpeechService
{
    private string $apiKey;
    private string $baseUrl = 'https://speech.googleapis.com/v1/speech:recognize';

    public function __construct()
    {
        $this->apiKey = config('services.google_cloud.api_key', '');
    }

    /**
     * Transcribe audio file using Google Cloud Speech-to-Text
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
        $cacheKey = 'google_speech:' . md5($audioContent . $languageCode);
        if (Cache::has($cacheKey)) {
            AIServiceLog::logSuccess(
                null,
                'google_speech',
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
            $response = Http::timeout(60)->post($this->baseUrl . '?key=' . $this->apiKey, [
                'config' => [
                    'encoding' => 'LINEAR16',
                    'sampleRateHertz' => 16000,
                    'languageCode' => $languageCode,
                    'enableAutomaticPunctuation' => true,
                    'enableWordTimeOffsets' => false,
                ],
                'audio' => [
                    'content' => $audioContent,
                ],
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                
                $transcript = '';
                $avgConfidence = 0.0;
                
                if (isset($data['results']) && count($data['results']) > 0) {
                    $alternatives = $data['results'][0]['alternatives'] ?? [];
                    
                    if (count($alternatives) > 0) {
                        $transcript = $alternatives[0]['transcript'] ?? '';
                        $avgConfidence = $alternatives[0]['confidence'] ?? 0.0;
                    }
                }

                $result = [
                    'transcript' => $transcript,
                    'confidence' => $avgConfidence,
                ];

                // Cache for 24 hours
                Cache::put($cacheKey, $result, 86400);

                AIServiceLog::logSuccess(
                    null,
                    'google_speech',
                    'transcribe',
                    $responseTimeMs,
                    ['languageCode' => $languageCode],
                    $data,
                    'google-speech-v1',
                    $avgConfidence
                );

                Log::info('Google Speech-to-Text transcription successful', [
                    'transcript_length' => strlen($transcript),
                    'confidence' => $avgConfidence,
                    'response_time_ms' => $responseTimeMs,
                ]);

                return $result;
            }

            throw new Exception('Google Speech API error: ' . $response->body(), $response->status());
        } catch (Throwable $e) {
            $responseTimeMs = isset($responseTimeMs) ? $responseTimeMs : (int) ((microtime(true) - $startTime) * 1000);
            
            AIServiceLog::logError(
                null,
                'google_speech',
                'transcribe',
                $e->getMessage(),
                $responseTimeMs
            );

            Log::error('Google Speech-to-Text transcription failed', [
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTimeMs,
            ]);

            throw $e;
        }
    }

    /**
     * Transcribe audio from file path
     * 
     * @param string $filePath Path to audio file
     * @param string $languageCode Language code
     * @return array
     * @throws Exception
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
