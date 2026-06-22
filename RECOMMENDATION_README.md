# Performance-Based Recommendations (FR-3.2.4, FR-3.6.3)

## Overview
Sistem rekomendasi belajar yang dihasilkan secara otomatis berdasarkan performa simulasi TOEFL siswa.

## Fitur Utama

### 1. Analisis Gap Skor
- Menghitung selisih antara skor saat ini dan target
- Menentukan required daily improvement
- Rekomendasi intensifikasi jika diperlukan

### 2. Identifikasi 3 Micro-Skills Terlemah
**Reading:**
- main_idea (Menemukan Ide Utama)
- detail_info (Informasi Detail)
- inference (Kesimpulan/Inferensi)
- vocabulary (Kosa Kata dalam Konteks)
- reference (Referensi Pronoun)
- sentence_simplification (Penyederhanaan Kalimat)
- insert_text (Menyisipkan Teks)
- summary (Ringkasan)

**Listening:**
- gist_content (Pemahaman Isi Utama)
- detail_info (Informasi Detail)
- inference (Kesimpulan)
- attitude_speaker (Sikap Pembicara)
- organization (Organisasi Informasi)
- connecting_content (Menghubungkan Konten)
- gist_purpose (Tujuan Pembicaraan)

**Structure:**
- subject_verb_agreement (Kesesuaian Subjek-Verb)
- verb_forms (Bentuk Verb)
- clauses (Klausa)
- modifiers (Modifier)
- parallel_structure (Struktur Paralel)
- comparison (Perbandingan)
- prepositions (Preposisi)
- negation (Negasi)

**Writing:**
- grammar_usage (Tata Bahasa)
- sentence_structure (Struktur Kalimat)
- organization (Organisasi Esai)
- development (Pengembangan Ide)
- vocabulary_range (Variasi Kosa Kata)
- mechanics (Ejaan & Tanda Baca)

### 3. Tipe Rekomendasi
1. **Module** - Pelajari modul spesifik untuk micro-skill tertentu
2. **Practice** - Latihan targeted dengan soal-soal fokus
3. **Strategy** - Tips dan strategi pengerjaan
4. **Simulation** - Jadwal simulasi berikutnya
5. **Schedule** - Adjustment jadwal belajar

### 4. Impact Scoring
Setiap rekomendasi memiliki impact score yang dihitung dari:
- Bobot micro-skill terhadap skor total
- Tingkat kelemahan (100% - accuracy)
- Rumus: `impact_score = (100 - accuracy) * weight * 100`

### 5. Urgency Factor (1-5)
Berdasarkan sisa waktu hingga ujian:
- **5 (Critical)**: < 7 hari
- **4 (High)**: 7-14 hari
- **3 (Medium)**: 15-30 hari
- **2 (Low)**: 30-60 hari
- **1 (Very Low)**: > 60 hari

### 6. Max 5 Rekomendasi per Simulasi
Sistem memilih top 5 rekomendasi berdasarkan:
1. Impact score (tertinggi)
2. Urgency factor (tertinggi)
3. Priority (terendah = prioritas utama)

## File Structure

```
app/
├── Models/
│   └── Recommendation.php (updated)
├── Services/
│   └── PerformanceRecommendationService.php (new)
├── Http/Controllers/
│   └── RecommendationController.php (new)
database/
├── migrations/
│   └── 2024_01_09_000001_update_recommendations_for_performance_analysis.php (new)
resources/
├── views/
│   └── recommendations/
│       └── index.blade.php (new)
routes/
├── student-dashboard.php (updated)
└── web.php (updated)
```

## Database Schema

### Tabel: recommendations (updated)
```sql
- id
- user_id (foreign key)
- simulation_id (foreign key, nullable)
- type (enum: module, practice, simulation, strategy, schedule)
- category (string: reading, listening, structure, writing, time_management, general)
- micro_skill (string, nullable)
- title (string)
- reason (text)
- action_plan (text)
- resource_id (nullable)
- priority (integer)
- impact_score (integer, default 0)
- urgency_factor (integer, 1-5, default 1)
- metadata (json)
- is_read (boolean, default false)
- generated_at (timestamp)
- created_at, updated_at
```

## Cara Penggunaan

### 1. Generate Rekomendasi Otomatis setelah Simulasi
```php
use App\Services\PerformanceRecommendationService;

$service = app(PerformanceRecommendationService::class);

$userProfile = [
    'target_score' => 550,
    'test_date' => '2024-03-15',
];

$recommendations = $service->generateFromSimulation($simulation, $userProfile);
```

### 2. Get Rekomendasi untuk User
```php
// Semua rekomendasi
$recommendations = $service->getUserRecommendations($userId, limit: 5);

// Hanya yang belum dibaca
$unread = $service->getUserRecommendations($userId, unreadOnly: true);

// Filter by category
$reading = $service->getUserRecommendations($userId, category: 'reading');
```

### 3. API Endpoints

**GET /recommendations**
- View halaman rekomendasi
- Query params: `category`, `unread`, `limit`

