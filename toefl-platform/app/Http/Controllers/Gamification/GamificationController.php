<?php

namespace App\Http\Controllers\Gamification;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GamificationController extends Controller
{
    protected GamificationService $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Get user's gamification stats
     */
    public function getStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $stats = $this->gamificationService->getUserStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Use a streak freeze
     */
    public function useFreeze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|in:sick,urgent,holiday',
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = $request->user()->id;
        $result = $this->gamificationService->useFreeze(
            $userId,
            $validated['reason'],
            $validated['notes'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Toggle badge visibility
     */
    public function toggleBadgeVisibility(Request $request, int $badgeId): JsonResponse
    {
        $validated = $request->validate([
            'is_public' => 'required|boolean',
        ]);

        $userId = $request->user()->id;
        $success = $this->gamificationService->toggleBadgeVisibility(
            $userId,
            $badgeId,
            $validated['is_public']
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Badge visibility updated',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Badge not found or unauthorized',
        ], 404);
    }

    /**
     * Get user's badges
     */
    public function getBadges(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $publicOnly = $request->query('public_only', 'false') === 'true';
        
        $badges = Badge::getUserBadges($userId, $publicOnly);

        return response()->json([
            'success' => true,
            'data' => $badges->map(fn($badge) => [
                'id' => $badge->id,
                'code' => $badge->badge_code,
                'name' => $badge->badge_name,
                'icon' => $badge->badge_icon,
                'description' => $badge->badge_description,
                'category' => $badge->category,
                'difficulty' => $badge->difficulty,
                'points' => $badge->points,
                'awarded_at' => $badge->awarded_at->toIso8601String(),
                'is_public' => $badge->is_public,
            ]),
        ]);
    }

    /**
     * Get all available badges (for reference)
     */
    public function getAllBadges(): JsonResponse
    {
        $badges = collect(Badge::BADGES)->map(function ($badge, $code) {
            return [
                'code' => $code,
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'category' => $badge['category'],
                'difficulty' => $badge['difficulty'],
                'points' => $badge['points'],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $badges,
        ]);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:streak,badges,points',
            'limit' => 'sometimes|integer|min:1|max:100',
            'institution_id' => 'sometimes|nullable|integer',
        ]);

        $leaderboard = $this->gamificationService->getLeaderboard(
            $validated['institution_id'] ?? null,
            $validated['type'] ?? 'streak',
            $validated['limit'] ?? 10
        );

        return response()->json([
            'success' => true,
            'data' => $leaderboard,
        ]);
    }
}
