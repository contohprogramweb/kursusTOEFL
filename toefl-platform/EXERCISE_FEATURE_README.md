# Latihan Interaktif TOEFL (FR-3.3.2)

## Fitur yang Diimplementasikan

✅ **1. User pilih section & jumlah soal**
- Form pemilihan section (Reading, Listening, Speaking, Writing)
- Input jumlah soal (1-50)
- Opsi timer per soal (opsional)

✅ **2. Tampilkan soal satu per satu**
- Navigasi soal menggunakan AJAX tanpa reload halaman
- Support berbagai tipe soal: multiple choice, fill blank, speaking, writing
- Split view untuk Reading (passage text + question)

✅ **3. Timer per soal (opsional)**
- Countdown timer per soal
- Visual warning ketika waktu hampir habis
- Auto-move ke soal berikutnya ketika waktu habis

✅ **4. Simpan jawaban sementara (session/database)**
- Model `ExerciseSession` menyimpan jawaban sementara
- Auto-save saat user mengetik atau memilih opsi
- Data tersimpan di database (JSON column)

✅ **5. Submit → koreksi instan untuk Reading/Listening**
- Instant grading untuk multiple choice questions
- Perbandingan jawaban user dengan correct_answer
- Kalkulasi score otomatis

✅ **6. Simpan ke exercise_histories**
- Setiap latihan yang selesai disimpan ke tabel `exercise_histories`
- Menyimpan: score, total_questions, correct_answers, duration_seconds

✅ **7. Umpan balik: benar/salah + explanation**
- Hasil detail per soal setelah submit
- Indikator visual (hijau=benar, merah=salah)
- Penjelasan/explanation untuk setiap soal

✅ **8. Riwayat latihan**
- Halaman history menampilkan semua latihan yang pernah dikerjakan
- Pagination untuk riwayat yang banyak
- Filter visual berdasarkan section

## Struktur File

### Database Migrations
```
database/migrations/
└── 2024_01_03_000001_create_exercise_sessions_table.php
```

### Models
```
app/Models/
├── ExerciseSession.php    # Session latihan aktif
└── ExerciseHistory.php    # Riwayat latihan (sudah ada)
```

### Controllers
```
app/Http/Controllers/
└── ExerciseController.php  # Main controller untuk latihan
```

### Views
```
resources/views/exercises/
├── index.blade.php   # Halaman pemilihan section
├── show.blade.php    # Halaman latihan interaktif
└── history.blade.php # Halaman riwayat latihan
```

### Routes
```php
// routes/web.php
GET  /exercises          -> index()
POST /exercises/create   -> create()
GET  /exercises/{session} -> show()
GET  /exercises/history  -> history()

// AJAX Endpoints
GET  /exercises/{session}/question  -> getCurrentQuestion()
POST /exercises/{session}/answer    -> saveAnswer()
POST /exercises/{session}/next      -> nextQuestion()
POST /exercises/{session}/previous  -> previousQuestion()
POST /exercises/{session}/submit    -> submit()
GET  /exercises/api/statistics      -> statistics()
```

## Cara Penggunaan

### 1. Instalasi
```bash
# Jalankan migration
php artisan migrate

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Akses Fitur
- Buka halaman `/exercises` (harus login)
- Pilih section dan jumlah soal
- Klik "Mulai Latihan"

### 3. Selama Latihan
- Jawaban otomatis tersimpan saat dipilih/diketik
- Gunakan tombol Next/Previous untuk navigasi
- Timer berjalan jika diaktifkan
- Semua navigasi menggunakan AJAX (tanpa reload)

### 4. Setelah Submit
- Lihat score dan hasil detail
- Review setiap soal dengan penjelasan
- Riwayat tersimpan otomatis di `/exercises/history`

## API Endpoints (AJAX)

### Get Current Question
```javascript
GET /exercises/{sessionId}/question
Response: {
  success: true,
  question: { id, section, question_type, question_text, passage_text, audio_url, image_url, options },
  current_index: 0,
  total_questions: 10,
  current_answer: "A",
  session_id: 1
}
```

### Save Answer
```javascript
POST /exercises/{sessionId}/answer
Body: { question_id: 1, answer: "A" }
Response: { success: true, message: "Answer saved successfully." }
```

### Navigate
```javascript
POST /exercises/{sessionId}/next
POST /exercises/{sessionId}/previous
Response: { success: true, question: {...}, has_next: true, has_previous: false }
```

### Submit Exercise
```javascript
POST /exercises/{sessionId}/submit
Response: {
  success: true,
  score: 85.5,
  correct_count: 8,
  total_questions: 10,
  results: [
    {
      question_id: 1,
      question_text: "...",
      user_answer: "A",
      correct_answer: "A",
      is_correct: true,
      explanation: "..."
    }
  ]
}
```

## Teknologi yang Digunakan

- **Backend**: Laravel 11 (PHP)
- **Frontend**: Vanilla JavaScript (Fetch API)
- **Database**: MySQL/PostgreSQL
- **Styling**: Tailwind CSS
- **AJAX**: Fetch API (native JavaScript)

## Keamanan

- CSRF Protection pada semua form
- Authorization check (user hanya bisa akses session sendiri)
- Input validation pada semua endpoint
- Soft deletes untuk data integrity

## Catatan Tambahan

- Untuk Speaking/Writing: jawaban disimpan tapi tidak auto-grade (butuh manual review atau AI grading)
- Timer bersifat opsional dan dapat diaktifkan/dimatikan
- Progress bar menunjukkan progress latihan secara real-time
- Responsive design untuk mobile dan desktop
