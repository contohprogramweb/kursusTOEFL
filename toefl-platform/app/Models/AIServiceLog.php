<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_result_id',
        'service',
        'action',
        'status',
        'response_time_ms',
        'request_payload',
        'response_payload',
        'error_message',
        'model_version',
        'confidence',
        'is_cached',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'confidence' => 'decimal:4',
            'is_cached' => 'boolean',
            'response_time_ms' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sectionResult(): BelongsTo
    {
        return $this->belongsTo(SectionResult::class, 'section_result_id');
    }

    /**
     * Scope to get logs by service
     */
    public function scopeOfService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope to get logs by status
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get logs within date range for SLA monitoring
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Log a successful API call
     */
    public static function logSuccess(
        ?int $sectionResultId,
        string $service,
        string $action,
        int $responseTimeMs,
        array $requestPayload,
        array $responsePayload,
        ?string $modelVersion = null,
        ?float $confidence = null,
        bool $isCached = false
    ): self {
        return static::create([
            'section_result_id' => $sectionResultId,
            'service' => $service,
            'action' => $action,
            'status' => 'success',
            'response_time_ms' => $responseTimeMs,
            'request_payload' => json_encode($requestPayload),
            'response_payload' => json_encode($responsePayload),
            'model_version' => $modelVersion,
            'confidence' => $confidence,
            'is_cached' => $isCached,
        ]);
    }

    /**
     * Log a failed API call
     */
    public static function logError(
        ?int $sectionResultId,
        string $service,
        string $action,
        string $errorMessage,
        ?int $responseTimeMs = null,
        ?array $requestPayload = null,
        ?string $modelVersion = null
    ): self {
        return static::create([
            'section_result_id' => $sectionResultId,
            'service' => $service,
            'action' => $action,
            'status' => 'error',
            'response_time_ms' => $responseTimeMs,
            'request_payload' => $requestPayload ? json_encode($requestPayload) : null,
            'error_message' => $errorMessage,
            'model_version' => $modelVersion,
        ]);
    }

    /**
     * Log a fallback API call
     */
    public static function logFallback(
        ?int $sectionResultId,
        string $service,
        string $action,
        string $originalService,
        string $errorMessage
    ): self {
        return static::create([
            'section_result_id' => $sectionResultId,
            'service' => $service,
            'action' => $action,
            'status' => 'fallback',
            'error_message' => "Original service ({$originalService}) failed: {$errorMessage}",
        ]);
    }
}
