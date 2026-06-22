# Laporan Pasca Simulasi (FR-3.6.2)

## Overview
Implementasi laporan komprehensif hasil simulasi TOEFL dengan visualisasi data interaktif menggunakan Chart.js dan ekspor PDF.

## Fitur yang Diimplementasikan

### 1. Header Informasi
- Tanggal dan waktu simulasi
- Mode simulasi (Full Test, Section-specific)
- Durasi pengerjaan (format jam:menit)
- Skor total (0-120)

### 2. Ringkasan Skor - Bar Chart
- Visualisasi skor per section (Reading, Listening, Speaking, Writing)
- Scale 0-30 per section
- Color-coded untuk setiap section

### 3. Perbandingan Trend - Line Chart
- Grafik garis perkembangan skor
- Maksimal 10 simulasi terakhir
- Interactive hover untuk detail skor

### 4. Analisis Micro-Skills - Radar Chart
- 8+ skills yang dianalisis:
  - Grammar
  - Vocabulary
  - Reading Comprehension
  - Listening Comprehension
  - Pronunciation
  - Fluency
  - Organization
  - Topic Development
- Scale 0-100

### 5. Analisis Waktu - Stacked Bar Chart
- Perbandingan waktu allocated vs actual
- Per section
- Dalam satuan menit

### 6. Top 3 Kesalahan Umum
- Kategori kesalahan
- Deskripsi detail
- Frekuensi terjadinya

### 7. Rekomendasi Studi
- 5 item rekomendasi spesifik
- Berdasarkan analisis AI
- Actionable items

### 8. Detail Per Soal - Expandable
- Accordion-style expansion
- Informasi lengkap per soal:
  - Pertanyaan
  - Jawaban user
  - Jawaban benar (jika salah)
  - Penjelasan
  - AI Feedback dengan highlights
  - Waktu pengerjaan

### 9. Export PDF
- Menggunakan DomPDF library
- Layout khusus untuk PDF
- Download langsung

## File yang Dibuat

### Database
```
database/migrations/2024_01_07_000001_create_simulation_reports_tables.php
```

### Models
```
app/Models/Simulation.php
app/Models/SimulationAnswer.php
```

### Controller
```
app/Http/Controllers/SimulationReportController.php
```

### Views
```
resources/views/reports/simulation-detail.blade.php    (Halaman utama dengan charts)
resources/views/reports/simulation-report-pdf.blade.php (Template PDF)
```

### Routes
Ditambahkan ke `routes/web.php`:
- GET `/simulations/{id}/report` - Tampilkan laporan
- GET `/simulations/{id}/report/export` - Export PDF

## Instalasi

### 1. Install DomPDF
```bash
composer require barryvdh/laravel-dompdf
```

### 2. Jalankan Migration
```bash
php artisan migrate
```

### 3. Publish DomPDF Config (Optional)
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

## Cara Penggunaan

### Menampilkan Laporan
```php
// Redirect ke halaman laporan
return redirect()->route('simulations.report.show', $simulationId);
```

### Export PDF
```html
<a href="{{ route('simulations.report.export', $simulation->id) }}">
    Export PDF
</a>
```

## Struktur Data

### Simulation Model
```json
{
    "total_score": 95,
    "reading_score": 25,
    "listening_score": 27,
    "speaking_score": 22,
    "writing_score": 21,
    "micro_skills": {
        "grammar": 75,
        "vocabulary": 80,
        "reading_comp": 85,
        "listening_comp": 82,
        "pronunciation": 70,
        "fluency": 72,
        "organization": 68,
        "development": 71
    },
    "time_analysis": [
        {"section": "Reading", "allocated": 54, "actual": 50},
        {"section": "Listening", "allocated": 41, "actual": 41},
        {"section": "Speaking", "allocated": 17, "actual": 15},
        {"section": "Writing", "allocated": 50, "actual": 48}
    ],
    "common_errors": [
        {"type": "Subject-Verb Agreement", "count": 5, "desc": "Kesepakatan subjek-kata kerja"},
        {"type": "Tense Consistency", "count": 3, "desc": "Konsistensi tense"},
        {"type": "Article Usage", "count": 4, "desc": "Penggunaan a/an/the"}
    ],
    "recommendations": [
        "Review tenses dalam bahasa Inggris",
        "Latihan subject-verb agreement",
        "Perbanyak membaca artikel akademik",
        "Practice speaking dengan timer",
        "Pelajari struktur esai TOEFL"
    ]
}
```

### SimulationAnswer Model
```json
{
    "question_number": 1,
    "section": "Reading",
    "question_text": "...",
    "user_answer": "B",
    "correct_answer": "C",
    "is_correct": false,
    "explanation": "Jawaban yang benar adalah C karena...",
    "ai_feedback": {
        "feedback": "Good attempt, but...",
        "highlights": ["text segment 1", "text segment 2"]
    },
    "time_spent_seconds": 120
}
```

## Teknologi yang Digunakan

- **Chart.js v4.4.0** - Via CDN (tanpa npm build)
- **Laravel Blade** - Server-side rendering
- **DomPDF** - PDF generation
- **Tailwind CSS** - Styling (asumsi sudah terinstall)
- **Eloquent ORM** - Database queries dengan eager loading

## Optimasi Performa

1. **Eager Loading**: `Simulation::with('answers')` untuk mengurangi N+1 query
2. **CDN Charts**: Menggunakan Chart.js via CDN untuk mengurangi bundle size
3. **Lazy Loading Details**: Detail soal menggunakan `<details>` element
4. **Efficient Queries**: Limit 10 untuk trend chart

## Browser Support

- Chrome/Edge (terbaru)
- Firefox (terbaru)
- Safari (terbaru)
- Mobile browsers (responsive)

## Catatan Penting

1. Pastikan koneksi internet tersedia untuk memuat Chart.js dari CDN
2. Untuk production, pertimbangkan untuk download Chart.js dan host lokal
3. PDF export tidak menyertakan charts (keterbatasan DomPDF), hanya data tabel
4. Untuk charts di PDF, pertimbangkan menggunakan Snappy (wkhtmltopdf) jika diperlukan

## Testing

Seed data testing dapat ditambahkan melalui seeder:

```php
// database/seeders/SimulationSeeder.php
Simulation::create([
    'user_id' => 1,
    'mode' => 'Full Test',
    'total_score' => 95,
    'reading_score' => 25,
    'listening_score' => 27,
    'speaking_score' => 22,
    'writing_score' => 21,
    'micro_skills' => [...],
    'time_analysis' => [...],
    'common_errors' => [...],
    'recommendations' => [...],
    'duration_seconds' => 7200,
]);
```

Akses laporan: `/simulations/1/report`
