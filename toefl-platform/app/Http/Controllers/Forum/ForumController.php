<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumThread;
use App\Models\ForumReply;
use App\Models\ForumAttachment;
use App\Models\ThreadFollower;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ForumController extends Controller
{
    /**
     * Display forum index with categories
     */
    public function index(Request $request)
    {
        $category = $request->get('category', 'all');
        $search = $request->get('search', '');

        $query = ForumThread::with(['author', 'author.profile'])
            ->withCount('replies')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        // Hide flagged threads for non-admin users
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            $query->where('is_flagged', false);
        }

        $threads = $query->paginate(20);
        $categories = ForumThread::getCategories();

        return view('forum.index', compact('threads', 'categories', 'category', 'search'));
    }

    /**
     * Show thread detail with replies
     */
    public function show(Request $request, ForumThread $thread)
    {
        // Increment view count
        $thread->incrementViewCount();

        // Auto-follow thread when viewing (for authenticated users)
        if (Auth::check()) {
            ThreadFollower::subscribe($thread->id, Auth::id());
        }

        // Load root replies with nested children
        $rootReplies = $thread->rootReplies()
            ->with(['author', 'author.profile', 'attachments'])
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc')
            ->get();

        // Load nested replies recursively (max 3 levels)
        $this->loadNestedReplies($rootReplies);

        // Check if user is following
        $isFollowing = Auth::check() ? ThreadFollower::isFollowing($thread->id, Auth::id()) : false;

        return view('forum.show', compact('thread', 'rootReplies', 'isFollowing'));
    }

    /**
     * Load nested replies recursively
     */
    private function loadNestedReplies($replies, $level = 1)
    {
        if ($level > ForumReply::MAX_NESTING_LEVEL) {
            return;
        }

        foreach ($replies as $reply) {
            $children = ForumReply::where('parent_reply_id', $reply->id)
                ->with(['author', 'author.profile', 'attachments'])
                ->where('is_hidden', false)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($children->isNotEmpty()) {
                $reply->setRelation('replies', $children);
                $this->loadNestedReplies($children, $level + 1);
            }
        }
    }

    /**
     * Create new thread form
     */
    public function create()
    {
        // Guest check - redirect to login
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'Daftar untuk berpartisipasi di forum.');
        }

        $categories = ForumThread::getCategories();
        return view('forum.create', compact('categories'));
    }

    /**
     * Store new thread
     */
    public function store(Request $request)
    {
        // Guest check
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk membuat thread.');
        }

        $validated = $request->validate([
            'category' => 'required|in:' . implode(',', array_keys(ForumThread::getCategories())),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'nullable|image|max:5120', // max 5MB per image
        ]);

        DB::beginTransaction();
        try {
            // Create thread
            $thread = ForumThread::create([
                'category' => $validated['category'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'author_id' => Auth::id(),
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $this->processImageUpload($thread, $image);
                }
            }

            // Auto-follow created thread
            ThreadFollower::subscribe($thread->id, Auth::id());

            DB::commit();

            return redirect()->route('forum.threads.show', $thread)
                ->with('success', 'Thread berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal membuat thread: ' . $e->getMessage()]);
        }
    }

    /**
     * Process and resize image upload
     */
    private function processImageUpload($attachable, $file): void
    {
        // Validate file
        $error = ForumAttachment::validateFile($file);
        if ($error) {
            throw new \Exception($error);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = 'forum/' . date('Y/m/d');

        // Resize image using Intervention Image
        $resizedImage = Image::read($file)
            ->resize(ForumAttachment::RESIZE_WIDTH, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

        // Save resized image
        $resizedImage->save(storage_path('app/public/' . $path . '/' . $filename));
        $filePath = $path . '/' . $filename;

        // Get image dimensions
        $imgInfo = getimagesize(storage_path('app/public/' . $filePath));

        // Create attachment record
        ForumAttachment::create([
            'attachable_type' => get_class($attachable),
            'attachable_id' => $attachable->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'original_width' => $imgInfo[0] ?? null,
            'original_height' => $imgInfo[1] ?? null,
        ]);
    }

    /**
     * Store reply to thread
     */
    public function reply(Request $request, ForumThread $thread)
    {
        // Guest check
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk membalas.');
        }

        // Check if thread is locked
        if ($thread->is_locked) {
            return back()->withErrors(['error' => 'Thread ini telah dikunci.']);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_reply_id' => 'nullable|exists:forum_replies,id',
            'images.*' => 'nullable|image|max:5120',
        ]);

        // Spam detection
        $spamReason = ForumReply::detectSpam(Auth::id(), $validated['content']);
        if ($spamReason) {
            return back()->withErrors(['error' => 'Konten Anda terdeteksi sebagai spam. Silakan gunakan bahasa yang lebih variatif atau hindari link pendek.']);
        }

        // Check nesting level if replying to a reply
        $nestingLevel = 0;
        if ($validated['parent_reply_id']) {
            $parentReply = ForumReply::findOrFail($validated['parent_reply_id']);
            
            // Verify parent belongs to this thread
            if ($parentReply->thread_id !== $thread->id) {
                return back()->withErrors(['error' => 'Invalid parent reply.']);
            }

            // Check max nesting
            if (!$parentReply->canHaveReplies()) {
                return back()->withErrors(['error' => 'Maksimal kedalaman balasan adalah 3 level.']);
            }

            $nestingLevel = $parentReply->nesting_level + 1;
        }

        DB::beginTransaction();
        try {
            $reply = ForumReply::create([
                'thread_id' => $thread->id,
                'parent_reply_id' => $validated['parent_reply_id'] ?? null,
                'nesting_level' => $nestingLevel,
                'content' => $validated['content'],
                'author_id' => Auth::id(),
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $this->processImageUpload($reply, $image);
                }
            }

            // Fire event for notifications
            event(new \App\Events\ReplyCreated($reply));

            DB::commit();

            return redirect()->route('forum.threads.show', $thread)
                ->with('success', 'Balasan berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menambahkan balasan: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle follow status
     */
    public function toggleFollow(ForumThread $thread)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (ThreadFollower::isFollowing($thread->id, Auth::id())) {
            ThreadFollower::unsubscribe($thread->id, Auth::id());
            return response()->json(['following' => false]);
        } else {
            ThreadFollower::subscribe($thread->id, Auth::id());
            return response()->json(['following' => true]);
        }
    }

    /**
     * Hide reply (Admin/Instructor only)
     */
    public function hideReply(Request $request, ForumReply $reply)
    {
        if (!Auth::check() || !Auth::user()->isAdmin() && !Auth::user()->isInstructor()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $reply->update([
            'is_hidden' => true,
            'hide_reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Balasan berhasil disembunyikan.');
    }

    /**
     * Delete reply permanently (Admin only)
     */
    public function deleteReply(ForumReply $reply)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $reply->delete();

        return back()->with('success', 'Balasan berhasil dihapus.');
    }

    /**
     * Flag content for review (Admin only)
     */
    public function flagContent(Request $request, $type, $id)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($type === 'thread') {
            $model = ForumThread::findOrFail($id);
        } elseif ($type === 'reply') {
            $model = ForumReply::findOrFail($id);
        } else {
            abort(400, 'Invalid type');
        }

        $model->update([
            'is_flagged' => true,
            'flag_reason' => $validated['reason'],
            'flagged_at' => now(),
        ]);

        return back()->with('success', 'Konten ditandai untuk review.');
    }

    /**
     * Get unread notifications count (AJAX polling)
     */
    public function getNotificationCount()
    {
        if (!Auth::check()) {
            return response()->json(['count' => 0]);
        }

        $count = \App\Models\Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->where('type', 'forum_reply')
            ->count();

        return response()->json(['count' => $count]);
    }
}
