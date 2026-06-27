<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Simulasi #{{ $simulation->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { border-bottom: 3px solid #2563EB; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1E40AF; }
        .score-box { text-align: right; }
        .total-score { font-size: 32px; font-weight: bold; color: #2563EB; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .info-card { background: #F3F4F6; padding: 15px; border-radius: 8px; }
        .section-title { background: #DBEAFE; padding: 10px; margin: 20px 0 10px; font-weight: bold; color: #1E40AF; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #D1D5DB; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #F3F4F6; }
        .correct { color: #059669; font-weight: bold; }
        .incorrect { color: #DC2626; font-weight: bold; }
        .recommendation { background: #EFF6FF; padding: 8px; margin: 5px 0; border-left: 3px solid #2563EB; }
        .error-box { background: #FEF2F2; border: 1px solid #FECACA; padding: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Hasil Simulasi TOEFL</h1>
        <p>ID: #{{ $simulation->id }} | Mode: {{ $simulation->mode }}</p>
        <div class="score-box">
            <div class="total-score">{{ $simulation->total_score }} / 120</div>
            <p>{{ $simulation->completed_at->format('d M Y, H:i') }} | Durasi: {{ $simulation->formatted_duration }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <strong>📊 Skor per Section:</strong><br>
            Reading: {{ $simulation->reading_score }}/30<br>
            Listening: {{ $simulation->listening_score }}/30<br>
            Speaking: {{ $simulation->speaking_score }}/30<br>
            Writing: {{ $simulation->writing_score }}/30
        </div>
        <div class="info-card">
            <strong>⏰ Analisis Waktu:</strong><br>
            @foreach(($simulation->time_analysis ?? []) as $time)
                {{ $time['section'] }}: {{ $time['actual'] }}/{{ $time['allocated'] }} menit<br>
            @endforeach
        </div>
    </div>

    <div class="section-title">⚠️ Top 3 Kesalahan Umum</div>
    @forelse($simulation->common_errors ?? [] as $index => $error)
        <div class="error-box">
            <strong>{{ $index + 1 }}. {{ $error['type'] ?? 'Error' }}</strong>: 
            {{ $error['desc'] ?? 'N/A' }} ({{ $error['count'] ?? 0 }} kali)
        </div>
    @empty
        <p>Tidak ada data kesalahan.</p>
    @endforelse

    <div class="section-title">💡 Rekomendasi Studi</div>
    @forelse($simulation->recommendations ?? [] as $rec)
        <div class="recommendation">✓ {{ $rec }}</div>
    @empty
        <p>Belum ada rekomendasi.</p>
    @endforelse

    <div class="section-title">📝 Detail Jawaban</div>
    @foreach($answers as $section => $sectionAnswers)
        <h3 style="margin-top: 20px;">{{ strtoupper($section) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Status</th>
                    <th>Jawaban Kamu</th>
                    <th>Jawaban Benar</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sectionAnswers as $answer)
                    <tr>
                        <td>{{ $answer->question_number }}</td>
                        <td class="{{ $answer->is_correct ? 'correct' : 'incorrect' }}">
                            {{ $answer->is_correct ? '✓ Benar' : '✗ Salah' }}
                        </td>
                        <td>{{ Str::limit($answer->user_answer ?? '-', 50) }}</td>
                        <td>{{ Str::limit($answer->correct_answer ?? '-', 50) }}</td>
                        <td>{{ $answer->formatted_time_spent }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #6B7280;">
        <p>Laporan ini dihasilkan secara otomatis oleh sistem AI Grading.</p>
        <p>Untuk evaluasi resmi, gunakan layanan ETS.</p>
    </div>
</body>
</html>
