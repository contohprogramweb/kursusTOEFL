<?php

namespace App\Http\Controllers;

use App\Models\Simulation;
use App\Services\PerformanceRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    protected PerformanceRecommendationService $recommendationService;

    public function __construct(PerformanceRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Display recommendations for current user
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $filters = [
            'category' => $request->query('category'),
            'unreadOnly' => $request->query('unread', false),
            'limit' => $request->query('limit', 10),
        ];
        
        $recommendations = $this->recommendationService->getUserRecommendations(
            $user->id,
            (int) $filters['limit'],
            $filters['category'],
            (bool) $filters['unreadOnly']
        );
        
        // Group by urgency
        $groupedByUrgency = $recommendations->groupBy('urgency_factor');
        
        return view('recommendations.index', compact('recommendations', 'groupedByUrgency'));
    }

    /**
     * Generate recommendations from latest simulation
     */
    public function generateFromLatest(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get latest simulation
        $simulation = Simulation::where('user_id', $user->id)
                                ->latest('completed_at')
                                ->first();
        
        if (!$simulation) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada simulasi yang diselesaikan.',
            ], 404);
        }
        
        // Get user profile (target_score, test_date dari study plan atau profile)
        $userProfile = [
            'target_score' => 550, // Default, bisa diambil dari user profile
            'test_date' => now()->addDays(60)->toDateString(), // Default 60 hari
        ];
        
        // Jika user punya study plan, ambil dari sana
        if ($user->studyPlans()->exists()) {
            $activePlan = $user->studyPlans()->where('status', 'active')->latest()->first();
            if ($activePlan) {
                $userProfile['target_score'] = $activePlan->target_score ?? 550;
                $userProfile['test_date'] = $activePlan->test_date ?? $userProfile['test_date'];
            }
        }
        
        // Generate recommendations
        $recommendations = $this->recommendationService->generateFromSimulation(
            $simulation,
            $userProfile
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi berhasil dibuat berdasarkan simulasi terbaru.',
            'data' => [
                'simulation_id' => $simulation->id,
                'total_score' => $simulation->total_score,
                'recommendations_count' => $recommendations->count(),
                'recommendations' => $recommendations->map(fn($r) => [
                    'id' => $r->id,
                    'type' => $r->type,
                    'title' => $r->title,
                    'reason' => $r->reason,
                    'action_plan' => $r->action_plan,
                    'priority' => $r->priority,
                    'impact_score' => $r->impact_score,
                    'urgency_factor' => $r->urgency_factor,
                    'urgency_label' => $r->urgency_label,
                    'icon' => $r->icon,
                ]),
            ],
        ]);
    }

    /**
     * Generate recommendations from specific simulation
     */
    public function generateFromSimulation(int $simulationId, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $simulation = Simulation::where('id', $simulationId)
                                ->where('user_id', $user->id)
                                ->first();
        
        if (!$simulation) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi tidak ditemukan.',
            ], 404);
        }
        
        $userProfile = $request->only(['target_score', 'test_date']);
        
        $recommendations = $this->recommendationService->generateFromSimulation(
            $simulation,
            $userProfile
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi berhasil dibuat.',
            'data' => [
                'simulation_id' => $simulation->id,
                'recommendations_count' => $recommendations->count(),
                'recommendations' => $recommendations,
            ],
        ]);
    }

    /**
     * Mark recommendation as read
     */
    public function markAsRead(int $recommendationId): JsonResponse
    {
        $success = $this->recommendationService->markAsRead($recommendationId);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Rekomendasi ditandai sebagai sudah dibaca.' : 'Gagal menandai rekomendasi.',
        ]);
    }

    /**
     * Mark all recommendations as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $count = \App\Models\Recommendation::where('user_id', $user->id)
                                           ->where('is_read', false)
                                           ->update(['is_read' => true]);
        
        return response()->json([
            'success' => true,
            'message' => "{$count} rekomendasi ditandai sebagai sudah dibaca.",
        ]);
    }

    /**
     * Get API endpoint for recommendations (for AJAX/fetch calls)
     */
    public function apiGet(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = [
            'limit' => $request->query('limit', 5),
            'category' => $request->query('category'),
            'unreadOnly' => $request->query('unread', false),
        ];
        
        $recommendations = $this->recommendationService->getUserRecommendations(
            $user->id,
            (int) $filters['limit'],
            $filters['category'],
            (bool) $filters['unreadOnly']
        );
        
        return response()->json([
            'success' => true,
            'data' => $recommendations->map(fn($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'category' => $r->category,
                'micro_skill' => $r->micro_skill,
                'title' => $r->title,
                'reason' => $r->reason,
                'action_plan' => $r->action_plan,
                'priority' => $r->priority,
                'impact_score' => $r->impact_score,
                'urgency_factor' => $r->urgency_factor,
                'urgency_label' => $r->urgency_label,
                'urgency_color' => $r->urgency_color,
                'icon' => $r->icon,
                'is_read' => $r->is_read,
                'generated_at' => $r->generated_at?->diffForHumans(),
                'days_until_test' => $r->days_until_test,
            ]),
        ]);
    }

    /**
     * Clear old recommendations
     */
    public function clearOld(Request $request): JsonResponse
    {
        $user = $request->user();
        $daysOld = $request->input('days_old', 30);
        
        $deleted = $this->recommendationService->clearOldRecommendations($user->id, $daysOld);
        
        return response()->json([
            'success' => true,
            'message' => "{$deleted} rekomendasi lama telah dihapus.",
        ]);
    }
}
