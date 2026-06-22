@extends('layouts.app')

@section('title', $thread->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Back Button -->
    <a href="{{ route('forum.index') }}" class="text-blue-600 hover:underline mb-4 inline-block">
        ← Kembali ke Forum
    </a>

    <!-- Thread Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center gap-2 mb-3">
            @if($thread->is_pinned)
                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">📌 Pinned</span>
            @endif
            @if($thread->is_locked)
                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">🔒 Locked</span>
            @endif
            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                {{ \App\Models\ForumThread::getCategories()[$thread->category] ?? $thread->category }}
            </span>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $thread->title }}</h1>

        <div class="flex items-center gap-4 text-sm text-gray-500 mb-4 pb-4 border-b">
            <span class="flex items-center gap-1">
                👤 {{ $thread->author->full_name ?? $thread->author->email }}
            </span>
            <span>🕐 {{ $thread->created_at->diffForHumans() }}</span>
            <span>👁️ {{ $thread->view_count }} views</span>
        </div>

        <div class="prose max-w-none mb-4">
            {!! nl2br(e($thread->content)) !!}
        </div>

        <!-- Thread Attachments -->
        @if($thread->attachments->count() > 0)
            <div class="mt-4 pt-4 border-t">
                <h4 class="font-semibold mb-2">Lampiran:</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($thread->attachments as $attachment)
                        @if($attachment->isImage())
                            <a href="{{ $attachment->url }}" target="_blank" class="block">
                                <img src="{{ $attachment->url }}" alt="{{ $attachment->file_name }}" 
                                     class="w-32 h-32 object-cover rounded-lg hover:opacity-80">
                            </a>
                        @else
                            <a href="{{ $attachment->url }}" class="text-blue-600 hover:underline">
                                📎 {{ $attachment->file_name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Follow Button -->
        @auth
            <div class="mt-4 pt-4 border-t flex items-center gap-4">
                <button id="follow-btn" 
                        data-thread-id="{{ $thread->id }}"
                        class="px-4 py-2 rounded-lg transition-colors {{ $isFollowing ? 'bg-gray-200 text-gray-700' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                    {{ $isFollowing ? '✓ Mengikuti' : '🔔 Ikuti Thread' }}
                </button>
                <span id="follow-status" class="text-sm text-gray-500"></span>
            </div>
        @endauth
    </div>

    <!-- Replies Section -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            💬 {{ $thread->replies->count() }} Balasan
        </h2>

        <div class="space-y-4">
            @foreach($rootReplies as $reply)
                @include('forum.partials.reply', ['reply' => $reply, 'level' => 0])
            @endforeach
        </div>
    </div>

    <!-- Reply Form -->
    @auth
        @if(!$thread->is_locked)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Buat Balasan</h3>
                
                <form action="{{ route('forum.threads.reply', $thread) }}" method="POST" enctype="multipart/form-data" id="reply-form">
                    @csrf
                    <input type="hidden" name="parent_reply_id" id="parent-reply-id">
                    
                    <div class="mb-4">
                        <textarea name="content" id="content" rows="5" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Tulis balasan Anda... (mendukung format rich text sederhana)"
                                  required></textarea>
                    </div>

                    <!-- Rich Text Toolbar -->
                    <div class="mb-4 flex gap-2">
                        <button type="button" onclick="formatText('bold')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                            <b>B</b>
                        </button>
                        <button type="button" onclick="formatText('italic')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                            <i>I</i>
                        </button>
                        <button type="button" onclick="formatText('bullet')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                            • List
                        </button>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Gambar (max 5MB, auto-resize 800px)
                        </label>
                        <input type="file" name="images[]" multiple accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Kirim Balasan
                    </button>
                </form>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <p class="text-red-700">🔒 Thread ini telah dikunci dan tidak dapat dibalas.</p>
            </div>
        @endif
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <p class="text-blue-700 mb-2">💬 Ingin berpartisipasi dalam diskusi?</p>
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-semibold">
                Login untuk membalas
            </a>
        </div>
    @endauth
</div>

<script>
// Follow/Unfollow functionality
document.getElementById('follow-btn')?.addEventListener('click', function() {
    const threadId = this.dataset.threadId;
    
    fetch(`/forum/threads/${threadId}/toggle-follow`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.following) {
            this.textContent = '✓ Mengikuti';
            this.className = 'px-4 py-2 rounded-lg transition-colors bg-gray-200 text-gray-700';
        } else {
            this.textContent = '🔔 Ikuti Thread';
            this.className = 'px-4 py-2 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700';
        }
    });
});

// Rich text formatting
function formatText(format) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selectedText = text.substring(start, end);
    
    let before = text.substring(0, start);
    let after = text.substring(end);
    let formatted = '';
    
    switch(format) {
        case 'bold':
            formatted = `**${selectedText}**`;
            break;
        case 'italic':
            formatted = `*${selectedText}*`;
            break;
        case 'bullet':
            formatted = `• ${selectedText}`;
            break;
    }
    
    textarea.value = before + formatted + after;
    textarea.focus();
}

// Reply to specific reply
function replyTo(replyId, level) {
    if (level >= 3) {
        alert('Maksimal kedalaman balasan adalah 3 level.');
        return;
    }
    
    document.getElementById('parent-reply-id').value = replyId;
    document.getElementById('content').focus();
    document.getElementById('content').placeholder = 'Membalas komentar...';
    
    // Scroll to form
    document.getElementById('reply-form').scrollIntoView({ behavior: 'smooth' });
}
</script>
@endsection
