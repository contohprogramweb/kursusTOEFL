<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AiTransparency extends Component
{
    public $content;
    public $highlights;
    public $confidenceScore;
    public $type;
    public $audioUrl;
    public $statistics;

    /**
     * Create a new component instance.
     *
     * @param string $content Teks atau transkrip
     * @param array $highlights Array highlight dari AI (format: position, type, message, suggestion, example)
     * @param int $confidenceScore Skor confidence 0-100
     * @param string $type 'speaking' atau 'writing'
     * @param string|null $audioUrl URL file audio (opsional, untuk speaking)
     */
    public function __construct(
        string $content,
        array $highlights = [],
        int $confidenceScore = 85,
        string $type = 'writing',
        ?string $audioUrl = null
    ) {
        $this->content = $content;
        $this->highlights = $this->normalizeHighlights($highlights);
        $this->confidenceScore = max(0, min(100, $confidenceScore));
        $this->type = $type;
        $this->audioUrl = $audioUrl;
        $this->statistics = $this->calculateStatistics($content);
    }

    /**
     * Normalize highlights format
     */
    protected function normalizeHighlights(array $highlights): array
    {
        return collect($highlights)->map(function ($highlight) {
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
    }

    /**
     * Calculate text statistics
     */
    protected function calculateStatistics(string $text): array
    {
        // Word count
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        // Sentence count
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        // Average sentence length
        $avgSentenceLength = $sentenceCount > 0 
            ? round($wordCount / $sentenceCount, 1) 
            : 0;

        // Unique words count (case-insensitive)
        $uniqueWords = collect($words)
            ->map(fn($word) => strtolower(preg_replace('/[^a-z0-9]/i', '', $word)))
            ->filter()
            ->unique()
            ->count();

        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'avg_sentence_length' => $avgSentenceLength,
            'unique_words_count' => $uniqueWords,
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.ai-transparency');
    }
}
