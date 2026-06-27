<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Grading Transparency - FR-3.5.4</title>
    <link rel="stylesheet" href="{{ asset('css/ai-transparency.css') }}">
</head>
<body>
    <div class="ai-grading-container" role="region" aria-label="Hasil Penilaian AI">
        
        <!-- Header dengan Confidence Score -->
        <div class="grading-header">
            <h2>Hasil Penilaian AI</h2>
            <div class="confidence-score" role="progressbar" aria-valuenow="{{ $confidenceScore ?? 85 }}" aria-valuemin="0" aria-valuemax="100" aria-label="AI Confidence: {{ $confidenceScore ?? 85 }}%">
                <div class="confidence-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="circle" stroke-dasharray="{{ $confidenceScore ?? 85 }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.35" class="percentage" text-anchor="middle">{{ $confidenceScore ?? 85 }}%</text>
                    </svg>
                </div>
                <span class="confidence-label">AI Confidence</span>
            </div>
        </div>

        <!-- Toggle Anotasi -->
        <div class="annotation-toggle">
            <label class="switch">
                <input type="checkbox" id="toggleAnnotations" checked aria-label="Toggle tampilan anotasi">
                <span class="slider round"></span>
            </label>
            <span id="toggleLabel">Tampilkan Anotasi</span>
        </div>

        <!-- Legend Warna -->
        <div class="color-legend" role="list" aria-label="Legenda warna anotasi">
            <div class="legend-item" role="listitem">
                <span class="color-box grammar-error"></span>
                <span>Grammar Error</span>
            </div>
            <div class="legend-item" role="listitem">
                <span class="color-box vocabulary-issue"></span>
                <span>Vocabulary Issue</span>
            </div>
            <div class="legend-item speaking-only" role="listitem">
                <span class="color-box pronunciation-fluency"></span>
                <span>Pronunciation/Fluency</span>
            </div>
            <div class="legend-item" role="listitem">
                <span class="color-box organization-issue"></span>
                <span>Organization/Development</span>
            </div>
        </div>

        <!-- Area Konten dengan Highlight -->
        <div class="content-area">
            <div id="highlightedContent" class="highlighted-text" role="document" aria-label="Teks dengan anotasi AI">
                <!-- Konten akan di-render oleh JavaScript -->
            </div>
            
            <!-- Audio Player untuk Speaking -->
            <div id="audioPlayer" class="audio-player" style="display: none;" role="region" aria-label="Pemutar audio transkrip">
                <audio id="audioElement" controls aria-label="Audio rekaman speaking">
                    <source src="" type="audio/mp3">
                    Browser Anda tidak mendukung elemen audio.
                </audio>
                <div id="audioHighlights" class="audio-highlights"></div>
            </div>
        </div>

        <!-- Tooltip Container -->
        <div id="tooltip" class="ai-tooltip" role="tooltip" aria-hidden="true">
            <div class="tooltip-header">
                <span class="tooltip-type"></span>
                <button class="tooltip-close" aria-label="Tutup tooltip">&times;</button>
            </div>
            <div class="tooltip-body">
                <p class="tooltip-explanation"></p>
                <div class="tooltip-suggestion">
                    <strong>Saran Perbaikan:</strong>
                    <p class="tooltip-suggestion-text"></p>
                </div>
                <div class="tooltip-example">
                    <strong>Contoh Benar:</strong>
                    <p class="tooltip-example-text"></p>
                </div>
            </div>
        </div>

        <!-- Statistik -->
        <div class="statistics-panel" role="region" aria-label="Statistik teks">
            <h3>Statistik</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value" id="wordCount">0</span>
                    <span class="stat-label">Word Count</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="sentenceCount">0</span>
                    <span class="stat-label">Sentence Count</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="avgSentenceLength">0</span>
                    <span class="stat-label">Avg Sentence Length</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="uniqueWordsCount">0</span>
                    <span class="stat-label">Unique Words</span>
                </div>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="disclaimer" role="note" aria-label="Disclaimer penilaian AI">
            <p>⚠️ <strong>Disclaimer:</strong> Penilaian ini menggunakan AI. Untuk evaluasi resmi, gunakan layanan ETS.</p>
        </div>

    </div>

    <script src="{{ asset('js/ai-transparency.js') }}"></script>
</body>
</html>
