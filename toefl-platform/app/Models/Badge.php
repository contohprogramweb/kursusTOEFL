<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'badge_type',
        'badge_name',
        'badge_description',
        'badge_icon',
        'awarded_at',
        'is_public',
        'badge_code',
        'category',
        'difficulty',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'points' => 'integer',
        ];
    }

    public const CATEGORIES = [
        'achievement' => 'Achievement',
        'consistency' => 'Consistency',
        'performance' => 'Performance',
        'speed' => 'Speed',
        'dedication' => 'Dedication',
        'special' => 'Special',
    ];

    public const DIFFICULTIES = [
        'easy' => 1,
        'medium' => 2,
        'hard' => 3,
        'legendary' => 4,
    ];

    /**
     * Badge definitions with minimum 15 types as per SRS
     */
    public const BADGES = [
        // Achievement badges
        'first_step' => [
            'name' => 'First Step',
            'description' => 'Menyelesaikan latihan pertama',
            'category' => 'achievement',
            'difficulty' => 'easy',
            'points' => 10,
            'icon' => '🎯',
        ],
        'consistency_7' => [
            'name' => 'Consistency',
            'description' => 'Streak 7 hari berturut-turut',
            'category' => 'consistency',
            'difficulty' => 'medium',
            'points' => 25,
            'icon' => '🔥',
        ],
        'consistency_30' => [
            'name' => 'Dedicated Learner',
            'description' => 'Streak 30 hari berturut-turut',
            'category' => 'consistency',
            'difficulty' => 'hard',
            'points' => 50,
            'icon' => '⭐',
        ],
        'master_reader' => [
            'name' => 'Master Reader',
            'description' => 'Skor Reading >= 25',
            'category' => 'performance',
            'difficulty' => 'hard',
            'points' => 40,
            'icon' => '📚',
        ],
        'perfect_score' => [
            'name' => 'Perfect Score',
            'description' => 'Skor total >= 110',
            'category' => 'performance',
            'difficulty' => 'legendary',
            'points' => 100,
            'icon' => '🏆',
        ],
        'speed_demon' => [
            'name' => 'Speed Demon',
            'description' => 'Selesai simulasi 10 menit lebih cepat',
            'category' => 'speed',
            'difficulty' => 'medium',
            'points' => 30,
            'icon' => '⚡',
        ],
        'night_owl' => [
            'name' => 'Night Owl',
            'description' => 'Belajar setelah jam 10 malam',
            'category' => 'dedication',
            'difficulty' => 'easy',
            'points' => 15,
            'icon' => '🦉',
        ],
        'early_bird' => [
            'name' => 'Early Bird',
            'description' => 'Belajar sebelum jam 6 pagi',
            'category' => 'dedication',
            'difficulty' => 'easy',
            'points' => 15,
            'icon' => '🌅',
        ],
        'weekend_warrior' => [
            'name' => 'Weekend Warrior',
            'description' => 'Belajar di akhir pekan',
            'category' => 'dedication',
            'difficulty' => 'easy',
            'points' => 10,
            'icon' => '📅',
        ],
        'listening_master' => [
            'name' => 'Listening Master',
            'description' => 'Skor Listening >= 25',
            'category' => 'performance',
            'difficulty' => 'hard',
            'points' => 40,
            'icon' => '🎧',
        ],
        'speaking_pro' => [
            'name' => 'Speaking Pro',
            'description' => 'Skor Speaking >= 25',
            'category' => 'performance',
            'difficulty' => 'hard',
            'points' => 40,
            'icon' => '🎤',
        ],
        'writing_expert' => [
            'name' => 'Writing Expert',
            'description' => 'Skor Writing >= 25',
            'category' => 'performance',
            'difficulty' => 'hard',
            'points' => 40,
            'icon' => '✍️',
        ],
        'marathon_runner' => [
            'name' => 'Marathon Runner',
            'description' => 'Belajar >= 2 jam dalam satu hari',
            'category' => 'dedication',
            'difficulty' => 'medium',
            'points' => 35,
            'icon' => '🏃',
        ],
        'quick_learner' => [
            'name' => 'Quick Learner',
            'description' => 'Menyelesaikan 10 latihan dalam seminggu',
            'category' => 'achievement',
            'difficulty' => 'medium',
            'points' => 30,
            'icon' => '🚀',
        ],
        'comeback_king' => [
            'name' => 'Comeback King',
            'description' => 'Mencapai streak 14 hari setelah streak terputus',
            'category' => 'consistency',
            'difficulty' => 'hard',
            'points' => 45,
            'icon' => '👑',
        ],
        'social_butterfly' => [
            'name' => 'Social Butterfly',
            'description' => 'Aktif di forum diskusi',
            'category' => 'special',
            'difficulty' => 'easy',
            'points' => 20,
            'icon' => '🦋',
        ],
        'helpful_friend' => [
            'name' => 'Helpful Friend',
            'description' => 'Membantu 5 siswa lain di forum',
            'category' => 'special',
            'difficulty' => 'medium',
            'points' => 35,
            'icon' => '🤝',
        ],
        'goal_crusher' => [
            'name' => 'Goal Crusher',
            'description' => 'Mencapai target skor study plan',
            'category' => 'achievement',
            'difficulty' => 'hard',
            'points' => 50,
            'icon' => '💪',
        ],
    ];

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get badge definition by code
     */
    public static function getBadgeDefinition(string $code): ?array
    {
        return self::BADGES[$code] ?? null;
    }

    /**
     * Check if badge exists for user
     */
    public static function hasBadge(int $userId, string $code): bool
    {
        return self::where('user_id', $userId)
            ->where('badge_code', $code)
            ->exists();
    }

    /**
     * Award badge to user
     */
    public static function award(int $userId, string $code): ?self
    {
        if (self::hasBadge($userId, $code)) {
            return null;
        }

        $definition = self::getBadgeDefinition($code);
        if (!$definition) {
            return null;
        }

        return self::create([
            'user_id' => $userId,
            'badge_type' => $definition['category'],
            'badge_name' => $definition['name'],
            'badge_description' => $definition['description'],
            'badge_icon' => $definition['icon'],
            'badge_code' => $code,
            'category' => $definition['category'],
            'difficulty' => $definition['difficulty'],
            'points' => $definition['points'],
            'awarded_at' => now(),
            'is_public' => true,
        ]);
    }

    /**
     * Get all badges for a user (public only if needed)
     */
    public static function getUserBadges(int $userId, bool $publicOnly = false)
    {
        $query = self::where('user_id', $userId)->orderBy('awarded_at', 'desc');
        
        if ($publicOnly) {
            $query->where('is_public', true);
        }
        
        return $query->get();
    }
}
