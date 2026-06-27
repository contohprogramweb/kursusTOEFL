@extends('layouts.app')

@section('title', $studyPlan->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $studyPlan->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Dibuat {{ $studyPlan->created_at->format('d M Y') }} 
                    @if($studyPlan->is_ai_generated)
                        <span class="inline-flex items-center ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                            ✨ AI Generated
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('study-plan.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    + Plan Baru
                </a>
                <button onclick="document.getElementById('regenerate-form').submit()" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition text-sm">
                    🔄 Regenerate
                </button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-medium">Progress: {{ $progressPercentage }}%</span>
                <span class="text-gray-500">{{ $studyPlan->completed_tasks }} / {{ $studyPlan->total_tasks }} tugas</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full transition-all" style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-xs text-blue-600 font-medium">Target Skor</p>
                <p class="text-xl font-bold text-blue-800">{{ $studyPlan->target_score }}</p>
            </div>
            <div class="bg-purple-50 p-3 rounded-lg">
                <p class="text-xs text-purple-600 font-medium">Test Date</p>
                <p class="text-lg font-bold text-purple-800">{{ $studyPlan->test_date->format('d M Y') }}</p>
            </div>
            <div class="bg-orange-50 p-3 rounded-lg">
                <p class="text-xs text-orange-600 font-medium">Sisa Hari</p>
                <p class="text-xl font-bold text-orange-800">{{ $studyPlan->days_remaining }}</p>
            </div>
            <div class="bg-green-50 p-3 rounded-lg">
                <p class="text-xs text-green-600 font-medium">Jam/Hari</p>
                <p class="text-xl font-bold text-green-800">{{ $studyPlan->daily_hours }}</p>
            </div>
        </div>

        <!-- AI Notes -->
        @if($studyPlan->ai_notes)
            <div class="mt-4 bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <p class="text-sm text-indigo-800">{{ $studyPlan->ai_notes }}</p>
            </div>
        @endif
    </div>

    <!-- Today's Tasks -->
    @if($todayTasks->count() > 0)
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg shadow-md p-6 mb-6 border-2 border-blue-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">📋 Tugas Hari Ini</h2>
                <form action="{{ route('study-plan.reminder', $studyPlan) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 underline">
                        🔔 Kirim Reminder
                    </button>
                </form>
            </div>
            <div class="space-y-3">
                @foreach($todayTasks as $task)
                    <div class="bg-white rounded-lg p-4 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <form action="{{ route('study-plan.task.complete', $task) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="w-6 h-6 rounded-full border-2 {{ $task->is_completed ? 'bg-green-500 border-green-500' : 'border-gray-300 hover:border-green-500' }} flex items-center justify-center transition">
                                    @if($task->is_completed)
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            <div>
                                <p class="font-medium text-gray-800">{{ $task->title }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ ucfirst($task->type) }} • {{ $task->estimated_minutes }} menit • 
                                    <span class="capitalize">{{ $task->section }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($task->priority <= 3)
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">High Priority</span>
                            @elseif($task->priority <= 6)
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Medium</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Low</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Calendar View -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">📅 Kalender Belajar</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tanggal</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hari</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tugas</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Total Waktu</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasksByDate as $date => $tasks)
                        @php
                            $dateObj = \Carbon\Carbon::parse($date);
                            $isToday = $dateObj->isToday();
                            $totalMinutes = $tasks->sum('estimated_minutes');
                            $completedCount = $tasks->where('is_completed', true)->count();
                            $allCompleted = $completedCount === $tasks->count();
                        @endphp
                        <tr class="border-t {{ $isToday ? 'bg-blue-50' : '' }} {{ $allCompleted ? 'bg-green-50' : '' }}">
                            <td class="px-4 py-3 text-sm {{ $isToday ? 'font-bold text-blue-700' : 'text-gray-700' }}">
                                {{ $dateObj->format('d M Y') }}
                                @if($isToday)
                                    <span class="ml-1 text-xs">(Hari Ini)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $dateObj->locale('id')->dayName }}</td>
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    @foreach($tasks->take(3) as $task)
                                        <div class="flex items-center space-x-2">
                                            <span class="w-2 h-2 rounded-full {{ $task->is_completed ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                            <span class="text-xs text-gray-700 truncate max-w-xs">{{ $task->title }}</span>
                                        </div>
                                    @endforeach
                                    @if($tasks->count() > 3)
                                        <p class="text-xs text-gray-500 italic">+{{ $tasks->count() - 3 }} tugas lainnya</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $totalMinutes }} menit</td>
                            <td class="px-4 py-3">
                                @if($allCompleted)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">✓ Selesai</span>
                                @elseif($completedCount > 0)
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">{{ $completedCount }}/{{ $tasks->count() }}</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Belum mulai</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden Regenerate Form -->
    <form id="regenerate-form" action="{{ route('study-plan.regenerate', $studyPlan) }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="target_score" value="{{ $studyPlan->target_score }}">
        <input type="hidden" name="test_date" value="{{ $studyPlan->test_date->format('Y-m-d') }}">
        <input type="hidden" name="daily_hours" value="{{ $studyPlan->daily_hours }}">
        @foreach($studyPlan->available_days as $day)
            <input type="hidden" name="available_days[]" value="{{ $day }}">
        @endforeach
    </form>
</div>
@endsection
