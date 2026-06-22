<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'role',
        'status',
        'email_verified',
        'sso_provider',
        'sso_id',
        'login_attempts',
        'locked_until',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'password_hash' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_until')
            ->where('locked_until', '>', now());
    }

    /**
     * Accessors & Mutators
     */
    public function getFullNameAttribute($value)
    {
        return ucwords($value ?? '');
    }

    /**
     * Relationships
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)->where('status', 'active');
    }

    public function simulationResults(): HasMany
    {
        return $this->hasMany(SimulationResult::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->whereNull('read_at');
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'author_id');
    }

    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'author_id');
    }

    public function instructorFeedbacks(): HasMany
    {
        return $this->hasMany(InstructorFeedback::class, 'instructor_id');
    }

    public function studentFeedbacks(): HasMany
    {
        return $this->hasMany(InstructorFeedback::class, 'student_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'class_enrollments', 'student_id', 'class_id')
            ->withPivot('enrolled_at', 'status')
            ->withTimestamps();
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->classes()->wherePivot('status', 'active');
    }

    public function taughtClasses(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'instructor_id');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function learningProgresses(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function exerciseHistories(): HasMany
    {
        return $this->hasMany(ExerciseHistory::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(Streak::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy(): HasMany
    {
        return $this->hasMany(Referral::class, 'referee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function markedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'marked_by');
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(ParentStudentLink::class, 'parent_id');
    }

    public function studentLinks(): HasMany
    {
        return $this->hasMany(ParentStudentLink::class, 'student_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student_links', 'parent_id', 'student_id')
            ->withPivot('status', 'invited_by')
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student_links', 'student_id', 'parent_id')
            ->withPivot('status', 'invited_by')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdModules(): HasMany
    {
        return $this->hasMany(Module::class, 'created_by');
    }

    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function createdSimulationTemplates(): HasMany
    {
        return $this->hasMany(SimulationTemplate::class, 'created_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'instructor_id');
    }

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'instructor_id');
    }

    public function createdPromoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class, 'created_by');
    }

    public function createdCoursePackages(): HasMany
    {
        return $this->hasMany(CoursePackage::class, 'created_by');
    }

    // ==================== KONSTANTA ROLE ====================
    
    const ROLE_STUDENT = 'student';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_PARENT = 'parent';
    const ROLE_GUEST = 'guest';

    // ==================== KONSTANTA STATUS ====================
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    // ==================== METHOD AUTENTIKASI ====================

    /**
     * Cek apakah user terkunci
     */
    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }

        return $this->locked_until->isFuture();
    }

    /**
     * Increment login attempts dan lock jika mencapai max (5 attempts = 30 menit lock)
     */
    public function recordLoginAttempt(bool $success): void
    {
        if ($success) {
            $this->login_attempts = 0;
            $this->locked_until = null;
        } else {
            $this->login_attempts = ($this->login_attempts ?? 0) + 1;

            if ($this->login_attempts >= 5) {
                $this->locked_until = now()->addMinutes(30);
            }
        }

        $this->save();
    }

    /**
     * Reset login attempts setelah login sukses
     */
    public function resetLoginAttempts(): void
    {
        $this->login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Cek apakah user bisa login
     */
    public function canLogin(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->isLocked()) {
            return false;
        }

        return true;
    }

    /**
     * Invalidate semua session lain (untuk logout other devices)
     */
    public function logoutOtherDevices(string $password): void
    {
        $this->forceFill([
            'remember_token' => \Str::random(60),
        ])->save();
    }

    // ==================== ROLE CHECKING ====================

    /**
     * Cek apakah user adalah student
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Cek apakah user adalah instructor
     */
    public function isInstructor(): bool
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    /**
     * Cek apakah user adalah admin atau super_admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Cek apakah user adalah super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Cek apakah user adalah parent
     */
    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    /**
     * Cek apakah user adalah guest
     */
    public function isGuest(): bool
    {
        return $this->role === self::ROLE_GUEST;
    }

    /**
     * Cek apakah user memiliki role tertentu
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Cek apakah user memiliki salah satu dari beberapa role
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Cek apakah user memiliki semua role yang diberikan
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    // ==================== ACCESSORS TAMBAHAN ====================

    /**
     * Get display name (full name atau email prefix)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->full_name) {
            return $this->full_name;
        }

        return explode('@', $this->email)[0];
    }

    /**
     * Get avatar URL dari profile
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile?->avatar_url;
    }

    // ==================== SCOPES TAMBAHAN ====================

    /**
     * Scope untuk user yang tidak terkunci
     */
    public function scopeUnlocked($query)
    {
        return $query->whereNull('locked_until')
                    ->orWhere('locked_until', '<', now());
    }

    /**
     * Scope untuk student
     */
    public function scopeStudents($query)
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    /**
     * Scope untuk instructor
     */
    public function scopeInstructors($query)
    {
        return $query->where('role', self::ROLE_INSTRUCTOR);
    }

    /**
     * Scope untuk admins (admin + super_admin)
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Scope untuk user yang suspended
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope untuk search by name or email
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Scope untuk filter by date range
     */
    public function scopeDateRange($query, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Relationship: Admin who suspended this user
     */
    public function suspendedByUser()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Relationship: Audit logs for this user
     */
    public function userAuditLogs()
    {
        return $this->hasMany(UserAuditLog::class, 'user_id');
    }

    /**
     * Relationship: Audit logs performed by this user (as admin)
     */
    public function performedAuditLogs()
    {
        return $this->hasMany(UserAuditLog::class, 'admin_id');
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Suspend user with reason
     */
    public function suspend(string $reason, User $admin): void
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->suspension_reason = $reason;
        $this->suspended_at = now();
        $this->suspended_by = $admin->id;
        $this->save();
    }

    /**
     * Unsuspend user
     */
    public function unsuspend(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->suspension_reason = null;
        $this->suspended_at = null;
        $this->suspended_by = null;
        $this->save();
    }
}
