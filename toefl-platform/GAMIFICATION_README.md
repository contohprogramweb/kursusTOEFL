# Implementasi Gamifikasi (FR-3.6.4)

## Overview
Implementasi fitur gamifikasi sesuai SRS TOEFL v2.0, mencakup:
1. **Streak System** - Tracking hari belajar berturut-turut
2. **Lencana (Badges)** - Minimum 15 jenis lencana pencapaian
3. **Leaderboard** - Peringkat per institusi (opsional)

## Files Created/Modified

### Database Migrations
1. `database/migrations/2024_01_06_000001_create_streak_freezes_table.php`
   - Tabel untuk streak freeze (sakit/urgent/libur)
   - Max 1x penggunaan per minggu

2. `database/migrations/2024_01_06_000002_update_badges_table.php`
   - Menambahkan kolom: badge_code, category, difficulty, points
   - Support untuk 15+ jenis lencana

### Models
1. `app/Models/Streak.php` (Updated)
   - Methods: canUseFreeze(), useFreeze(), isTodayFrozen(), isDateFrozen()
   
2. `app/Models/StreakFreeze.php` (New)
   - Model untuk tracking penggunaan streak freeze
   
3. `app/Models/Badge.php` (Updated)
   - 18 badge definitions sesuai SRS
   - Methods: award(), hasBadge(), getUserBadges()

### Notifications
1. `app/Notifications/StreakWarningNotification.php`
   - Notifikasi "Streak akan terputus dalam 6 jam"
   - Channels: mail, database, broadcast
   
2. `app/Notifications/BadgeEarnedNotification.php`
   - Notifikasi saat mendapat lencana baru
   - Include confetti trigger (2 detik)

### Services
1. `app/Services/GamificationService.php`
   - recordActivity() - Track aktivitas belajar >= 15 menit
   - useFreeze() - Gunakan streak freeze
   - checkStreakBadges() - Award badges untuk streak
   - checkPerformanceBadges() - Award badges untuk skor
   - checkTimeBasedBadges() - Night Owl, Early Bird, Weekend Warrior
   - sendStreakWarnings() - Kirim notifikasi (scheduler)
   - calculateNightlyStreaks() - Hitung streak nightly (scheduler)
   - getLeaderboard() - Dapatkan peringkat

### Controllers
1. `app/Http/Controllers/Gamification/GamificationController.php`
   - GET /gamification/stats - User stats
   - GET /gamification/badges - User badges
   - GET /gamification/badges/all - All available badges
   - PATCH /gamification/badges/{badge}/visibility - Toggle visibility
   - POST /gamification/streak/freeze - Use freeze
   - GET /gamification/leaderboard - Leaderboard

### Routes
1. `routes/console.php` (Updated)
   - Daily 00:00 - calculateNightlyStreaks
   - Daily 18:00 - sendStreakWarnings
   
2. `routes/web.php` (Updated)
   - Gamification API routes

## Badge Definitions (18 types)

### Achievement Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| first_step | First Step | Menyelesaikan latihan pertama | 10 |
| quick_learner | Quick Learner | 10 latihan dalam seminggu | 30 |
| goal_crusher | Goal Crusher | Mencapai target study plan | 50 |

### Consistency Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| consistency_7 | Consistency | Streak 7 hari | 25 |
| consistency_30 | Dedicated Learner | Streak 30 hari | 50 |
| comeback_king | Comeback King | Streak 14 hari setelah break | 45 |

### Performance Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| master_reader | Master Reader | Reading >= 25 | 40 |
| listening_master | Listening Master | Listening >= 25 | 40 |
| speaking_pro | Speaking Pro | Speaking >= 25 | 40 |
| writing_expert | Writing Expert | Writing >= 25 | 40 |
| perfect_score | Perfect Score | Total >= 110 | 100 |

### Speed Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| speed_demon | Speed Demon | Selesai 10 menit lebih cepat | 30 |

### Dedication Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| night_owl | Night Owl | Belajar setelah jam 10 malam | 15 |
| early_bird | Early Bird | Belajar sebelum jam 6 pagi | 15 |
| weekend_warrior | Weekend Warrior | Belajar di akhir pekan | 10 |
| marathon_runner | Marathon Runner | Belajar >= 2 jam/hari | 35 |

### Special Badges
| Code | Name | Description | Points |
|------|------|-------------|--------|
| social_butterfly | Social Butterfly | Aktif di forum | 20 |
| helpful_friend | Helpful Friend | Membantu 5 siswa di forum | 35 |

## Streak Rules

1. **Minimum Activity**: 15 menit belajar/hari
2. **Streak Freeze**: 
   - Self-report (sakit/urgent/libur)
   - Max 1x per minggu
   - Reset setiap minggu
3. **Notification**: "Streak akan terputus dalam 6 jam" dikirim jam 18:00

## Scheduler Setup

Tambahkan ke crontab:
```bash
* * * * * cd /path/to/toefl-platform && php artisan schedule:run >> /dev/null 2>&1
```

Atau gunakan Laravel Forge/Envoyer untuk managed scheduling.

## API Usage Examples

### Get User Stats
```javascript
GET /gamification/stats
Response:
{
  "success": true,
  "data": {
    "current_streak": 7,
    "longest_streak": 14,
    "total_badges": 5,
    "total_points": 150,
    "badges": [...],
    "can_use_freeze": true
  }
}
```

### Use Streak Freeze
```javascript
POST /gamification/streak/freeze
{
  "reason": "sick", // sick|urgent|holiday
  "notes": "Demam tinggi"
}
```

### Toggle Badge Visibility
```javascript
PATCH /gamification/badges/123/visibility
{
  "is_public": false
}
```

### Get Leaderboard
```javascript
GET /gamification/leaderboard?type=streak&limit=10&institution_id=1
```

## Frontend Integration

### Confetti Animation (2 seconds)
```javascript
// When receiving badge_earned notification
if (notification.data.show_confetti) {
  startConfetti({
    duration: notification.data.confetti_duration || 2000
  });
}
```

### Streak Display
```javascript
// Display streak with fire animation
<div class="streak-counter">
  <span class="fire-icon">🔥</span>
  <span>{{ currentStreak }} hari</span>
</div>
```

## Testing

Run migrations:
```bash
php artisan migrate
```

Test service:
```bash
php artisan tinker
>>> $service = app(\App\Services\GamificationService::class);
>>> $service->getUserStats(1);
```

Test scheduler:
```bash
php artisan schedule:test
```

## Compliance with SRS

✅ Streak: Hitung hari berturut-turut dengan aktivitas >= 15 menit
✅ Streak Freeze: self-report (sakit/urgent/libur), max 1x/minggu
✅ Notifikasi: "Streak akan terputus dalam 6 jam"
✅ Lencana: 18 jenis (minimum 15 sesuai SRS)
✅ Animasi confetti: 2 detik saat dapat lencana
✅ Toggle visibility: Sembunyikan dari profil publik
✅ Leaderboard: Opsional, per institusi
✅ Laravel Scheduler: Nightly streak calculation
✅ Laravel Notification: Streak notifications
