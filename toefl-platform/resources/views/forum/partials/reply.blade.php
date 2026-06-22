<div class="bg-white rounded-lg shadow-md p-4 {{ $level > 0 ? 'ml-8 border-l-2 border-gray-200' : '' }}" id="reply-{{ $reply->id }}">
    <div class="flex items-start justify-between mb-2">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-gray-800">{{ $reply->author->full_name ?? $reply->author->email }}</span>
            <span class="text-xs text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
            @if($level > 0)
                <span class="text-xs bg-gray-100 px-2 py-1 rounded">Level {{ $level }}</span>
            @endif
        </div>
        
        @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isInstructor()))
            <div class="flex gap-2">
                <button onclick="toggleHideForm({{ $reply->id }})" 
                        class="text-yellow-600 hover:text-yellow-800 text-sm">
                    🚩 Sembunyikan
                </button>
                @if(Auth::user()->isAdmin())
                    <form action="{{ route('forum.replies.delete', $reply) }}" method="POST" class="inline"
                          onsubmit="return confirm('Yakin ingin menghapus balasan ini secara permanen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                            🗑️ Hapus
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="prose max-w-none text-gray-700 mb-3">
        {!! nl2br(e($reply->content)) !!}
    </div>

    <!-- Attachments -->
    @if($reply->attachments->count() > 0)
        <div class="mb-3 pt-3 border-t flex flex-wrap gap-2">
            @foreach($reply->attachments as $attachment)
                @if($attachment->isImage())
                    <a href="{{ $attachment->url }}" target="_blank">
                        <img src="{{ $attachment->url }}" alt="{{ $attachment->file_name }}" 
                             class="w-24 h-24 object-cover rounded hover:opacity-80">
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    <!-- Reply Actions -->
    @auth
        @if($reply->canHaveReplies())
            <button onclick="replyTo({{ $reply->id }}, {{ $level + 1 }})" 
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                ↩️ Balas
            </button>
        @else
            <span class="text-gray-400 text-sm">Maksimal kedalaman balasan tercapai</span>
        @endif
    @endauth

    <!-- Hide Form (Admin/Instructor) -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isInstructor()))
        <form id="hide-form-{{ $reply->id }}" action="{{ route('forum.replies.hide', $reply) }}" 
              method="POST" class="hidden mt-3 p-3 bg-yellow-50 rounded">
            @csrf
            <input type="text" name="reason" placeholder="Alasan menyembunyikan..." required
                   class="w-full px-3 py-2 border border-yellow-300 rounded mb-2">
            <div class="flex gap-2">
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm">
                    Sembunyikan
                </button>
                <button type="button" onclick="toggleHideForm({{ $reply->id }})" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-1 rounded text-sm">
                    Batal
                </button>
            </div>
        </form>
    @endif

    <!-- Nested Replies -->
    @if(isset($reply->replies) && $reply->replies->count() > 0)
        <div class="mt-4 space-y-4">
            @foreach($reply->replies as $childReply)
                @include('forum.partials.reply', ['reply' => $childReply, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

<script>
function toggleHideForm(replyId) {
    const form = document.getElementById('hide-form-' + replyId);
    form.classList.toggle('hidden');
}
</script>
