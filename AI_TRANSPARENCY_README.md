# AI Grading Transparency - FR-3.5.4

## Overview

Implementasi transparansi AI untuk menampilkan hasil penilaian Speaking & Writing dengan highlight inline, tooltip, confidence score, dan statistik.

## Fitur

### 1. Highlight Inline pada Teks/Audio Transkrip

Warna highlight sesuai spesifikasi:
- **Merah (#FF4444)**: Grammar error
- **Kuning (#FFBB33)**: Vocabulary issue
- **Biru (#33B5E5)**: Pronunciation/Fluency (Speaking only)
- **Oranye (#FF8800)**: Organization/Development issue

### 2. Tooltip Hover/Tap

Setiap highlight menampilkan tooltip dengan:
- Jenis kesalahan
- Penjelasan detail
- Saran perbaikan
- Contoh bentuk yang benar

### 3. Confidence Score AI

- Ditampilkan dalam circular chart (0-100%)
- Label "AI Confidence"
- Warna dinamis berdasarkan skor:
  - Hijau: ≥80%
  - Oranye: 60-79%
  - Merah: <60%

### 4. Disclaimer

```
⚠️ Disclaimer: Penilaian ini menggunakan AI. Untuk evaluasi resmi, gunakan layanan ETS.
```

### 5. Toggle "Tampilkan/Sembunyikan Anotasi"

- Switch toggle untuk menunjukkan/sembunyikan highlight
- Tetap accessible dengan keyboard navigation

### 6. Statistik

- Word count
- Sentence count
- Average sentence length
- Unique words count

## Struktur File

```
workspace/
├── app/
│   ├── Http/Controllers/
│   │   └── AiTransparencyController.php
│   ├── Models/
│   │   └── AiGradingResult.php
│   └── View/Components/
│       └── AiTransparency.php
├── database/migrations/
│   └── 2024_01_05_000002_create_ai_grading_tables.php
├── public/
│   ├── css/
│   │   └── ai-transparency.css
│   └── js/
│       └── ai-transparency.js
└── resources/views/components/
    └── ai-transparency.blade.php
```

## Cara Penggunaan

### 1. Menggunakan Blade Component

```blade
<x-ai-transparency
    :content="$essayText"
    :highlights="$aiHighlights"
    :confidence-score="85"
    type="writing"
/>
```

### 2. Untuk Speaking dengan Audio

```blade
<x-ai-transparency
    :content="$transcript"
    :highlights="$aiHighlights"
    :confidence-score="78"
    type="speaking"
    audio-url="{{ $audioFileUrl }}"
/>
```

### 3. Menggunakan JavaScript API

```javascript
// Initialize dengan data
AITransparency.init({
    content: 'The quick brown fox jumps over the lazy dog...',
    highlights: [
        {
            position: { start: 0, end: 15 },
            type: 'grammar_error',
            message: 'Subject-verb agreement error',
            suggestion: 'Ganti "jump" menjadi "jumps"',
            example: 'The fox jumps over the dog.',
            confidence: 0.95
        }
    ],
    confidence_score: 85,
    type: 'writing', // atau 'speaking'
    audio_url: 'https://example.com/audio.mp3' // opsional
});
```

### 4. Controller Methods

```php
// Menampilkan halaman transparency
Route::get('/ai-grading/writing/{id}', [AiTransparencyController::class, 'showWriting']);
Route::get('/ai-grading/speaking/{id}', [AiTransparencyController::class, 'showSpeaking']);

// API endpoint untuk data JSON
Route::post('/api/ai-transparency/initialize', [AiTransparencyController::class, 'initialize']);
```

## Format Data Highlights

```json
{
    "highlights": [
        {
            "position": {
                "start": 0,
                "end": 15
            },
            "type": "grammar_error",
            "message": "Penjelasan kesalahan",
            "suggestion": "Saran perbaikan",
            "example": "Contoh yang benar",
            "timestamp": 45.5, // opsional, untuk speaking
            "confidence": 0.95
        }
    ]
}
```

## Tipe Highlight

| Type | Deskripsi | Warna |
|------|-----------|-------|
| `grammar_error` | Kesalahan tata bahasa | #FF4444 |
| `vocabulary_issue` | Masalah kosakata | #FFBB33 |
| `pronunciation_fluency` | Pengucapan/kelancaran | #33B5E5 |
| `organization_issue` | Organisasi/pengembangan | #FF8800 |

## Accessibility (ARIA)

Komponen ini mendukung:
- `role="region"` untuk container utama
- `role="progressbar"` untuk confidence score
- `role="mark"` untuk highlight spans
- `role="tooltip"` untuk tooltip
- `aria-label` untuk deskripsi elemen
- `aria-hidden` untuk visibility tooltip
- `tabindex="0"` untuk keyboard navigation
- Keyboard navigation: Arrow keys, Enter, Space, Escape

## SLA Monitoring

Response time untuk rendering:
- Writing: ≤ 100ms (client-side rendering)
- Speaking: ≤ 200ms (termasuk audio player initialization)

## Browser Support

- Chrome/Edge: ✅ Latest
- Firefox: ✅ Latest
- Safari: ✅ Latest
- Mobile browsers: ✅ Responsive

## Testing

### Manual Testing Checklist

- [ ] Highlight muncul dengan warna yang benar
- [ ] Tooltip muncul saat hover/tap
- [ ] Tooltip menampilkan informasi lengkap
- [ ] Confidence score ditampilkan dengan benar
- [ ] Toggle annotations berfungsi
- [ ] Statistik dihitung dengan benar
- [ ] Audio player berfungsi (speaking)
- [ ] Keyboard navigation berfungsi
- [ ] Screen reader dapat membaca konten
- [ ] Responsive di mobile

### Automated Testing

```javascript
// Example test using Jest
describe('AI Transparency Component', () => {
    test('should render highlights with correct colors', () => {
        // Test implementation
    });
    
    test('should display tooltip on hover', () => {
        // Test implementation
    });
    
    test('should calculate statistics correctly', () => {
        const stats = AITransparency.calculateStatistics('Hello world. This is a test.');
        expect(stats.wordCount).toBe(6);
        expect(stats.sentenceCount).toBe(2);
    });
});
```

## Performance Optimization

1. **Lazy loading** untuk audio player (speaking only)
2. **Event delegation** untuk highlight interactions
3. **Debounced resize handler** untuk tooltip repositioning
4. **CSS containment** untuk isolated rendering

## Security

- XSS prevention dengan HTML escaping
- Content Security Policy (CSP) compatible
- No eval() atau innerHTML dengan user input langsung

## Integration dengan AI Grading System

```php
// Dalam OpenAiGradingService.php setelah grading
$highlights = collect($aiResponse['highlights'])->map(function ($highlight) {
    return [
        'position' => [
            'start' => $highlight['start_index'],
            'end' => $highlight['end_index'],
        ],
        'type' => $this->mapErrorType($highlight['category']),
        'message' => $highlight['explanation'],
        'suggestion' => $highlight['correction'],
        'example' => $highlight['correct_example'],
        'confidence' => $highlight['confidence_score'],
    ];
})->toArray();

// Simpan ke database
AiGradingResult::create([
    'gradable_type' => WritingSubmission::class,
    'gradable_id' => $submission->id,
    'highlights' => $highlights,
    'confidence' => $aiResponse['overall_confidence'],
    // ... other fields
]);
```

## Troubleshooting

### Highlight tidak muncul
- Pastikan data highlights memiliki format yang benar
- Check console untuk JavaScript errors
- Verify CSS file loaded correctly

### Tooltip tidak muncul
- Check apakah annotations visible (toggle status)
- Verify tooltip element exists in DOM
- Check z-index conflicts

### Statistik salah
- Verify text content tidak empty
- Check special characters handling

## License

Internal use only - TOEFL Preparation Platform
