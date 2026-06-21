<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleContent;
use App\Models\LearningProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LearningController extends Controller
{
    /**
     * Display modules available for learning.
     */
    public function index(Request $request)
    {
        $query = Module::published()->with(['creator', 'learningProgresses' => function ($q) {
            $q->where('user_id', Auth::id());
        }]);

        // Filters
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Show user's progress
        $modules = $query->orderBy('order_index')->get();

        return view('learning.index', compact('modules'));
    }

    /**
     * Display the specified module for learning.
     */
    public function show(Module $module)
    {
        // Check if module is published or user is admin
        if ($module->status !== 'published' && !Auth::user()->isAdmin()) {
            abort(403, 'Module is not available.');
        }

        $contents = $module->contents()->ordered()->get();
        
        // Get or create learning progress
        $progress = LearningProgress::firstOrCreate(
            ['user_id' => Auth::id(), 'module_id' => $module->id],
            ['progress_percentage' => 0, 'time_spent_minutes' => 0]
        );

        // Get last accessed content for resume functionality
        $lastContentId = Cache::get("learning.module_{$module->id}.user_" . Auth::id() . '.last_content');
        
        // Calculate total contents and completed count
        $totalContents = $contents->count();
        $completedCount = 0; // Will be updated as user progresses

        return view('learning.show', compact('module', 'contents', 'progress', 'lastContentId', 'totalContents', 'completedCount'));
    }

    /**
     * Start or resume a module.
     */
    public function start(Module $module)
    {
        // Get or create learning progress
        $progress = LearningProgress::firstOrCreate(
            ['user_id' => Auth::id(), 'module_id' => $module->id],
            ['progress_percentage' => 0, 'time_spent_minutes' => 0]
        );

        // Get last accessed content for resume
        $lastContentId = Cache::get("learning.module_{$module->id}.user_" . Auth::id() . '.last_content');

        if ($lastContentId) {
            $lastContent = ModuleContent::find($lastContentId);
            if ($lastContent && $lastContent->module_id === $module->id) {
                return redirect()->route('learning.content.show', [$module, $lastContent]);
            }
        }

        // If no last content, go to first content
        $firstContent = $module->contents()->ordered()->first();
        if ($firstContent) {
            return redirect()->route('learning.content.show', [$module, $firstContent]);
        }

        return redirect()->route('learning.modules.show', $module);
    }

    /**
     * Display a specific content within a module.
     */
    public function showContent(Module $module, ModuleContent $content)
    {
        if ($content->module_id !== $module->id) {
            abort(404);
        }

        // Update last accessed cache for resume functionality
        Cache::put(
            "learning.module_{$module->id}.user_" . Auth::id() . '.last_content',
            $content->id,
            now()->addDays(30)
        );

        // Update learning progress - last_accessed
        LearningProgress::where('user_id', Auth::id())
            ->where('module_id', $module->id)
            ->update(['last_accessed' => now()]);

        // Get navigation
        $previousContent = ModuleContent::where('module_id', $module->id)
            ->where('order_index', '<', $content->order_index)
            ->orderBy('order_index', 'desc')
            ->first();

        $nextContent = ModuleContent::where('module_id', $module->id)
            ->where('order_index', '>', $content->order_index)
            ->orderBy('order_index')
            ->first();

        return view('learning.content', compact('module', 'content', 'previousContent', 'nextContent'));
    }

    /**
     * Update progress for a content.
     */
    public function updateProgress(Request $request, Module $module, ModuleContent $content)
    {
        if ($content->module_id !== $module->id) {
            abort(404);
        }

        $validated = $request->validate([
            'time_spent' => 'nullable|integer|min:0',
        ]);

        // Update or create learning progress
        $progress = LearningProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'module_id' => $module->id],
            [
                'last_accessed' => now(),
            ]
        );

        // Add time spent if provided
        if (isset($validated['time_spent'])) {
            $progress->increment('time_spent_minutes', $validated['time_spent']);
        }

        // Calculate progress based on contents completed
        $totalContents = $module->contents()->count();
        if ($totalContents > 0) {
            // Get current content index
            $currentIndex = $content->order_index;
            $progressPercentage = min(100, round((($currentIndex + 1) / $totalContents) * 100, 2));
            
            $progress->update(['progress_percentage' => $progressPercentage]);

            // Mark as completed if 100%
            if ($progressPercentage >= 100 && !$progress->completed_at) {
                $progress->update(['completed_at' => now()]);
            }
        }

        return response()->json(['success' => true, 'progress' => $progress->progress_percentage]);
    }

    /**
     * Clear resume position (start over).
     */
    public function clearResumePosition(Module $module)
    {
        Cache::forget("learning.module_{$module->id}.user_" . Auth::id() . '.last_content');
        
        return redirect()->route('learning.modules.start', $module)
            ->with('success', 'Progress reset. Starting from beginning.');
    }

    /**
     * Get user's learning dashboard with all progress.
     */
    public function dashboard()
    {
        $inProgress = LearningProgress::where('user_id', Auth::id())
            ->whereIn('progress_percentage', range(1, 99))
            ->with('module')
            ->orderBy('last_accessed', 'desc')
            ->get();

        $completed = LearningProgress::where('user_id', Auth::id())
            ->whereNotNull('completed_at')
            ->with('module')
            ->orderBy('completed_at', 'desc')
            ->get();

        $notStarted = Module::published()
            ->whereNotIn('id', LearningProgress::where('user_id', Auth::id())->pluck('module_id'))
            ->orderBy('order_index')
            ->get();

        return view('learning.dashboard', compact('inProgress', 'completed', 'notStarted'));
    }
}