**POST /api/recommendations/generate-latest**
- Generate dari simulasi terbaru
- Returns JSON dengan daftar rekomendasi

**POST /api/recommendations/{id}/read**
- Mark rekomendasi sebagai sudah dibaca

**POST /api/recommendations/mark-all-read**
- Mark semua rekomendasi sebagai sudah dibaca

**GET /api/recommendations**
- API endpoint untuk AJAX calls
- Returns JSON array

### 4. Routes

```php
// Web routes (authenticated)
Route::get('/recommendations', [RecommendationController::class, 'index'])
    ->name('recommendations.index');

// API routes
Route::post('/api/recommendations/generate-latest', [RecommendationController::class, 'generateFromLatest'])
    ->name('api.recommendations.generate-latest');

Route::post('/api/recommendations/{id}/read', [RecommendationController::class, 'markAsRead'])
    ->name('api.recommendations.mark-read');

Route::post('/api/recommendations/mark-all-read', [RecommendationController::class, 'markAllAsRead'])
    ->name('api.recommendations.mark-all-read');

Route::get('/api/recommendations', [RecommendationController::class, 'apiGet'])
    ->name('api.recommendations.get');
```

## Algoritma

### Flow Generate Rekomendasi:

```
1. Extract data dari simulasi
   - micro_skills performance
   - section scores
   - duration

2. Hitung urgency factor dari test_date

3. Analisis gap skor
   - Jika gap > 0, generate schedule recommendation

4. Identifikasi 3 weakest skills
   - Sort by impact = (100 - accuracy) * weight
   - Take top 3

5. Generate recommendations untuk setiap weak skill
   - Module recommendation (always)
   - Practice recommendation (if accuracy < 60%)
   - Strategy recommendation (if accuracy 60-75%)

6. Add strategic recommendations
   - Time management (if too fast)
   - Next simulation schedule

7. Sort by impact_score DESC, urgency_factor DESC
   - Take top 5

8. Save to database
```

### Weight Distribution:

**Reading:**
- detail_info: 25%
- main_idea: 20%
- inference: 15%
- vocabulary: 15%
- reference: 8%
- sentence_simplification: 7%
- insert_text: 5%
- summary: 5%

**Listening:**
- detail_info: 28%
- gist_content: 18%
- inference: 15%
- attitude_speaker: 12%
- organization: 10%
- connecting_content: 10%
- gist_purpose: 7%

**Structure:**
- subject_verb_agreement: 18%
- verb_forms: 18%
- clauses: 15%
- modifiers: 12%
- parallel_structure: 12%
- comparison: 10%
- prepositions: 8%
- negation: 7%

**Writing:**
- grammar_usage: 25%
- sentence_structure: 20%
- organization: 18%
- development: 17%
- vocabulary_range: 12%
- mechanics: 8%

## Testing

### Manual Testing:
```bash
# 1. Jalankan simulasi
# 2. Kunjungi /recommendations
# 3. Klik "Generate dari Simulasi Terbaru"
# 4. Verifikasi rekomendasi muncul dengan:
#    - Title yang relevan
#    - Reason yang jelas
#    - Action plan yang konkret
#    - Urgency badge yang sesuai
#    - Impact score yang masuk akal
```

### Unit Test Example:
```php
public function test_generate_recommendations_from_simulation()
{
    $user = User::factory()->create();
    $simulation = Simulation::factory()->create([
        'user_id' => $user->id,
        'total_score' => 450,
        'micro_skills' => [
            'reading' => [
                'detail_info' => ['correct' => 5, 'total' => 15], // 33%
                'main_idea' => ['correct' => 8, 'total' => 10],   // 80%
            ],
        ],
    ]);
    
    $service = new PerformanceRecommendationService();
    $recommendations = $service->generateFromSimulation($simulation, [
        'target_score' => 550,
        'test_date' => now()->addDays(30),
    ]);
    
    $this->assertCount(5, $recommendations);
    $this->assertEquals('detail_info', $recommendations[0]->micro_skill);
}
```

## Maintenance

### Clear Old Recommendations:
```php
$service = app(PerformanceRecommendationService::class);
$deleted = $service->clearOldRecommendations($userId, daysOld: 30);
```

### Scheduled Task (Optional):
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        // Clear old read recommendations monthly
        Recommendation::where('is_read', true)
                     ->where('generated_at', '<', now()->subMonths(2))
                     ->delete();
    })->monthly();
}
```

## Integration Points

1. **Simulation Results** - Auto-generate setelah simulasi selesai
2. **Student Dashboard** - Show latest recommendations widget
3. **Study Plan** - Recommendations dapat di-link ke study plan tasks
4. **Notifications** - Send notification when high-urgency recommendation created

## Future Enhancements

- [ ] Link recommendations ke modul/latihan spesifik (resource_id)
- [ ] Track completion rate per recommendation
- [ ] A/B testing untuk recommendation templates
- [ ] Machine learning untuk improve accuracy prediction
- [ ] Personalized recommendation based on learning style
