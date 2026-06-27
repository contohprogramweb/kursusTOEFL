<?php

namespace App\Http\Controllers;

use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use App\Models\StudyPlanAdjustment;
use App\Models\StudyPlanReminder;
use App\Services\StudyPlanGeneratorService;
use App\Notifications\StudyPlanReminderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudyPlanController extends Controller
{
    protected StudyPlanGeneratorService $generatorService;

    public function __construct(StudyPlanGeneratorService $generatorService)
    {
        $this->generatorService = $generatorService;
    }

    /**
     * Display form to create new study plan
     */
    public function create()
    {
        return view('study-plans.create', [
            'defaultTestDate' => now()->addMonths(3),
            'defaultDailyHours' => 2.0,
            'availableDaysOptions' => [
                ['value' => 0, 'label' => 'Minggu'],
                ['value' => 1, 'label' => 'Senin'],
                ['value' => 2, 'label' => 'Selasa'],
                ['value' => 3, 'label' => 'Rabu'],
                ['value' => 4, 'label' => 'Kamis'],
                ['value' => 5, 'label' => 'Jumat'],
                ['value' => 6, 'label' => 'Sabtu'],
            ],
        ]);
    }

    /**
     * Store a newly created study plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_score' => 'required|integer|min:0|max:677',
            'test_date' => 'required|date|after:today',
            'daily_hours' => 'required|numeric|min:0.5|max:12',
            'available_days' => 'required|array|min:1',
            'available_days.*' => 'integer|min:0|max:6',
            'plan_name' => 'nullable|string|max:255',
        ]);

        $studyPlan = $this->generatorService->generatePlan(
            Auth::user(),
            $validated['target_score'],
            Carbon::parse($validated['test_date']),
            $validated['daily_hours'],
            $validated['available_days'],
            $validated['plan_name'] ?? null
        );

        return redirect()->route('study-plan.show', $studyPlan)
            ->with('success', 'Study Plan berhasil dibuat!');
    }

    /**
     * Display the specified study plan with calendar view
     */
    public function show(StudyPlan $studyPlan)
    {
        // Authorize user can only view their own plans
        if ($studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $studyPlan->load(['tasks' => function ($query) {
            $query->orderBy('order');
        }]);

        // Group tasks by date for calendar view
        $tasksByDate = $studyPlan->tasks->groupBy(function ($task) {
            return $task->getMetadataValue('scheduled_date', $studyPlan->start_date->toDateString());
        });

        // Calculate statistics
        $totalTasks = $studyPlan->total_tasks;
        $completedTasks = $studyPlan->tasks->where('is_completed', true)->count();
        $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        
        // Get today's tasks
        $today = now()->toDateString();
        $todayTasks = $tasksByDate->get($today, collect());

        // Get upcoming tasks (next 7 days)
        $upcomingTasks = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->toDateString();
            if ($tasksByDate->has($date)) {
                $upcomingTasks = $upcomingTasks->merge($tasksByDate->get($date));
            }
        }

        return view('study-plans.show', compact('studyPlan', 'tasksByDate', 'progressPercentage', 'todayTasks', 'upcomingTasks'));
    }

    /**
     * Mark a task as completed
     */
    public function completeTask(StudyPlanTask $task)
    {
        // Authorize
        if ($task->studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $task->markAsCompleted();

        // Update parent study plan progress
        $studyPlan = $task->studyPlan;
        $studyPlan->increment('completed_tasks');

        // Check if all tasks completed
        if ($studyPlan->completed_tasks >= $studyPlan->total_tasks) {
            $studyPlan->update(['status' => 'completed']);
        }

        return back()->with('success', 'Tugas berhasil diselesaikan! 🎉');
    }

    /**
     * Unmark a task as completed
     */
    public function uncompleteTask(StudyPlanTask $task)
    {
        // Authorize
        if ($task->studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $task->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        // Update parent study plan progress
        $studyPlan = $task->studyPlan;
        $studyPlan->decrement('completed_tasks');
        $studyPlan->update(['status' => 'active']);

        return back()->with('success', 'Tugas ditandai belum selesai.');
    }

    /**
     * Adjust task schedule (reschedule)
     */
    public function adjustTask(Request $request, StudyPlanTask $task)
    {
        // Authorize
        if ($task->studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'adjustment_type' => 'required|in:reschedule,skip,add_custom',
            'new_date' => 'required_if:adjustment_type,reschedule|date|after_or_equal:today',
            'reason' => 'nullable|string|max:500',
        ]);

        // Record adjustment
        StudyPlanAdjustment::create([
            'study_plan_id' => $task->study_plan_id,
            'user_id' => Auth::id(),
            'task_id' => $task->id,
            'adjustment_type' => $validated['adjustment_type'],
            'reason' => $validated['reason'] ?? null,
        ]);

        if ($validated['adjustment_type'] === 'reschedule' && isset($validated['new_date'])) {
            // Update task metadata with new scheduled date
            $metadata = $task->metadata ?? [];
            $metadata['scheduled_date'] = $validated['new_date'];
            $metadata['rescheduled'] = true;
            $metadata['original_date'] = $task->getMetadataValue('scheduled_date');
            
            $task->update(['metadata' => $metadata]);
        } elseif ($validated['adjustment_type'] === 'skip') {
            // Mark as skipped (we can add a skipped status if needed)
            $task->update([
                'is_completed' => true,
                'completed_at' => now(),
                'metadata' => array_merge($task->metadata ?? [], ['skipped' => true]),
            ]);
        }

        return back()->with('success', 'Jadwal tugas berhasil disesuaikan.');
    }

    /**
     * Regenerate study plan with new parameters
     */
    public function regenerate(Request $request, StudyPlan $studyPlan)
    {
        // Authorize
        if ($studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'target_score' => 'sometimes|integer|min:0|max:677',
            'test_date' => 'sometimes|date|after:today',
            'daily_hours' => 'sometimes|numeric|min:0.5|max:12',
            'available_days' => 'sometimes|array|min:1',
            'available_days.*' => 'integer|min:0|max:6',
        ]);

        $this->generatorService->regeneratePlan($studyPlan, $validated);

        return back()->with('success', 'Study Plan berhasil dibuat ulang!');
    }

    /**
     * Get study plan data for calendar visualization (API endpoint)
     */
    public function calendarData(StudyPlan $studyPlan)
    {
        // Authorize
        if ($studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $tasks = $studyPlan->tasks()
            ->orderBy('order')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => $task->type,
                    'section' => $task->section,
                    'estimated_minutes' => $task->estimated_minutes,
                    'is_completed' => $task->is_completed,
                    'priority' => $task->priority,
                    'scheduled_date' => $task->getMetadataValue('scheduled_date'),
                    'difficulty' => $task->getMetadataValue('difficulty'),
                ];
            });

        return response()->json([
            'study_plan' => $studyPlan,
            'tasks' => $tasks,
            'progress' => [
                'total' => $studyPlan->total_tasks,
                'completed' => $studyPlan->completed_tasks,
                'percentage' => $studyPlan->progress_percentage,
            ],
        ]);
    }

    /**
     * Send manual reminder for today's tasks
     */
    public function sendReminder(StudyPlan $studyPlan)
    {
        // Authorize
        if ($studyPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $today = now()->toDateString();
        $todayTasks = $studyPlan->tasks()
            ->whereJsonContains('metadata->scheduled_date', $today)
            ->where('is_completed', false)
            ->get();

        $sentCount = 0;
        foreach ($todayTasks as $task) {
            // Check if reminder already sent today
            $existingReminder = StudyPlanReminder::where('user_id', Auth::id())
                ->where('task_id', $task->id)
                ->where('scheduled_date', $today)
                ->first();

            if (!$existingReminder || !$existingReminder->is_sent) {
                Auth::user()->notify(new StudyPlanReminderNotification($task, $studyPlan->name));
                
                StudyPlanReminder::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'task_id' => $task->id,
                        'scheduled_date' => $today,
                    ],
                    ['is_sent' => true, 'sent_at' => now()]
                );
                
                $sentCount++;
            }
        }

        return back()->with('success', "Reminder dikirim untuk {$sentCount} tugas.");
    }
}
