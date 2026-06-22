<?php

namespace App\Listeners;

use App\Events\ReplyCreated;
use App\Models\Notification;
use App\Models\ThreadFollower;
use App\Models\User;

class SendReplyNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ReplyCreated $event): void
    {
        $reply = $event->reply;
        $thread = $reply->thread;

        // Get all followers of the thread (excluding the reply author)
        $followers = ThreadFollower::where('thread_id', $thread->id)
            ->where('is_following', true)
            ->where('user_id', '!=', $reply->author_id)
            ->get();

        foreach ($followers as $follower) {
            // Create in-app notification
            Notification::create([
                'user_id' => $follower->user_id,
                'type' => 'forum_reply',
                'title' => 'Balasan Baru di Forum',
                'message' => sprintf(
                    '%s membalas thread "%s"',
                    $reply->author->full_name ?? $reply->author->email,
                    $thread->title
                ),
                'channel' => 'in-app',
                'status' => 'pending',
                'action_url' => route('forum.threads.show', ['thread' => $thread->id]) . '#reply-' . $reply->id,
            ]);
        }

        // Also notify thread author if they're not already in followers list
        if ($thread->author_id !== $reply->author_id) {
            Notification::create([
                'user_id' => $thread->author_id,
                'type' => 'forum_reply',
                'title' => 'Balasan Baru di Thread Anda',
                'message' => sprintf(
                    '%s membalas thread "%s"',
                    $reply->author->full_name ?? $reply->author->email,
                    $thread->title
                ),
                'channel' => 'in-app',
                'status' => 'pending',
                'action_url' => route('forum.threads.show', ['thread' => $thread->id]) . '#reply-' . $reply->id,
            ]);
        }
    }
}
