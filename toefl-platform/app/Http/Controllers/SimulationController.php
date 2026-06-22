<?php

namespace App\Http\Controllers;

use App\Models\SimulationTemplate;
use App\Models\SimulationResult;
use App\Models\SectionResult;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Services\AutoGradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SimulationController extends Controller
{
    /**
     * Display available simulation templates for the user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $institutionId = $user->profile?->institution_id ?? null;

        $query = SimulationTemplate::with('sections')
            ->active();

        if ($institutionId) {
            $query->scopeForInstitution($institutionId);
        }

        // Filter by mode if provided
        if ($request->has('mode')) {
            $query->where('mode', $request->mode);
        }

        $templates = $query->get();

        // Get user's in-progress and completed simulations
        $inProgress = SimulationResult::with('template')
            ->where('user_id', $user->id)
            ->inProgress()
            ->orderBy('created_at', 'desc')
            ->get();

        $completed = SimulationResult::with('template')
            ->where('user_id', $user->id)
            ->completed()
            ->orderBy('end_time', 'desc')
            ->limit(5)
            ->get();

        return view('simulations.index', compact('templates', 'inProgress', 'completed'));
    }

    /**
     * Start a new simulation from a template.
     */
    public function start(SimulationTemplate $template)
    {
        $user = auth()->user();

        // Check if user already has an in-progress simulation with this template
        $existing = SimulationResult::where('user_id', $user->id)
            ->where('template_id', $template->id)
            ->inProgress()
            ->first();

        if ($existing) {
            return redirect()->route('simulations.resume', $existing->id)
                ->with('info', 'You have an existing simulation in progress.');
        }

        // Create new simulation result
        $simulation = DB::transaction(function () use ($template, $user) {
            $simulation = SimulationResult::create([
                'user_id' => $user->id,
                'template_id' => $template->id,
                'mode' => $template->mode,
                'status' => SimulationTemplate::STATUS_INITIATED,
                'start_time' => now(),
                'current_section_index' => 0,
            ]);

            // Create section results for each section in the template
            foreach ($template->sections as $section) {
                SectionResult::create([
                    'result_id' => $simulation->id,
                    'section' => $section->section,
                    'status' => 'not_started',
                ]);
            }

            return $simulation;
        });

        return redirect()->route('simulations.run', $simulation->id);
    }

    /**
     * Resume an in-progress simulation.
     */
    public function resume(SimulationResult $simulation)
    {
        if ($simulation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!$simulation->isInProgress()) {
            return redirect()->route('simulations.results.show', $simulation->id)
                ->with('error', 'This simulation is not in progress.');
        }

        return redirect()->route('simulations.run', $simulation->id);
    }

    /**
     * Run a simulation (main interface).
     */
    public function run(SimulationResult $simulation)
    {
        if ($simulation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $simulation->load(['template.sections', 'sectionResults']);

        return view('simulations.run', compact('simulation'));
    }

    /**
     * Transition to the next section in the simulation.
     */
    public function nextSection(SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$simulation->isInProgress()) {
            return response()->json(['error' => 'Simulation is not in progress'], 400);
        }

        try {
            $simulation->transitionToNextStatus();
            
            return response()->json([
                'success' => true,
                'new_status' => $simulation->status,
                'current_section_index' => $simulation->current_section_index,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Submit the simulation for grading.
     */
    public function submit(SimulationResult $simulation, AutoGradingService $gradingService): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$simulation->isInProgress()) {
            return response()->json(['error' => 'Simulation is not in progress'], 400);
        }

        DB::transaction(function () use ($simulation, $gradingService) {
            // Transition to submitted status
            $simulation->transitionTo(SimulationTemplate::STATUS_SUBMITTED);
            
            // Mark all section results as submitted for grading
            $simulation->sectionResults()->update([
                'status' => 'graded'
            ]);
            
            // Perform auto-grading for Reading and Listening sections (SLA: <= 1 detik)
            $gradingService->gradeSimulation($simulation);
        });

        return response()->json([
            'success' => true,
            'message' => 'Simulation submitted and graded successfully',
        ]);
    }

    /**
     * Pause the simulation.
     */
    public function pause(SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $simulation->pause();

        return response()->json([
            'success' => true,
            'paused_at' => $simulation->paused_at?->toIso8601String(),
        ]);
    }

    /**
     * Resume a paused simulation.
     */
    public function resumeSimulation(SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $simulation->resume();

        return response()->json([
            'success' => true,
            'resumed' => true,
        ]);
    }

    /**
     * Record time spent on current section.
     */
    public function recordTime(Request $request, SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'seconds' => 'required|integer|min:1',
        ]);

        $sectionMap = [
            SimulationTemplate::STATUS_READING => 'reading',
            SimulationTemplate::STATUS_LISTENING => 'listening',
            SimulationTemplate::STATUS_SPEAKING => 'speaking',
            SimulationTemplate::STATUS_WRITING => 'writing',
        ];

        $currentSection = $sectionMap[$simulation->status] ?? null;
        
        if ($currentSection) {
            $simulation->recordSectionTime($currentSection, $validated['seconds']);
        }

        return response()->json([
            'success' => true,
            'section_times' => $simulation->section_times,
        ]);
    }

    /**
     * Save answer for a question (auto-save every 30 detik).
     */
    public function saveAnswer(Request $request, SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_option_id' => 'nullable|exists:question_options,id',
            'text_response' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'time_spent_seconds' => 'nullable|integer|min:0',
            'flagged' => 'nullable|boolean',
        ]);

        $sectionMap = [
            SimulationTemplate::STATUS_READING => 'reading',
            SimulationTemplate::STATUS_LISTENING => 'listening',
            SimulationTemplate::STATUS_SPEAKING => 'speaking',
            SimulationTemplate::STATUS_WRITING => 'writing',
        ];

        $currentSection = $sectionMap[$simulation->status] ?? null;

        if (!$currentSection) {
            return response()->json(['error' => 'No active section'], 400);
        }

        // Get the section result for current section
        $sectionResult = $simulation->sectionResults()
            ->where('section', $currentSection)
            ->first();

        if (!$sectionResult) {
            return response()->json(['error' => 'Section result not found'], 400);
        }

        // Update or create question response
        $response = QuestionResponse::updateOrCreate(
            [
                'section_result_id' => $sectionResult->id,
                'question_id' => $validated['question_id'],
            ],
            [
                'selected_option_id' => $validated['selected_option_id'] ?? null,
                'text_response' => $validated['text_response'] ?? null,
                'audio_url' => $validated['audio_url'] ?? null,
                'time_spent_seconds' => $validated['time_spent_seconds'] ?? 0,
                'flagged' => $validated['flagged'] ?? false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Answer saved successfully',
            'response_id' => $response->id,
        ]);
    }

    /**
     * Bulk save answers (for auto-save).
     */
    public function bulkSaveAnswers(Request $request, SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.selected_option_id' => 'nullable|exists:question_options,id',
            'answers.*.text_response' => 'nullable|string',
            'answers.*.audio_url' => 'nullable|string',
            'answers.*.time_spent_seconds' => 'nullable|integer|min:0',
            'answers.*.flagged' => 'nullable|boolean',
        ]);

        $sectionMap = [
            SimulationTemplate::STATUS_READING => 'reading',
            SimulationTemplate::STATUS_LISTENING => 'listening',
            SimulationTemplate::STATUS_SPEAKING => 'speaking',
            SimulationTemplate::STATUS_WRITING => 'writing',
        ];

        $currentSection = $sectionMap[$simulation->status] ?? null;

        if (!$currentSection) {
            return response()->json(['error' => 'No active section'], 400);
        }

        // Get the section result for current section
        $sectionResult = $simulation->sectionResults()
            ->where('section', $currentSection)
            ->first();

        if (!$sectionResult) {
            return response()->json(['error' => 'Section result not found'], 400);
        }

        $savedCount = 0;

        DB::transaction(function () use ($validated, $sectionResult, &$savedCount) {
            foreach ($validated['answers'] as $answer) {
                QuestionResponse::updateOrCreate(
                    [
                        'section_result_id' => $sectionResult->id,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'selected_option_id' => $answer['selected_option_id'] ?? null,
                        'text_response' => $answer['text_response'] ?? null,
                        'audio_url' => $answer['audio_url'] ?? null,
                        'time_spent_seconds' => $answer['time_spent_seconds'] ?? 0,
                        'flagged' => $answer['flagged'] ?? false,
                    ]
                );
                $savedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Saved {$savedCount} answer(s) successfully",
            'saved_count' => $savedCount,
        ]);
    }

    /**
     * Get simulation status and progress.
     */
    public function getStatus(SimulationResult $simulation): JsonResponse
    {
        if ($simulation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $simulation->load(['template.sections', 'sectionResults']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $simulation->id,
                'status' => $simulation->status,
                'current_section_index' => $simulation->current_section_index,
                'is_paused' => $simulation->isPaused(),
                'elapsed_seconds' => $simulation->getElapsedTimeSeconds(),
                'section_times' => $simulation->section_times,
                'total_duration_minutes' => $simulation->template->total_duration,
                'sections' => $simulation->template->sections->map(fn($s) => [
                    'section' => $s->section,
                    'duration_minutes' => $s->duration_minutes,
                    'question_count' => $s->question_count,
                    'has_break' => $s->hasBreak(),
                    'break_duration' => $s->break_duration,
                ]),
            ],
        ]);
    }

    /**
     * Show simulation results.
     */
    public function showResults(SimulationResult $simulation)
    {
        if ($simulation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $simulation->load(['template', 'sectionResults.questionResponses']);

        return view('simulations.results.show', compact('simulation'));
    }

    /**
     * View detailed results for a specific section.
     */
    public function showSectionResults(SimulationResult $simulation, string $section)
    {
        if ($simulation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $sectionResult = $simulation->sectionResults()
            ->where('section', $section)
            ->with('questionResponses.question.options')
            ->firstOrFail();

        return view('simulations.results.section', compact('simulation', 'sectionResult'));
    }
}
