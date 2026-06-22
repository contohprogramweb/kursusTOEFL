<?php

namespace App\Http\Controllers;

use App\Models\ExerciseSession;
use App\Models\ExerciseHistory;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    /**
     * Display exercise selection page
     */
    public function index()
    {
        return view('exercises.index');
    }

    /**
     * Create a new exercise session
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|in:reading,listening,speaking,writing',
            'total_questions' => 'required|integer|min:1|max:50',
        ]);

        // Get random questions for the selected section
        $questionIds = Question::where('section', $validated['section'])
            ->where('status', 'published')
            ->inRandomOrder()
            ->limit($validated['total_questions'])
            ->pluck('id')
            ->toArray();

        if (empty($questionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No questions available for this section.',
            ], 404);
        }

        // Create exercise session
        $session = ExerciseSession::create([
            'user_id' => Auth::id(),
            'section' => $validated['section'],
            'total_questions' => $validated['total_questions'],
            'question_ids' => $questionIds,
            'user_answers' => [],
            'is_completed' => false,
            'current_question_index' => 0,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
            'redirect' => route('exercises.show', $session),
        ]);
    }

    /**
     * Display exercise session with first question
     */
    public function show(ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this exercise session.');
        }

        return view('exercises.show', compact('session'));
    }

    /**
     * Get current question data (AJAX)
     */
    public function getCurrentQuestion(ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($session->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Session already completed.',
                'completed' => true,
            ]);
        }

        $question = $session->current_question;

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'No more questions.',
                'completed' => true,
            ]);
        }

        // Load options if it's multiple choice
        if ($question->question_type === 'multiple_choice') {
            $question->load('options');
        }

        $currentAnswer = $session->getAnswer($question->id);

        return response()->json([
            'success' => true,
            'question' => [
                'id' => $question->id,
                'section' => $question->section,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'passage_text' => $question->passage_text,
                'audio_url' => $question->audio_url,
                'image_url' => $question->image_url,
                'difficulty' => $question->difficulty,
                'options' => $question->question_type === 'multiple_choice' ? $question->options : null,
                'preparation_time' => $question->preparation_time,
                'response_time' => $question->response_time,
            ],
            'current_index' => $session->current_question_index,
            'total_questions' => $session->total_questions,
            'current_answer' => $currentAnswer,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Save answer for current question (AJAX)
     */
    public function saveAnswer(Request $request, ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'required|string',
        ]);

        $session->saveAnswer($validated['question_id'], $validated['answer']);

        return response()->json([
            'success' => true,
            'message' => 'Answer saved successfully.',
        ]);
    }

    /**
     * Navigate to next question (AJAX)
     */
    public function nextQuestion(ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hasNext = $session->nextQuestion();

        if ($hasNext) {
            $question = $session->current_question;
            
            if ($question && $question->question_type === 'multiple_choice') {
                $question->load('options');
            }

            $currentAnswer = $session->getAnswer($question->id);

            return response()->json([
                'success' => true,
                'question' => [
                    'id' => $question->id,
                    'section' => $question->section,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'passage_text' => $question->passage_text,
                    'audio_url' => $question->audio_url,
                    'image_url' => $question->image_url,
                    'difficulty' => $question->difficulty,
                    'options' => $question->question_type === 'multiple_choice' ? $question->options : null,
                ],
                'current_index' => $session->current_question_index,
                'total_questions' => $session->total_questions,
                'current_answer' => $currentAnswer,
                'has_next' => $session->current_question_index < $session->total_questions - 1,
                'has_previous' => $session->current_question_index > 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No more questions. Please submit your answers.',
            'has_next' => false,
        ]);
    }

    /**
     * Navigate to previous question (AJAX)
     */
    public function previousQuestion(ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hasPrevious = $session->previousQuestion();

        if ($hasPrevious) {
            $question = $session->current_question;
            
            if ($question && $question->question_type === 'multiple_choice') {
                $question->load('options');
            }

            $currentAnswer = $session->getAnswer($question->id);

            return response()->json([
                'success' => true,
                'question' => [
                    'id' => $question->id,
                    'section' => $question->section,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'passage_text' => $question->passage_text,
                    'audio_url' => $question->audio_url,
                    'image_url' => $question->image_url,
                    'difficulty' => $question->difficulty,
                    'options' => $question->question_type === 'multiple_choice' ? $question->options : null,
                ],
                'current_index' => $session->current_question_index,
                'total_questions' => $session->total_questions,
                'current_answer' => $currentAnswer,
                'has_next' => true,
                'has_previous' => $session->current_question_index > 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Already at first question.',
            'has_previous' => false,
        ]);
    }

    /**
     * Submit exercise and get instant correction (for Reading/Listening)
     */
    public function submit(ExerciseSession $session)
    {
        // Check ownership
        if ($session->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $session->markAsCompleted();

        $questions = $session->questions;
        $userAnswers = $session->user_answers ?? [];

        $results = [];
        $correctCount = 0;
        $totalScore = 0;

        foreach ($questions as $question) {
            $userAnswer = $userAnswers[$question->id] ?? null;
            $isCorrect = false;

            // For multiple choice, compare with correct_answer
            if ($question->question_type === 'multiple_choice') {
                $isCorrect = ($userAnswer == $question->correct_answer);
                if ($isCorrect) {
                    $correctCount++;
                }
            }

            $results[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'user_answer' => $userAnswer,
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
                'options' => $question->options,
            ];
        }

        // Calculate score (0-100 scale)
        if ($session->total_questions > 0) {
            $totalScore = round(($correctCount / $session->total_questions) * 100, 2);
        }

        // Calculate duration
        $durationSeconds = $session->started_at->diffInSeconds(now());

        // Save to exercise history
        ExerciseHistory::create([
            'user_id' => Auth::id(),
            'exercise_type' => 'practice',
            'section' => $session->section,
            'mode' => 'timed',
            'score' => $totalScore,
            'total_questions' => $session->total_questions,
            'correct_answers' => $correctCount,
            'duration_seconds' => $durationSeconds,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'completed' => true,
            'score' => $totalScore,
            'correct_count' => $correctCount,
            'total_questions' => $session->total_questions,
            'duration_seconds' => $durationSeconds,
            'results' => $results,
            'section' => $session->section,
        ]);
    }

    /**
     * Get user's exercise history
     */
    public function history()
    {
        $histories = ExerciseHistory::where('user_id', Auth::id())
            ->orderBy('completed_at', 'desc')
            ->paginate(20);

        return view('exercises.history', compact('histories'));
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $stats = DB::table('exercise_histories')
            ->where('user_id', Auth::id())
            ->selectRaw('section, COUNT(*) as total_exercises, AVG(score) as avg_score')
            ->groupBy('section')
            ->get();

        $recentExercises = ExerciseHistory::where('user_id', Auth::id())
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'statistics' => $stats,
            'recent_exercises' => $recentExercises,
        ]);
    }
}
