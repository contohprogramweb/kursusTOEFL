# Dasbor Siswa (FR-3.6.1) - Dokumentasi Implementasi

## Ringkasan
Implementasi dasbor siswa dengan card-based layout yang dioptimalkan untuk waktu muat ≤ 3 detik menggunakan eager loading Eloquent dan caching.

## File yang Dibuat

### 1. Migration
**`database/migrations/2024_01_06_000001_create_student_dashboard_tables.php`**
- Tabel `study_plans` - Rencana belajar siswa
- Tabel `study_plan_tasks` - Tugas dalam study plan
- Tabel `badges` - Master data lencana
- Tabel `user_badges` - Lencana yang diperoleh siswa
- Tabel `recommendations` - Rekomendasi AI harian
- Tabel `daily_activity_logs` - Log aktivitas harian
- Tabel `simulation_results` - Hasil simulasi (jika belum ada)

### 2. Models
**`app/Models/StudyPlan.php`**
- Relasi: `user()`, `tasks()`
- Accessor: `next_task`, `progress_percentage`, `days_remaining`
- Scope: `active()`

**`app/Models/StudyPlanTask.php`**
- Relasi: `studyPlan()`
- Scope: `incomplete()`
- Method: `markAsCompleted()`

**`app/Models/Badge.php`**
- Relasi: `users()`
- Scope: `earnededBy()`

**`app/Models/Recommendation.php`**
- Relasi: `user()`
- Scope: `unread()`, `byPriority()`
- Accessor: `icon`

**`app/Models/DailyActivityLog.php`**
- Relasi: `user()`
- Method: `getOrCreateToday()`, `addStudyDuration()`, dll.
- Accessor: `formatted_study_duration`

### 3. Controller
**`app/Http/Controllers/StudentDashboardController.php`**
- Method `index()` - Menampilkan dasbor dengan caching 5 menit
- Method `buildDashboardData()` - Mengumpulkan semua data dengan query optimal
- Method `calculateStreak()` - Menghitung streak belajar berturut-turut
- Method `refresh()` - Membersihkan cache dashboard

### 4. Views
**`resources/views/student/dashboard.blade.php`**
- Layout grid responsif (mobile-first)
- 6 section utama: Summary, Study Plan, Badges, Last Score, Recommendations, Quick Actions

**Komponen Blade:**
- `components/stats-card.blade.php` - Card statistik harian
- `components/study-plan-card.blade.php` - Card progress study plan
- `components/badges-card.blade.php` - Card streak dan lencana
- `components/recommendation-item.blade.php` - Item rekomendasi individual

### 5. Routes
**`routes/student-dashboard.php`**
- `/student/dashboard` - Halaman utama dasbor
- `/student/dashboard/refresh` - Endpoint refresh cache
- Placeholder routes untuk quick actions

## Fitur yang Diimplementasikan

### ✅ 1. Ringkasan Hari Ini
- Waktu belajar (format HH:MM)
- Jumlah soal dikerjakan
- Simulasi diikuti
- Data dari `daily_activity_logs`

### ✅ 2. Rekomendasi Harian
- 3-5 item berdasarkan prioritas
- Tipe: modul, latihan, simulasi
- Alasan rekomendasi dari AI analysis
- Badge "AI Analysis" untuk transparansi

### ✅ 3. Status Study Plan
- Progress bar persentase
- Hari tersisa hingga deadline
- Next task dengan tipe dan judul
- Link ke detail study plan

### ✅ 4. Streak & Lencana
- Counter streak hari berturut-turut
- Grid 4 lencana terbaru
- Tooltip hover/focus dengan detail
- Lazy loading untuk gambar lencana

### ✅ 5. Skor Terakhir
- Skor total simulasi terakhir
- Trend indicator (naik/turun/stabil)
- Tanggal simulasi terakhir
- Fallback jika belum ada simulasi

### ✅ 6. Quick Actions
- Tombol "Mulai Latihan"
- Tombol "Mulai Simulasi"
- Tombol "Lanjutkan Modul" (jika ada next task)
- Animasi hover bounce untuk feedback visual

## Optimasi Performa

### Eager Loading
```php
StudyPlan::with([
    'tasks' => fn($q) => $q->incomplete()->orderBy('order')->limit(1)
])
```

### Caching
```php
Cache::remember("dashboard_data_user_{$user->id}", 300, function () {
    // Build dashboard data
});
```

### Database Indexing
- Index pada `user_id`, `status` di `study_plans`
- Index pada `user_id`, `is_read`, `priority` di `recommendations`
- Unique index pada `user_id`, `activity_date` di `daily_activity_logs`

### Query Optimization
- Menggunakan `limit()` untuk membatasi hasil
- Join query untuk badges daripada nested queries
- Single query untuk daily activity log

## Accessibility (A11y)

- ARIA labels pada semua card interaktif
- Role attributes: `article`, `status`, `progressbar`, `list`, `listitem`
- Keyboard navigation dengan `tabindex="0"`
- Focus states untuk tooltip
- Screen reader friendly dengan `aria-live="polite"`
- `aria-hidden="true"` untuk dekorasi emoji/icon

## Cara Penggunaan

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Daftarkan Routes
Tambahkan ke `routes/web.php`:
```php
require __DIR__.'/student-dashboard.php';
```

### 3. Seed Data (Opsional)
```php
// Buat seeder untuk data dummy
php artisan make:seeder DashboardSeeder
```

### 4. Akses Dashboard
```
GET /student/dashboard
```

### 5. Refresh Cache Manual
```
POST /student/dashboard/refresh
```

## Testing

### Unit Test Example
```php
public function test_dashboard_loads_within_3_seconds()
{
    $start = microtime(true);
    
    $response = $this->actingAs($user)->get('/student/dashboard');
    
    $duration = (microtime(true) - $start) * 1000; // ms
    
    $response->assertStatus(200);
    $this->assertLessThan(3000, $duration, 'Dashboard should load in under 3 seconds');
}
```

## SLA Target

| Metrik | Target | Implementasi |
|--------|--------|--------------|
| Load Time (p95) | ≤ 3 detik | Caching 5 menit + eager loading |
| Query Count | ≤ 10 queries | Optimized dengan eager loading |
| Cache Hit Rate | ≥ 80% | File/database cache driver |

## Catatan Tambahan

1. **Cache Invalidation**: Cache akan otomatis invalid setelah 5 menit atau bisa manual via POST endpoint
2. **Timezone**: Pastikan timezone aplikasi sesuai untuk perhitungan streak
3. **Placeholder Routes**: Quick action routes masih placeholder, implementasi sebenarnya diperlukan
4. **Layout Base**: View mengasumsikan `layouts.app` sudah ada dengan Tailwind CSS

## Dependensi

- Laravel 10+ 
- PHP 8.1+
- Tailwind CSS (untuk styling)
- Carbon (untuk date manipulation)
