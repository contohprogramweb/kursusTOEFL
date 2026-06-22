<?php

namespace App\Events;

use App\Models\ForumReply;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReplyCreated
{
    use Dispatchable, SerializesModels;

    public ForumReply $reply;

    /**
     * Create a new event instance.
     */
    public function __construct(ForumReply $reply)
    {
        $this->reply = $reply;
    }
}
