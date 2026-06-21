<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\GenerateInviteCodeRequest;
use App\Http\Requests\Parent\SubmitInviteCodeRequest;
use App\Http\Requests\Parent\ApproveLinkRequest;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\ParentLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ParentLinkController extends Controller
{
    protected ParentLinkService $linkService;

    public function __construct(ParentLinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    /**
     * Tampilkan halaman generate invite code untuk siswa
     */
    public function showGenerateCode()
    {
        return view('parent.generate-code');
    }

    /**
     * Generate invite code (AJAX)
     */
    public function generateCode(GenerateInviteCodeRequest $request)
    {
        $student = Auth::user();
        $code = $this->linkService->generateInviteCode($student);

        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => 'Kode undangan berhasil dibuat. Kode ini berlaku selama 24 jam.',
        ]);
    }

    /**
     * Tampilkan halaman input invite code untuk orang tua
     */
    public function showSubmitCode()
    {
        return view('parent.submit-code');
    }

    /**
     * Submit invite code (AJAX)
     */
    public function submitCode(SubmitInviteCodeRequest $request)
    {
        $parent = Auth::user();
        $code = strtoupper($request->input('invite_code'));

        $result = $this->linkService->processInviteCode($parent, $code);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'link' => $result['link'] ?? null,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ], 422);
    }

    /**
     * Tampilkan daftar pending requests untuk siswa
     */
    public function pendingRequests()
    {
        $student = Auth::user();
        
        Gate::authorize('viewAny', ParentStudentLink::class);

        $pendingLinks = $this->linkService->getPendingLinksForStudent($student);

        return view('parent.pending-requests', compact('pendingLinks'));
    }

    /**
     * Approve link (AJAX)
     */
    public function approveLink(Request $request, ParentStudentLink $link)
    {
        $student = Auth::user();

        Gate::authorize('approve', [$link, $student]);

        $result = $this->linkService->approveLink($link, $student);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'link' => $result['link'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ], 422);
    }

    /**
     * Revoke link (AJAX)
     */
    public function revokeLink(Request $request, ParentStudentLink $link)
    {
        $student = Auth::user();

        Gate::authorize('revoke', [$link, $student]);

        $result = $this->linkService->revokeLink($link, $student);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'link' => $result['link'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ], 422);
    }

    /**
     * Tampilkan daftar children untuk orang tua
     */
    public function myChildren()
    {
        $parent = Auth::user();
        
        Gate::authorize('viewAny', ParentStudentLink::class);

        $activeLinks = $this->linkService->getActiveLinksForParent($parent);
        $childrenCount = $this->linkService->getActiveChildrenCount($parent);

        return view('parent.my-children', compact('activeLinks', 'childrenCount'));
    }

    /**
     * Tampilkan dashboard parent-student links
     */
    public function dashboard()
    {
        $user = Auth::user();

        if ($user->isStudent()) {
            $pendingLinks = $this->linkService->getPendingLinksForStudent($user);
            $activeLinks = $this->linkService->getActiveLinksForStudent($user);
            
            return view('parent.student-dashboard', compact('pendingLinks', 'activeLinks'));
        } elseif ($user->isParent()) {
            $activeLinks = $this->linkService->getActiveLinksForParent($user);
            $childrenCount = $this->linkService->getActiveChildrenCount($user);
            
            return view('parent.parent-dashboard', compact('activeLinks', 'childrenCount'));
        }

        abort(403, 'Unauthorized access.');
    }
}
