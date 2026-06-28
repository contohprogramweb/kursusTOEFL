# TOEFL Learning Platform

Aplikasi kursus TOEFL berbasis web dengan fitur pembelajaran interaktif, simulasi ujian, penilaian AI, dan gamifikasi.

## 🚀 Fitur Utama

- **Pembelajaran Interaktif**: Modul belajar dengan tracking progress
- **Latihan Soal (Exercise)**: Practice session dengan berbagai tipe soal TOEFL
- **Simulasi Ujian**: Simulasi ujian TOEFL lengkap dengan timer dan auto-save
- **AI Grading**: Penilaian otomatis menggunakan AI untuk Speaking & Writing
- **Gamification**: Badge, streak, leaderboard untuk motivasi belajar
- **Forum Diskusi**: Forum tanya jawab antara siswa dan instructor
- **Dashboard Siswa**: Tracking progress, rekomendasi belajar, dan laporan hasil
- **Manajemen Kelas**: Fitur untuk institusi dan kelas belajar
- **Parent Monitoring**: Orang tua dapat memantau progress anak

## 🛠️ Teknologi

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade Templates + Vite
- **Database**: SQLite/MySQL
- **AI Services**: Google Cloud AI, OpenAI, Claude
- **Session & Cache**: Database-driven
- **Queue**: Database queue untuk background jobs

## 📋 Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite/MySQL
- Redis (opsional)

## ⚙️ Instalasi

```bash
# Clone repository
cd toefl-platform

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run build

# Jalankan development server
php artisan serve
```

## 🔑 Konfigurasi Environment

Edit file `.env` untuk konfigurasi:

```env
# Database
DB_CONNECTION=sqlite
# atau DB_CONNECTION=mysql

# AI Services (opsional)
GOOGLE_CLOUD_API_KEY=your_key
OPENAI_API_KEY=your_key
CLAUDE_API_KEY=your_key

# Session & Queue
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## 📁 Struktur Fitur

```
toefl-platform/
├── app/
│   ├── Models/          # Model database (User, Simulation, Exercise, dll)
│   ├── Http/            # Controllers & Requests
│   ├── Services/        # AI services, grading logic
│   └── Jobs/            # Background jobs
├── routes/
│   ├── web.php          # Route definitions
│   ├── admin.php        # Admin routes
│   └── student-dashboard.php
├── database/
│   ├── migrations/      # Database migrations
│   └── seeders/         # Data seeders
└── resources/
    ├── views/           # Blade templates
    └── js/              # Frontend JavaScript
```

## 🎯 Fitur Detail

Lihat dokumentasi lengkap per fitur:

- [Exam Interface](EXAM_INTERFACE_README.md) - Interface ujian simulasi
- [AI Grading](AI_GRADING_README.md) - Sistem penilaian AI
- [Auto Grading](AUTO_GRADING_README.md) - Auto grading untuk listening & reading
- [Exercise Feature](EXERCISE_FEATURE_README.md) - Fitur latihan interaktif
- [Simulation Feature](SIMULATION_FEATURE_README.md) - Simulasi ujian lengkap
- [Simulation Report](SIMULATION_REPORT_README.md) - Laporan hasil simulasi
- [Recommendation System](RECOMMENDATION_README.md) - Rekomendasi belajar personal
- [Student Dashboard](STUDENT_DASHBOARD_README.md) - Dashboard siswa
- [Study Plan](STUDY_PLAN_README.md) - Rencana belajar terstruktur
- [Gamification](GAMIFICATION_README.md) - Sistem gamifikasi
- [AI Transparency](AI_TRANSPARENCY_README.md) - Transparansi penilaian AI

## 👥 Role Pengguna

- **Student**: Akses belajar, latihan, simulasi, forum
- **Instructor**: Moderasi forum, feedback, manajemen kelas
- **Admin**: Manajemen modul, soal, template simulasi, user
- **Parent**: Monitoring progress anak

## 📝 License

MIT License

---

**Catatan**: Pastikan semua migration sudah dijalankan sebelum menggunakan aplikasi. Untuk production, gunakan `npm run build` dan configure queue worker.
