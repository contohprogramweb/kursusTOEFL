# AI Grading Integration - Speaking & Writing

## Overview

This document describes the implementation of AI grading for TOEFL Speaking and Writing sections using Laravel.

## Architecture

### Services

1. **GoogleSpeechService** (`app/Services/GoogleSpeechService.php`)
   - Integrates with Google Cloud Speech-to-Text API (REST)
   - Transcribes audio to text
   - Includes caching for repeated requests

2. **AwsTranscribeService** (`app/Services/AwsTranscribeService.php`)
   - Fallback service when Google Speech is unavailable
   - Integrates with AWS Transcribe API

3. **OpenAiGradingService** (`app/Services/OpenAiGradingService.php`)
   - Grades speaking transcripts and writing essays
   - Uses GPT-4 for analysis
   - Returns scores, feedback, highlights, and confidence levels

### Jobs (Async Processing)

1. **ProcessSpeakingGrading** (`app/Jobs/ProcessSpeakingGrading.php`)
   - SLA: <= 5 minutes (p95)
   - Transcribes audio → Grades with OpenAI → Saves results
   - Implements fallback from Google to AWS
   - Queues for manual review if confidence < 70%

2. **ProcessWritingGrading** (`app/Jobs/ProcessWritingGrading.php`)
   - SLA: <= 3 minutes (p95)
   - Sends essay to OpenAI → Saves results
   - Analyzes 4 dimensions: Grammar & Mechanics (25%), Organization (25%), Development (30%), Vocabulary (20%)
   - Queues for manual review if confidence < 70%

### Models

1. **AIGradingResult** (`app/Models/AIGradingResult.php`) - Already exists
   - Stores dimension-level scores, feedback, highlights, confidence

2. **AIGradingQueue** (`app/Models/AIGradingQueue.php`)
   - Manual review queue for low-confidence or failed AI grades
   - Tracks reason: `low_confidence`, `service_down`, `manual_override`

3. **AIServiceLog** (`app/Models/AIServiceLog.php`)
   - Logs all AI service calls for monitoring and SLA tracking
   - Records response times, success/error status, cache hits

### Database Tables (Migration)

```php
// ai_grading_queue - Manual review queue
- section_result_id (FK)
- type (speaking|writing)
- reason (low_confidence|service_down|manual_override)
- transcript (text, nullable)
- essay_text (text, nullable)
- ai_response (json)
- ai_confidence (decimal)
- status (pending|in_review|completed)
- reviewed_by (FK, nullable)
- reviewed_at (timestamp, nullable)
- reviewer_notes (text, nullable)

// ai_service_logs - Service call monitoring
- section_result_id (FK, nullable)
- service (google_speech|aws_transcribe|openai|claude)
- action (transcribe|grade_speaking|grade_writing)
- status (success|error|timeout|fallback)
- response_time_ms (integer)
- request_payload (text)
- response_payload (text)
- error_message (text)
- model_version (string)
- confidence (decimal)
- is_cached (boolean)
```

## Configuration

### Environment Variables (.env)

```env
# Google Cloud Speech-to-Text
GOOGLE_CLOUD_API_KEY=your_api_key
GOOGLE_CLOUD_PROJECT_ID=your_project_id

# OpenAI
OPENAI_API_KEY=your_api_key
OPENAI_MODEL=gpt-4
OPENAI_MAX_TOKENS=2500

# Anthropic Claude (alternative)
CLAUDE_API_KEY=your_api_key
CLAUDE_MODEL=claude-3-opus-20240229
CLAUDE_MAX_TOKENS=4096

# AI Settings
AI_CONFIDENCE_THRESHOLD=0.70
AI_SPEAKING_SLA_MS=300000
AI_WRITING_SLA_MS=180000
```

### Queue Configuration

Uses Laravel Queue with database driver:
```env
QUEUE_CONNECTION=database
```

### Cache Configuration

Uses Laravel Cache (file/database driver):
```env
CACHE_STORE=database
```

## Usage

### Dispatch Speaking Grading Job

```php
use App\Jobs\ProcessSpeakingGrading;

// After student submits speaking response
ProcessSpeakingGrading::dispatch(
    $sectionResult->id,
    $audioFilePath,
    $questionText
)->onQueue('ai-grading');
```

