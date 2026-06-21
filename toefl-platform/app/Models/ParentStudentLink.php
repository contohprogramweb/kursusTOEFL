<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentStudentLink extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_REVOKED = 'revoked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'student_id',
        'status',
        'invited_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the parent user (orang tua).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the student user (siswa).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the user who initiated the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending links.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get active links.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get revoked links.
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', self::STATUS_REVOKED);
    }

    /**
     * Check if the link is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the link is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the link is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /**
     * Approve the link.
     */
    public function approve(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Revoke the link.
     */
    public function revoke(): bool
    {
        return $this->update(['status' => self::STATUS_REVOKED]);
    }
}
