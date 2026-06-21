@extends('layouts.app')

@section('title', 'Question Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.questions.index') }}" class="text-blue-600 hover:underline">&larr; Back to Questions</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Question #{{ $question->id }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-50 px-6 py-4 border-b">
            <div class="flex justify-between items-center">
                <div class="flex gap-3">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                        {{ $question->section === 'reading' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $question->section === 'listening' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $question->section === 'speaking' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $question->section === 'writing' ? 'bg-purple-100 text-purple-800' : '' }}">
                        {{ $question->section_label }}
                    </span>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                        {{ $question->question_type_label }}
                    </span>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                        {{ $question->status === 'published' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $question->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $question->status === 'archived' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ $question->status_label }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.questions.edit', $question) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        Edit
                    </a>
                    <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition" 
                                onclick="return confirm('Are you sure you want to delete this question?')">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Basic Info Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Difficulty</label>
                    <div class="mt-1 flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $question->difficulty ? 'text-yellow-500' : 'text-gray-300' }}">★</span>
                        @endfor
                        <span class="ml-2 text-sm text-gray-600">Level {{ $question->difficulty }}</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Source</label>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $question->source_label }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Created By</label>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $question->creator->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Created At</label>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $question->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            <!-- Question Text -->
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Question Text</label>
                <div class="bg-gray-50 rounded-lg p-4 border">
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $question->question_text }}</p>
                </div>
            </div>

            <!-- Passage Text -->
            @if($question->passage_text)
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Passage Text</label>
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <p class="text-gray-900 whitespace-pre-wrap">{{ $question->passage_text }}</p>
                    </div>
                </div>
            @endif

            <!-- Media -->
            @if($question->audio_url || $question->image_url)
                <div class="grid grid-cols-2 gap-4">
                    @if($question->audio_url)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Audio</label>
                            <audio controls class="w-full">
                                <source src="{{ $question->audio_url }}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    @endif
                    @if($question->image_url)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Image</label>
                            <img src="{{ $question->image_url }}" alt="Question image" class="rounded-lg border max-h-48">
                        </div>
                    @endif
                </div>
            @endif

            <!-- Answer Options -->
            @if($question->options->count() > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Answer Options</label>
                    <div class="space-y-2">
                        @foreach($question->options as $option)
                            <div class="flex items-center gap-3 p-3 rounded-lg border {{ $option->is_correct ? 'bg-green-50 border-green-200' : 'bg-white' }}">
                                <input type="radio" disabled {{ $option->is_correct ? 'checked' : '' }}>
                                <span class="flex-1 text-gray-900">{{ $option->option_text }}</span>
                                @if($option->is_correct)
                                    <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">Correct</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Correct Answer & Explanation -->
            @if($question->correct_answer || $question->explanation)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($question->correct_answer)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Correct Answer</label>
                            <p class="text-gray-900">{{ $question->correct_answer }}</p>
                        </div>
                    @endif
                    @if($question->explanation)
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Explanation</label>
                            <p class="text-gray-900 whitespace-pre-wrap">{{ $question->explanation }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Time & Word Limits -->
            @if($question->preparation_time || $question->response_time || $question->word_limit_min || $question->word_limit_max)
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Time & Word Limits</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @if($question->preparation_time)
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Preparation Time</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $question->preparation_time }}s</p>
                            </div>
                        @endif
                        @if($question->response_time)
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Response Time</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $question->response_time }}s</p>
                            </div>
                        @endif
                        @if($question->word_limit_min || $question->word_limit_max)
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500">Word Limit</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $question->word_limit_min ?? '?' }} - {{ $question->word_limit_max ?? '?' }} words
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Micro-Skills -->
            @if($question->skills->count() > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Micro-Skills ({{ $question->skills->count() }})</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($question->skills as $skill)
                            <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                                {{ $skill->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 border-t">
            <div class="flex justify-between items-center text-sm text-gray-500">
                <span>Last updated: {{ $question->updated_at->diffForHumans() }}</span>
                <a href="{{ route('admin.questions.edit', $question) }}" class="text-blue-600 hover:underline">Edit this question →</a>
            </div>
        </div>
    </div>
</div>
@endsection
