@extends('layouts.app')

@section('title', 'Laporan Simulasi #' . $simulation->id)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- 1. Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border-l-4 border-blue-600">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan Hasil Simulasi</h1>
                <p class="text-gray-500 mt-1">ID: #{{ $simulation->id }} | {{ $simulation->mode }}</p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-blue-600">{{ $simulation->total_score }}</div>
                <p class="text-sm text-gray-500">Total Score (0-120)</p>
                <div class="mt-2 flex gap-3 text-xs">
                    <span class="bg-gray-100 px-2 py-1 rounded">📅 {{ $simulation->completed_at->format('d M Y, H:i') }}</span>
                    <span class="bg-gray-100 px-2 py-1 rounded">⏱️ {{ $simulation->formatted_duration }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Ringkasan Skor - Bar Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">📊 Ringkasan Skor per Section</h3>
            <canvas id="scoreBarChart" height="200"></canvas>
        </div>

        <!-- 3. Perbandingan Trend - Line Chart -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">📈 Trend Skor (Max 10 Terakhir)</h3>
            <canvas id="trendLineChart" height="200"></canvas>
        </div>
    </div>

    <!-- 4. Analisis Micro-Skills - Radar Chart -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">🎯 Analisis Micro-Skills (8 Skills)</h3>
        <div class="flex justify-center">
            <canvas id="microSkillsRadarChart" height="300" width="400"></canvas>
        </div>
    </div>

    <!-- 5. Analisis Waktu - Stacked Bar -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">⏰ Analisis Waktu: Actual vs Allocated (menit)</h3>
        <canvas id="timeAnalysisChart" height="150"></canvas>
    </div>

    <!-- 6. Top 3 Kesalahan Umum -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">⚠️ Top 3 Kesalahan Umum</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($simulation->common_errors ?? [] as $index => $error)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-sm font-bold">{{ $index + 1 }}</span>
                        <span class="font-semibold text-red-700">{{ $error['type'] ?? 'Error' }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $error['desc'] ?? 'Tidak ada deskripsi' }}</p>
                    <p class="text-xs text-red-500 mt-2">Terjadi {{ $error['count'] ?? 0 }} kali</p>
                </div>
            @empty
                <div class="col-span-3 text-center py-4 text-gray-500">Tidak ada data kesalahan.</div>
            @endforelse
        </div>
    </div>

    <!-- 7. Rekomendasi -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">💡 Rekomendasi Studi (5 Item)</h3>
        <ul class="space-y-3">
            @forelse($simulation->recommendations ?? [] as $rec)
                <li class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <span class="text-blue-600 text-lg">✓</span>
                    <span class="text-gray-700">{{ $rec }}</span>
                </li>
            @empty
                <li class="text-center py-4 text-gray-500">Belum ada rekomendasi.</li>
            @endforelse
        </ul>
    </div>

    <!-- 8. Detail Per Soal - Expandable -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">📝 Detail Jawaban Per Soal</h3>
        <div class="space-y-3">
            @foreach($simulation->answers->groupBy('section') as $section => $answers)
                <div class="mb-6">
                    <h4 class="text-md font-bold text-gray-800 mb-3 capitalize border-b pb-2">{{ $section }} Section</h4>
                    @foreach($answers as $answer)
                        <details class="group mb-2 border rounded-lg overflow-hidden">
                            <summary class="flex justify-between items-center p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 transition list-none">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-700">Soal #{{ $answer->question_number }}</span>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $answer->is_correct ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $answer->is_correct ? '✓ Benar' : '✗ Salah' }}
                                    </span>
                                </div>
                                <span class="text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <div class="p-4 border-t">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Pertanyaan</p>
                                        <p class="text-sm text-gray-700">{{ Str::limit($answer->question_text, 200) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Waktu Dikerjakan</p>
                                        <p class="text-sm text-gray-700">{{ $answer->formatted_time_spent }}</p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Jawaban Kamu</p>
                                    <div class="p-3 bg-gray-50 rounded {{ $answer->is_correct ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
                                        <p class="text-sm text-gray-800">{{ $answer->user_answer ?? '-' }}</p>
                                    </div>
                                </div>

                                @if(!$answer->is_correct && $answer->correct_answer)
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Jawaban Benar</p>
                                    <div class="p-3 bg-green-50 rounded border-l-4 border-green-500">
                                        <p class="text-sm text-green-800">{{ $answer->correct_answer }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($answer->explanation)
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">💡 Penjelasan</p>
                                    <div class="p-3 bg-blue-50 rounded">
                                        <p class="text-sm text-blue-800">{{ $answer->explanation }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($answer->ai_feedback)
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">🤖 AI Feedback</p>
                                    <div class="p-3 bg-purple-50 rounded">
                                        @if(isset($answer->ai_feedback['feedback']))
                                            <p class="text-sm text-purple-800 mb-2">{{ $answer->ai_feedback['feedback'] }}</p>
                                        @endif
                                        @if(isset($answer->ai_feedback['highlights']) && is_array($answer->ai_feedback['highlights']))
                                            <div class="text-xs text-purple-600 mt-2">
                                                <strong>Highlights:</strong>
                                                <ul class="list-disc list-inside mt-1">
                                                    @foreach(array_slice($answer->ai_feedback['highlights'], 0, 3) as $highlight)
                                                        <li>{{ is_string($highlight) ? $highlight : ($highlight['message'] ?? '') }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <!-- Export Button -->
    <div class="fixed bottom-6 right-6">
        <a href="{{ route('simulations.report.export', $simulation->id) }}" 
           class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-full shadow-lg flex items-center gap-2 transition transform hover:scale-105">
            <span>📄</span>
            <span class="font-semibold">Export PDF</span>
        </a>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = @json($chartData);
    const simulation = @json($simulation);

    // 2. Bar Chart - Ringkasan Skor
    new Chart(document.getElementById('scoreBarChart'), {
        type: 'bar',
        data: {
            labels: ['Reading', 'Listening', 'Speaking', 'Writing'],
            datasets: [{
                label: 'Score',
                data: [chartData.scores.reading, chartData.scores.listening, chartData.scores.speaking, chartData.scores.writing],
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'],
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 30 }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 3. Line Chart - Trend
    new Chart(document.getElementById('trendLineChart'), {
        type: 'line',
        data: {
            labels: chartData.trend_labels.length > 0 ? chartData.trend_labels : ['Belum ada data'],
            datasets: [{
                label: 'Total Score',
                data: chartData.trend_data.length > 0 ? chartData.trend_data : [0],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: false, min: 0, max: 120 }
            }
        }
    });

    // 4. Radar Chart - Micro Skills
    const microSkillsLabels = Object.keys(chartData.micro_skills);
    const microSkillsData = Object.values(chartData.micro_skills);
    
    new Chart(document.getElementById('microSkillsRadarChart'), {
        type: 'radar',
        data: {
            labels: microSkillsLabels.length > 0 ? microSkillsLabels : ['Grammar', 'Vocabulary', 'Reading Comp', 'Listening Comp', 'Pronunciation', 'Fluency', 'Organization', 'Development'],
            datasets: [{
                label: 'Micro Skills',
                data: microSkillsData.length > 0 ? microSkillsData : [70, 65, 75, 80, 60, 70, 65, 70],
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: '#3B82F6',
                pointBackgroundColor: '#3B82F6',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { display: true },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            }
        }
    });

    // 5. Stacked Bar - Time Analysis
    const timeData = chartData.time_analysis.length > 0 ? chartData.time_analysis : [
        {section: 'Reading', allocated: 54, actual: 50},
        {section: 'Listening', allocated: 41, actual: 41},
        {section: 'Speaking', allocated: 17, actual: 15},
        {section: 'Writing', allocated: 50, actual: 48}
    ];
    
    new Chart(document.getElementById('timeAnalysisChart'), {
        type: 'bar',
        data: {
            labels: timeData.map(d => d.section),
            datasets: [
                {
                    label: 'Allocated (min)',
                    data: timeData.map(d => d.allocated),
                    backgroundColor: '#9CA3AF',
                    borderRadius: 4,
                },
                {
                    label: 'Actual (min)',
                    data: timeData.map(d => d.actual),
                    backgroundColor: '#3B82F6',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true }
            }
        }
    });
});
</script>
@endsection
