<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiTransparencyController extends Controller
{
    /**
     * Tampilkan halaman transparansi AI untuk writing
     * @param int $gradingResultId ID dari ai_grading_results
     */
    public function showWriting($gradingResultId)
    {
        // Contoh data - dalam implementasi nyata, ambil dari database
        $data = $this->getGradingData($gradingResultId);
        
        return view('components.ai-transparency', [
            'content' => $data['content'],
            'highlights' => $data['highlights'],
            'confidenceScore' => $data['confidence_score'],
            'type' => 'writing',
            'audioUrl' => null,
        ]);
    }

    /**
     * Tampilkan halaman transparansi AI untuk speaking
     * @param int $gradingResultId ID dari ai_grading_results
     */
    public function showSpeaking($gradingResultId)
    {
        $data = $this->getGradingData($gradingResultId);
        
        return view('components.ai-transparency', [
            'content' => $data['transcript'] ?? $data['content'],
            'highlights' => $data['highlights'],
            'confidenceScore' => $data['confidence_score'],
            'type' => 'speaking',
            'audioUrl' => $data['audio_url'] ?? null,
        ]);
    }

    /**
     * API endpoint untuk initialize transparency component
     * Digunakan oleh frontend untuk mendapatkan data dan merender komponen
     */
    public function initialize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grading_result_id' => 'required|integer|min:1',
        ]);

        $data = $this->getGradingData($validated['grading_result_id']);

        // Normalize highlights untuk frontend
        $normalizedHighlights = collect($data['highlights'])->map(function ($highlight) {
            return [
                'position' => [
                    'start' => $highlight['position']['start'] ?? $highlight['start_index'] ?? 0,
                    'end' => $highlight['position']['end'] ?? $highlight['end_index'] ?? 0,
                ],
                'type' => $highlight['type'] ?? 'grammar_error',
                'message' => $highlight['message'] ?? $highlight['explanation'] ?? '',
                'suggestion' => $highlight['suggestion'] ?? $highlight['correction'] ?? '',
                'example' => $highlight['example'] ?? $highlight['correct_form'] ?? '',
                'timestamp' => $highlight['timestamp'] ?? null,
                'confidence' => $highlight['confidence'] ?? null,
            ];
        })->sortBy('position.start')->values()->toArray();

        $responseData = [
            'content' => $data['content'],
            'highlights' => $normalizedHighlights,
            'confidence_score' => $data['confidence_score'],
            'type' => $data['type'],
        ];

        // Tambahkan audio URL untuk speaking
        if ($data['type'] === 'speaking' && isset($data['audio_url'])) {
            $responseData['audio_url'] = $data['audio_url'];
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    /**
     * Dapatkan data grading dari database (mock untuk contoh)
     */
    protected function getGradingData(int $id): array
    {
        // Dalam implementasi nyata, gunakan:
        // $result = \App\Models\AiGradingResult::findOrFail($id);
        
        // Mock data untuk contoh
        return [
            'id' => $id,
            'content' => 'The quick brown fox jumps over the lazy dog. This sentence contains every letter in the alphabet. However, there are some grammar error that need to be fixed. The vocabulary could be more diverse and interesting.',
            'transcript' => 'The quick brown fox jumps over the lazy dog. This sentence contains every letter in the alphabet.',
            'highlights' => [
                [
                    'start_index' => 98,
                    'end_index' => 112,
                    'type' => 'grammar_error',
                    'message' => 'Subject-verb agreement error. "error" should be plural.',
                    'suggestion' => 'Ganti "error" menjadi "errors"',
                    'example' => 'There are some grammar errors that need to be fixed.',
                    'confidence' => 0.95,
                ],
                [
                    'start_index' => 149,
                    'end_index' => 157,
                    'type' => 'vocabulary_issue',
                    'message' => 'Kata "diverse" bisa diganti dengan sinonim yang lebih spesifik.',
                    'suggestion' => 'Gunakan "varied", "rich", atau "extensive"',
                    'example' => 'The vocabulary could be more varied and interesting.',
                    'confidence' => 0.78,
                ],
            ],
            'confidence_score' => 85,
            'type' => 'writing',
            'audio_url' => null,
        ];
    }
}