### Dispatch Writing Grading Job

```php
use App\Jobs\ProcessWritingGrading;

// After student submits writing essay
ProcessWritingGrading::dispatch(
    $sectionResult->id,
    $essayText,
    $questionText
)->onQueue('ai-grading');
```

## Scoring Dimensions

### Speaking (0-30 scale)
- **Delivery**: Pronunciation, intonation, pacing, fluency
- **Language Use**: Grammar accuracy, vocabulary range
- **Topic Development**: Completeness, coherence, idea progression

### Writing (0-30 scale)
- **Grammar & Mechanics** (25%): Grammar, punctuation, spelling
- **Organization** (25%): Structure, paragraphing, transitions
- **Development** (30%): Idea elaboration, supporting details
- **Vocabulary** (20%): Word choice, variety, appropriateness

## Fallback Mechanisms

### Speech-to-Text Fallback
1. Try Google Cloud Speech-to-Text first
2. If Google fails → Fall back to AWS Transcribe
3. Log fallback in `ai_service_logs` table
4. If both fail → Queue for manual review

### AI Grading Fallback
1. If AI confidence < 70% → Queue for manual review
2. If AI service down → Queue for manual review + notify admin
3. All failures logged in `ai_service_logs`

## SLA Monitoring

### Speaking: <= 5 minutes (p95)
- Total time includes: Audio upload + Transcription + AI grading + DB save
- Monitored via `response_time_ms` in `ai_service_logs`

### Writing: <= 3 minutes (p95)
- Total time includes: AI grading + DB save
- Faster since no transcription needed

### Query for SLA Compliance
```php
use App\Models\AIServiceLog;

// Get p95 response time for speaking grading in last 24 hours
$p95 = AIServiceLog::ofService('openai')
    ->ofStatus('success')
    ->forDateRange(now()->subDay(), now())
    ->where('action', 'grade_speaking')
    ->orderBy('response_time_ms')
    ->limit(ceil(AIServiceLog::count() * 0.95))
    ->max('response_time_ms');
```

## Caching Strategy

### What's Cached
- Speech-to-Text results (24 hours)
- AI grading results (7 days)

### Cache Keys
- `google_speech:{md5(audio_content + language_code)}`
- `aws_transcribe:{md5(audio_content + language_code)}`
- `openai_speaking:{md5(transcript + question_text)}`
- `openai_writing:{md5(essay_text + question_text)}`

## Manual Review Queue

Items are queued for manual review when:
1. AI confidence < 70%
2. AI service is down
3. Manual override by admin

### Process Manual Review Items
```php
use App\Models\AIGradingQueue;

// Get pending items
$pendingItems = AIGradingQueue::pending()
    ->ofType('speaking') // or 'writing'
    ->orderBy('created_at')
    ->get();

// Mark as in review
$item->markAsInReview($reviewer);

// Complete review
$item->markAsCompleted('Reviewer notes...');
```

## Error Handling

### Logging
All errors are logged with context:
- Section result ID
- Error message
- Response time
- Stack trace

### Failed Jobs
Failed jobs automatically:
1. Retry up to 2 times
2. Queue for manual review on permanent failure
3. Log to `failed_jobs` table

## Security Considerations

1. API keys stored in environment variables
2. Audio files stored securely (not publicly accessible)
3. Rate limiting on API calls (configure in Laravel)
4. Input validation before sending to external APIs

## Testing

### Unit Tests
```bash
php artisan test --filter=AiGradingTest
```

### Manual Testing
1. Submit a speaking response
2. Check `jobs` table for queued job
3. Run queue worker: `php artisan queue:work --queue=ai-grading`
4. Verify results in `ai_grading_results` table
5. Check `ai_service_logs` for API call details

## Performance Optimization

1. **Caching**: Reduces API calls for identical submissions
2. **Async Processing**: Jobs run in background via queue
3. **Timeouts**: Configured per job type (5min speaking, 3min writing)
4. **Connection Pooling**: Laravel HTTP client reuses connections

## Future Enhancements

1. Add Claude API support as alternative to OpenAI
2. Implement real-time progress updates via WebSocket
3. Add admin dashboard for manual review queue
4. Implement batch processing for bulk grading
5. Add detailed analytics for AI grading accuracy
