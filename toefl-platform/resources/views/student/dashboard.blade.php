@extends('layouts.app')

@section('title', 'Dasbor Siswa')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Halo, {{ Auth::user()->name }}!</h1>
        <p class="text-gray-500 mt-1">Siap untuk meningkatkan skor TOEFL kamu hari ini?</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Row 1: Summary Cards (Full Width) -->
        <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
            @include('components.stats-card', [
                'icon' => '⏱️',
                'label' => 'Waktu Belajar',
                'value' => $summary['study_time'],
                'subtext' => 'Hari Ini'
            ])
            @include('components.stats-card', [
                'icon' => '📝',
                'label' => 'Soal Dikerjakan',
                'value' => $summary['questions_solved'],
                'subtext' => 'Butir'
            ])
            @include('components.stats-card', [
                'icon' => '🎓',
                'label' => 'Simulasi Diikuti',
                'value' => $summary['simulations_taken'],
                'subtext' => 'Sesi'
            ])
        </div>

        <!-- Row 2: Study Plan (2/3 width) -->
        @if($studyPlan)
            <div class="lg:col-span-2">
                @include('components.study-plan-card', ['plan' => $studyPlan])
            </div>
        @endif

        <!-- Row 2: Badges & Streak (1/3 width) -->
        <div class="lg:col-span-1">
            @include('components.badges-card', ['streak' => $streak, 'badges' => $badges])
        </div>

        <!-- Row 3: Last Score (1/3 width) -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Skor Terakhir</h3>
            @if($lastSimulation)
                <div class="flex items-end gap-3">
                    <span class="text-4xl font-bold text-gray-900">{{ $scoreValue }}</span>
                    <span class="mb-1 text-sm font-medium {{ $trend === 'up' ? 'text-green-500' : ($trend === 'down' ? 'text-red-500' : 'text-gray-400') }}">
                        @if($trend === 'up')
                            ▲ Naik
                        @elseif($trend === 'down')
                            ▼ Turun
                        @else
                            ─ Stabil
                        @endif
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Terakhir: {{ \Carbon\Carbon::parse($lastSimulation->completed_at)->format('d M Y') }}
                </p>
            @else
                <div class="text-center py-4">
                    <span class="text-4xl text-gray-300">📊</span>
                    <p class="text-gray-500 italic mt-2">Belum ada simulasi.</p>
                </div>
            @endif
        </div>

        <!-- Row 3: Recommendations (2/3 width) -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Rekomendasi Untukmu 🤖</h3>
                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full font-medium">AI Analysis</span>
            </div>
            
            @if($recommendations->count() > 0)
                <div class="space-y-3">
                    @foreach($recommendations as $rec)
                        @include('components.recommendation-item', ['item' => $rec])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <span class="text-4xl text-gray-300">✨</span>
                    <p class="text-gray-500 mt-2">Tidak ada rekomendasi baru.</p>
                    <p class="text-xs text-gray-400">Selesaikan latihan untuk mendapatkan rekomendasi!</p>
                </div>
            @endif
        </div>

        <!-- Row 4: Quick Actions (Full width on mobile, 1/3 on desktop) -->
        <div class="lg:col-span-1 flex flex-col gap-3">
            <a href="{{ route('practice.start') ?? '#' }}" 
               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-6 flex flex-col justify-center items-center transition transform hover:scale-105 shadow-lg group"
               aria-label="Mulai Latihan Baru">
                <span class="text-3xl mb-2 group-hover:animate-bounce">✍️</span>
                <span class="font-bold text-lg">Mulai Latihan</span>
                <span class="text-xs text-blue-100 mt-1">Latihan per skill</span>
            </a>
            
            <a href="{{ route('simulation.start') ?? '#' }}" 
               class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl p-6 flex flex-col justify-center items-center transition transform hover:scale-105 shadow-lg group"
               aria-label="Mulai Simulasi TOEFL">
                <span class="text-3xl mb-2 group-hover:animate-bounce">⏱️</span>
                <span class="font-bold text-lg">Mulai Simulasi</span>
                <span class="text-xs text-indigo-100 mt-1">Full test experience</span>
            </a>
            
            @if($studyPlan && $studyPlan->next_task)
                <a href="{{ route('module.resume', $studyPlan->next_task->id) ?? '#' }}" 
                   class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl p-6 flex flex-col justify-center items-center transition transform hover:scale-105 shadow-lg group"
                   aria-label="Lanjutkan Modul">
                    <span class="text-3xl mb-2 group-hover:animate-bounce">▶️</span>
                    <span class="font-bold text-lg">Lanjutkan Modul</span>
                    <span class="text-xs text-emerald-100 mt-1 truncate w-full text-center">
                        {{ Str::limit($studyPlan->next_task->title, 25) }}
                    </span>
                </a>
            @else
                <a href="{{ route('study-plan.create') ?? '#' }}" 
                   class="flex-1 bg-gray-500 hover:bg-gray-600 text-white rounded-xl p-6 flex flex-col justify-center items-center transition transform hover:scale-105 shadow-lg group"
                   aria-label="Buat Study Plan">
                    <span class="text-3xl mb-2">📅</span>
                    <span class="font-bold text-lg">Buat Study Plan</span>
                    <span class="text-xs text-gray-200 mt-1">Rencanakan belajarmu</span>
                </a>
            @endif
        </div>

    </div>
</div>

@push('styles')
<style>
    .group:hover .group-hover\:animate-bounce {
        animation: bounce 0.5s infinite alternate;
    }
    @keyframes bounce {
        from { transform: translateY(0); }
        to { transform: translateY(-5px); }
    }
</style>
@endpush
@endsection
