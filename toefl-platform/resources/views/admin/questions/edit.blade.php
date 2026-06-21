@extends('layouts.app')

@section('title', 'Edit Question')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.questions.index') }}" class="text-blue-600 hover:underline">&larr; Back to Questions</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Question #{{ $question->id }}</h1>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.questions.update', $question) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                    <select name="section" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($sections as $value => $label)
                            <option value="{{ $value }}" {{ old('section', $question->section) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Question Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Type *</label>
                    <select name="question_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($questionTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('question_type', $question->question_type) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Difficulty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty (1-5) *</label>
                    <select name="difficulty" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($difficulties as $level)
                            <option value="{{ $level }}" {{ old('difficulty', $question->difficulty) == $level ? 'selected' : '' }}>
                                Level {{ $level }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Source -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Source *</label>
                    <select name="source" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($sources as $value => $label)
                            <option value="{{ $value }}" {{ old('source', $question->source) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $question->status) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Question Content -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Question Content</h2>
            
            <!-- Question Text -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Question Text *</label>
                <textarea name="question_text" rows="4" required 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('question_text', $question->question_text) }}</textarea>
            </div>

            <!-- Passage Text -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Passage Text (optional)</label>
                <textarea name="passage_text" rows="4" 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('passage_text', $question->passage_text) }}</textarea>
            </div>

            <!-- Media URLs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Audio URL (optional)</label>
                    <input type="text" name="audio_url" value="{{ old('audio_url', $question->audio_url) }}" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image URL (optional)</label>
                    <input type="text" name="image_url" value="{{ old('image_url', $question->image_url) }}" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Explanation -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Explanation (optional)</label>
                <textarea name="explanation" rows="3" 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('explanation', $question->explanation) }}</textarea>
            </div>

            <!-- Correct Answer -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer (optional)</label>
                <input type="text" name="correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Time & Word Limits -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Time & Word Limits</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preparation Time (sec)</label>
                    <input type="number" name="preparation_time" value="{{ old('preparation_time', $question->preparation_time) }}" min="0" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Response Time (sec)</label>
                    <input type="number" name="response_time" value="{{ old('response_time', $question->response_time) }}" min="0" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Word Limit Min</label>
                    <input type="number" name="word_limit_min" value="{{ old('word_limit_min', $question->word_limit_min) }}" min="0" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Word Limit Max</label>
                    <input type="number" name="word_limit_max" value="{{ old('word_limit_max', $question->word_limit_max) }}" min="0" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Question Options (for Multiple Choice) -->
        <div class="bg-white rounded-lg shadow p-6" id="options-section">
            <h2 class="text-lg font-semibold mb-4">Answer Options (for Multiple Choice)</h2>
            <div id="options-container" class="space-y-3">
                @php $optionIndex = 0; @endphp
                @forelse($question->options as $option)
                    <div class="option-row flex gap-2 items-start">
                        <input type="hidden" name="options[{{ $optionIndex }}][id]" value="{{ $option->id }}">
                        <input type="checkbox" name="options[{{ $optionIndex }}][is_correct]" {{ $option->is_correct ? 'checked' : '' }} 
                               class="mt-2" title="Is Correct">
                        <input type="text" name="options[{{ $optionIndex }}][option_text]" value="{{ $option->option_text }}" 
                               placeholder="Option text" required
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <input type="number" name="options[{{ $optionIndex }}][order_index]" value="{{ $option->order_index }}" 
                               placeholder="Order" min="0"
                               class="w-20 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <label class="mt-2 text-sm text-gray-600">
                            <input type="checkbox" name="options[{{ $optionIndex }}][_delete]" value="1" class="mr-1"> Delete
                        </label>
                    </div>
                    @php $optionIndex++; @endphp
                @empty
                    <div class="option-row flex gap-2 items-start">
                        <input type="text" name="options[0][option_text]" placeholder="Option A" 
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <input type="checkbox" name="options[0][is_correct]" class="mt-2" title="Is Correct">
                        <input type="number" name="options[0][order_index]" value="0" min="0"
                               class="w-20 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="this.parentElement.remove()" class="mt-2 text-red-600 hover:text-red-900">✕</button>
                    </div>
                @endforelse
            </div>
            <button type="button" onclick="addOption()" class="mt-3 text-blue-600 hover:text-blue-900">+ Add Option</button>
        </div>

        <!-- Micro-Skills Tagging -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Micro-Skills (1-3 tags required) *</h2>
            <select name="skill_ids[]" multiple required size="5" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                @foreach($microSkills as $skill)
                    <option value="{{ $skill->id }}" {{ $question->skills->contains($skill->id) ? 'selected' : '' }}>
                        {{ $skill->name }} ({{ ucfirst($skill->section) }})
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-2">Hold Ctrl/Cmd to select 1-3 skills. Each question must have at least 1 and maximum 3 micro-skill tags.</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                Update Question
            </button>
            <a href="{{ route('admin.questions.index') }}" 
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
let optionIndex = {{ $optionIndex }};

function addOption() {
    const container = document.getElementById('options-container');
    const div = document.createElement('div');
    div.className = 'option-row flex gap-2 items-start';
    div.innerHTML = `
        <input type="checkbox" name="options[${optionIndex}][is_correct]" class="mt-2" title="Is Correct">
        <input type="text" name="options[${optionIndex}][option_text]" placeholder="Option text" required
               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
        <input type="number" name="options[${optionIndex}][order_index]" value="${optionIndex}" min="0"
               class="w-20 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
        <button type="button" onclick="this.parentElement.remove()" class="mt-2 text-red-600 hover:text-red-900">✕</button>
    `;
    container.appendChild(div);
    optionIndex++;
}
</script>
@endsection
