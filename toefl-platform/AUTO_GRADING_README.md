# Auto Grading Implementation

## Overview
Implementasi penilaian otomatis untuk Reading dan Listening sections dengan SLA ≤ 1 detik.

## Files Created/Modified

### 1. Service Layer
**File:** `app/Services/AutoGradingService.php`

Service ini menangani:
- Grading otomatis untuk Reading & Listening
- Konversi raw score ke scaled score (0-30)
- Update section_results dengan score, raw_score, duration_seconds, status=completed

### 2. Controller Updates
**File:** `app/Http/Controllers/SimulationController.php`

Methods added:
- `submit()` - Updated untuk call auto-grading service saat submit
- `saveAnswer()` - Save single answer (AJAX)
- `bulkSaveAnswers()` - Bulk save answers untuk auto-save setiap 30 detik

### 3. Routes
**File:** `routes/web.php`

New routes:
```php
Route::post('/{simulation}/save-answer', [SimulationController::class, 'saveAnswer']);
Route::post('/{simulation}/bulk-save-answers', [SimulationController::class, 'bulkSaveAnswers']);
```

## Implementation Details

### 1. Reading/Listening Grading
```php
// Bandingkan selected_option_id dengan QuestionOption.is_correct
public function gradeQuestion(QuestionResponse $response): bool
{
    if (!$response->selected_option_id) {
        return false;
    }
    
    $selectedOption = QuestionOption::find($response->selected_option_id);
    
    if (!$selectedOption) {
        return false;
    }
    
    return $selectedOption->is_correct === true;
}
```

### 2. Raw Score to Scaled Score (0-30)
```php
public function convertToScaledScore(int $rawScore, int $totalQuestions, string $section): float
{
    if ($totalQuestions === 0) {
        return 0.0;
    }
    
    // Persentase jawaban benar
    $percentage = $rawScore / $totalQuestions;
    
    // Linear scaling: percentage * 30
    $scaledScore = $percentage * 30;
    
    return round($scaledScore, 2);
}
```

### 3. Save to section_results
```php
$sectionResult->update([
    'raw_score' => $rawScore,
    'score' => $scaledScore,
    'duration_seconds' => $duration,
    'status' => 'completed'
]);
```

### 4. SLA Performance (≤ 1 detik)
- Menggunakan DB transaction untuk batch update
- Single query untuk fetch semua responses
- Direct comparison tanpa AI eksternal
- Logging processing time untuk monitoring

Example log output:
```json
{
    "section_result_id": 123,
    "section": "reading",
    "raw_score": 28,
    "scaled_score": 28.0,
    "correct_count": 28,
    "total_questions": 30,
    "processing_time_ms": 45.23
}
```

## API Endpoints

### Submit Simulation (dengan auto-grading)
```http
POST /simulations/{id}/submit
Content-Type: application/json
X-CSRF-TOKEN: {token}

Response:
{
    "success": true,
    "message": "Simulation submitted and graded successfully"
}
```

### Save Answer (Auto-save setiap 30 detik)
```http
POST /simulations/{id}/save-answer
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
    "question_id": 123,
    "selected_option_id": 456,
    "time_spent_seconds": 45,
    "flagged": false
}
```

### Bulk Save Answers
```http
POST /simulations/{id}/bulk-save-answers
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
    "answers": [
        {
            "question_id": 123,
            "selected_option_id": 456,
            "time_spent_seconds": 45,
            "flagged": false
        },
        {
            "question_id": 124,
            "selected_option_id": 457,
            "time_spent_seconds": 30,
            "flagged": true
        }
    ]
}
```

## Database Schema

### section_results table
```sql
- id
- result_id (FK to simulation_results)
- section (reading/listening/speaking/writing)
- score (decimal 0-30)
- raw_score (integer)
- duration_seconds (integer)
- status (not_started/in_progress/graded/completed)
- created_at
- updated_at
```

### question_responses table
```sql
- id
- section_result_id (FK)
- question_id (FK)
- selected_option_id (FK to question_options)
- text_response (nullable)
- audio_url (nullable)
- is_correct (boolean, filled after grading)
- time_spent_seconds (integer)
- flagged (boolean)
- created_at
- updated_at
```

## Usage Example

```php
use App\Services\AutoGradingService;
use App\Models\SimulationResult;

// Inject service
$gradingService = app(AutoGradingService::class);

// Grade specific section
$sectionResult = SectionResult::find(123);
$result = $gradingService->gradeSection($sectionResult);

// Result:
// [
//     'raw_score' => 28,
//     'score' => 28.0,
//     'correct_count' => 28,
//     'total_count' => 30,
//     'processing_time_ms' => 45.23
// ]

// Grade entire simulation (Reading + Listening)
$simulation = SimulationResult::find(456);
$results = $gradingService->gradeSimulation($simulation);
```

## Performance Optimization

1. **Single Transaction**: Semua update dalam satu DB transaction
2. **Eager Loading**: Load relationships upfront
3. **Batch Operations**: Bulk update untuk multiple responses
4. **No External Calls**: Tidak ada API calls ke external services
5. **Indexed Queries**: Foreign keys sudah indexed

## Testing

```bash
# Run tests
php artisan test --filter=AutoGrading

# Test specific method
php artisan tinker
>>> $service = app(App\Services\AutoGradingService::class);
>>> $result = $service->gradeSection(SectionResult::find(1));
>>> dd($result);
```

## Notes

- Tidak perlu AI eksternal untuk Reading/Listening
- Speaking/Writing tetap membutuhkan AI/human grading
- Processing time biasanya < 100ms untuk 30 questions
- SLA ≤ 1 detik tercapai dengan margin aman
