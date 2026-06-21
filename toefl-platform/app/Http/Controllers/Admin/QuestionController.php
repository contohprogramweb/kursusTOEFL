<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\MicroSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions with search and filters.
     */
    public function index(Request $request)
    {
        $query = Question::with(['options', 'skills', 'creator'])
            ->orderBy('created_at', 'desc');

        // Search (full-text or LIKE)
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by section
        if ($request->filled('section')) {
            $query->bySection($request->section);
        }

        // Filter by difficulty (1-5)
        if ($request->filled('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->bySource($request->source);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by micro-skill(s)
        if ($request->filled('skill_ids')) {
            $skillIds = is_array($request->skill_ids) 
                ? $request->skill_ids 
                : explode(',', $request->skill_ids);
            $query->withSkills($skillIds);
        }

        // Pagination: 20 per halaman
        $questions = $query->paginate(20);

        // Load filter options
        $sections = Question::SECTIONS;
        $difficulties = range(1, 5);
        $sources = Question::SOURCES;
        $statuses = Question::STATUSES;
        $questionTypes = Question::QUESTION_TYPES;
        $microSkills = MicroSkill::orderBy('name')->get();

        return view('admin.questions.index', compact(
            'questions',
            'sections',
            'difficulties',
            'sources',
            'statuses',
            'questionTypes',
            'microSkills'
        ));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create()
    {
        $sections = Question::SECTIONS;
        $questionTypes = Question::QUESTION_TYPES;
        $sources = Question::SOURCES;
        $statuses = Question::STATUSES;
        $difficulties = range(1, 5);
        $microSkills = MicroSkill::orderBy('section')->orderBy('name')->get();

        return view('admin.questions.create', compact(
            'sections',
            'questionTypes',
            'sources',
            'statuses',
            'difficulties',
            'microSkills'
        ));
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => ['required', Rule::in(array_keys(Question::SECTIONS))],
            'question_type' => ['required', Rule::in(array_keys(Question::QUESTION_TYPES))],
            'question_text' => 'required|string',
            'passage_text' => 'nullable|string',
            'audio_url' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:500',
            'difficulty' => 'required|integer|min:1|max:5',
            'explanation' => 'nullable|string',
            'source' => ['required', Rule::in(array_keys(Question::SOURCES))],
            'correct_answer' => 'nullable|string',
            'preparation_time' => 'nullable|integer|min:0',
            'response_time' => 'nullable|integer|min:0',
            'word_limit_min' => 'nullable|integer|min:0',
            'word_limit_max' => 'nullable|integer|min:0|gte:word_limit_min',
            'status' => ['required', Rule::in(array_keys(Question::STATUSES))],
            
            // Question options (for multiple choice)
            'options' => 'nullable|array',
            'options.*.option_text' => 'required_with:options|string',
            'options.*.is_correct' => 'boolean',
            'options.*.order_index' => 'integer|min:0',
            
            // Micro-skills (minimum 1, maximum 3)
            'skill_ids' => 'required|array|min:1|max:3',
            'skill_ids.*' => 'exists:micro_skills,id',
        ]);

        DB::beginTransaction();
        try {
            // Create question
            $validated['created_by'] = Auth::id();
            $question = Question::create($validated);

            // Create options if provided
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $index => $optionData) {
                    $optionData['order_index'] = $optionData['order_index'] ?? $index;
                    $question->options()->create($optionData);
                }
            }

            // Attach micro-skills with weight
            if (!empty($validated['skill_ids'])) {
                $weight = round(1 / count($validated['skill_ids']), 2);
                $skillAttachments = [];
                foreach ($validated['skill_ids'] as $skillId) {
                    $skillAttachments[$skillId] = ['weight' => $weight];
                }
                $question->skills()->attach($skillAttachments);
            }

            DB::commit();

            return redirect()->route('admin.questions.edit', $question)
                ->with('success', 'Question created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create question: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified question.
     */
    public function show(Question $question)
    {
        $question->load(['options', 'skills', 'creator']);
        return view('admin.questions.show', compact('question'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Question $question)
    {
        $question->load(['options', 'skills']);
        
        $sections = Question::SECTIONS;
        $questionTypes = Question::QUESTION_TYPES;
        $sources = Question::SOURCES;
        $statuses = Question::STATUSES;
        $difficulties = range(1, 5);
        $microSkills = MicroSkill::orderBy('section')->orderBy('name')->get();

        return view('admin.questions.edit', compact(
            'question',
            'sections',
            'questionTypes',
            'sources',
            'statuses',
            'difficulties',
            'microSkills'
        ));
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'section' => ['required', Rule::in(array_keys(Question::SECTIONS))],
            'question_type' => ['required', Rule::in(array_keys(Question::QUESTION_TYPES))],
            'question_text' => 'required|string',
            'passage_text' => 'nullable|string',
            'audio_url' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|max:500',
            'difficulty' => 'required|integer|min:1|max:5',
            'explanation' => 'nullable|string',
            'source' => ['required', Rule::in(array_keys(Question::SOURCES))],
            'correct_answer' => 'nullable|string',
            'preparation_time' => 'nullable|integer|min:0',
            'response_time' => 'nullable|integer|min:0',
            'word_limit_min' => 'nullable|integer|min:0',
            'word_limit_max' => 'nullable|integer|min:0|gte:word_limit_min',
            'status' => ['required', Rule::in(array_keys(Question::STATUSES))],
            
            // Question options
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|exists:question_options,id',
            'options.*.option_text' => 'required_with:options|string',
            'options.*.is_correct' => 'boolean',
            'options.*.order_index' => 'integer|min:0',
            'options.*._delete' => 'boolean',
            
            // Micro-skills (minimum 1, maximum 3)
            'skill_ids' => 'required|array|min:1|max:3',
            'skill_ids.*' => 'exists:micro_skills,id',
        ]);

        DB::beginTransaction();
        try {
            // Update question
            $question->update($validated);

            // Update options
            if (isset($validated['options'])) {
                foreach ($validated['options'] as $optionData) {
                    if (!empty($optionData['_delete'])) {
                        // Delete option
                        if (!empty($optionData['id'])) {
                            QuestionOption::find($optionData['id'])->delete();
                        }
                    } elseif (!empty($optionData['id'])) {
                        // Update existing option
                        $existingOption = QuestionOption::find($optionData['id']);
                        $existingOption->update([
                            'option_text' => $optionData['option_text'],
                            'is_correct' => $optionData['is_correct'] ?? false,
                            'order_index' => $optionData['order_index'] ?? 0,
                        ]);
                    } else {
                        // Create new option
                        $question->options()->create([
                            'option_text' => $optionData['option_text'],
                            'is_correct' => $optionData['is_correct'] ?? false,
                            'order_index' => $optionData['order_index'] ?? 0,
                        ]);
                    }
                }
            }

            // Update micro-skills
            if (isset($validated['skill_ids'])) {
                $weight = round(1 / count($validated['skill_ids']), 2);
                $skillAttachments = [];
                foreach ($validated['skill_ids'] as $skillId) {
                    $skillAttachments[$skillId] = ['weight' => $weight];
                }
                $question->skills()->sync($skillAttachments);
            }

            DB::commit();

            return redirect()->route('admin.questions.index')
                ->with('success', 'Question updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update question: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Question $question)
    {
        $question->delete();

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    /**
     * Get questions via AJAX for dynamic loading.
     */
    public function apiIndex(Request $request)
    {
        $query = Question::with(['options', 'skills'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('section')) {
            $query->bySection($request->section);
        }

        if ($request->filled('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        if ($request->filled('source')) {
            $query->bySource($request->source);
        }

        if ($request->filled('skill_ids')) {
            $skillIds = is_array($request->skill_ids) 
                ? $request->skill_ids 
                : explode(',', $request->skill_ids);
            $query->withSkills($skillIds);
        }

        $questions = $query->paginate(20);

        return response()->json($questions);
    }
}
