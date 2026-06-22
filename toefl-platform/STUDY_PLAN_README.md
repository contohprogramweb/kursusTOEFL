# Study Plan & Rekomendasi - Dokumentasi Implementasi

## Fitur yang Diimplementasikan (FR-3.2.4, FR-3.6.3)

### 1. Generator Rencana Belajar

**Input yang Diterima:**
- `target_score`: Target skor TOEFL (0-677 untuk ITP)
- `test_date`: Tanggal rencana test
- `daily_hours`: Jam belajar per hari (0.5 - 12 jam)
- `available_days`: Array hari tersedia [0-6] (0=Minggu, 6=Sabtu)

**Algoritma Generator (PHP Native):**
```php
StudyPlanGeneratorService::generatePlan()
```

**Logika Algoritma:**
1. Hitung total waktu tersedia hingga test date
2. Analisis skor terakhir user untuk menentukan prioritas section
3. Section dengan gap terbesar mendapat prioritas lebih tinggi (1-10)
4. Distribusikan jam belajar merata di hari tersedia
5. Sisipkan simulasi full test setiap 7 hari
6. Generate tasks: modul, practice, simulation, review

### 2. Struktur Database

#### Migrations Created:
- `2024_01_08_000001_update_study_plans_for_generator.php`

**Tabel Baru:**
- `study_plan_adjustments`: Tracking penyesuaian manual user
- `study_plan_reminders`: Log reminder notifications

**Kolom Baru di `study_plans`:**
- `target_score`, `test_date`, `daily_hours`
- `available_days` (JSON), `ai_notes`, `is_ai_generated`

**Kolom Baru di `study_plan_tasks`:**
- `estimated_minutes`, `section`, `priority`, `metadata` (JSON)

### 3. Models Created/Updated

| Model | Fungsi |
|-------|--------|
| `StudyPlan` (updated) | Main model dengan methods: `isDayAvailable()`, `getTotalEstimatedMinutes()` |
| `StudyPlanTask` (updated) | Task individual dengan scopes: `bySection()`, `byPriority()` |
| `StudyPlanAdjustment` | Tracking reschedule/skip/add_custom |
| `StudyPlanReminder` | Log pengiriman reminder |

### 4. Services

**StudyPlanGeneratorService** (`app/Services/`)
- `generatePlan()`: Buat plan baru dengan algoritma AI
- `regeneratePlan()`: Buat ulang plan dengan parameter baru
- `calculateSectionPriorities()`: Analisis skor untuk prioritas
- `generateTasks()`: Generate daftar tugas harian

### 5. Controller

**StudyPlanController** (`app/Http/Controllers/`)

| Method | Route | Fungsi |
|--------|-------|--------|
| `create()` | GET `/study-plan/create` | Form pembuatan plan |
| `store()` | POST `/study-plan` | Simpan plan baru |
| `show()` | GET `/study-plan/{id}` | Tampilkan plan + kalender |
| `completeTask()` | POST `/study-plan/task/{id}/complete` | Mark task selesai |
| `uncompleteTask()` | POST `/study-plan/task/{id}/uncomplete` | Undo completion |
| `adjustTask()` | POST `/study-plan/task/{id}/adjust` | Reschedule/skip task |
| `regenerate()` | POST `/study-plan/{id}/regenerate` | Generate ulang plan |
| `calendarData()` | GET `/study-plan/{id}/calendar` | API data untuk calendar JS |
| `sendReminder()` | POST `/study-plan/{id}/reminder` | Kirim reminder manual |

### 6. Notifications

**StudyPlanReminderNotification** (`app/Notifications/`)
- Channel: Email + Database
- Queueable untuk background processing
- Berisi: task title, type, section, estimated time

### 7. Laravel Scheduler (Cron)

**Command:** `SendStudyPlanReminders`
- Signature: `study-plan:send-reminders`
- Schedule: Daily at 07:00
- File: `routes/console.php`

**Setup Cron di Server:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Views (Blade Templates)

**Create Form:** `resources/views/study-plans/create.blade.php`
- Input form dengan validasi
- Checkbox untuk available days
- Info card penjelasan algoritma

**Show Page:** `resources/views/study-plans/show.blade.php`
- Progress bar visual
- Stats grid (target, test date, days remaining, hours/day)
- Today's tasks dengan checkbox completion
- Calendar view table
- Priority badges (High/Medium/Low)

### 9. Routes

**File:** `routes/student-dashboard.php`

```php
// Study Plan CRUD
GET  /study-plan/create
POST /study-plan
GET  /study-plan/{studyPlan}
POST /study-plan/{studyPlan}/regenerate
GET  /study-plan/{studyPlan}/calendar

// Task Management
POST /study-plan/task/{task}/complete
POST /study-plan/task/{task}/uncomplete
POST /study-plan/task/{task}/adjust

// Reminder
POST /study-plan/{studyPlan}/reminder
```

### 10. Fitur Utama

✅ **AI Schedule Generation**
- Prioritas section berdasarkan skor terendah
- Distribusi waktu merata
- Simulasi mingguan otomatis

✅ **Visual Calendar**
- Tabel kalender dengan grouping per tanggal
- Highlight hari ini
- Status completion per hari

✅ **Progress Tracking**
- Checkbox completion
- Progress bar percentage
- Auto-update parent study plan

✅ **Manual Adjustment**
- Reschedule task ke tanggal lain
- Skip task (mark as completed)
- Reason tracking

✅ **Daily Reminders**
- Auto-sent via scheduler (7 AM)
- Manual send button
- Prevent duplicate reminders

### 11. Cara Penggunaan

**1. Buat Study Plan Baru:**
```
Dashboard → "Buat Study Plan" → Isi form → Generate
```

**2. Lihat Progress:**
```
Dashboard → Study Plan Card → Klik plan → Lihat kalender
```

**3. Selesaikan Tugas:**
```
Klik checkbox pada task → Progress auto-update
```

**4. Reschedule:**
```
Klik menu task → Pilih "Reschedule" → Pilih tanggal baru
```

### 12. Testing

**Manual Test Scenario:**
1. Buat plan dengan target 550, test date +30 hari, 2 jam/hari
2. Verify tasks generated dengan prioritas benar
3. Complete beberapa task, verify progress bar update
4. Reschedule task, verify adjustment logged
5. Run `php artisan study-plan:send-reminders` manually
6. Check notification sent

**Test Command:**
```bash
php artisan study-plan:send-reminders
```

### 13. Dependencies

- Laravel 10.x+
- Carbon (datetime handling)
- Tailwind CSS (styling)
- Queue driver (database/redis) untuk async notifications

### 14. Catatan Penting

- User hanya bisa akses study plan mereka sendiri (authorization check)
- Tasks di-generate dengan metadata JSON untuk fleksibilitas
- Reminder dicek duplikasi per user-task-date
- Regenerate plan akan menghapus semua tasks lama
- Available days default: Senin-Jumat [1,2,3,4,5]

---

**Status:** ✅ Implementasi Lengkap
**Files Created:** 12 files
**Lines of Code:** ~1500+ lines
