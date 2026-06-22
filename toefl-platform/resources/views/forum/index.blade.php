@extends('layouts.app')

@section('title', 'Forum Diskusi')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Forum Diskusi</h1>
        @auth
            <a href="{{ route('forum.threads.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                + Buat Thread Baru
            </a>
        @else
            <a href="{{ route('login') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                Login untuk Berpartisipasi
            </a>
        @endauth
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form action="{{ route('forum.index') }}" method="GET" class="flex gap-4">
            <input type="text" name="search" value="{{ $search }}" 
                   placeholder="Cari thread..." 
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            
            <select name="category" onchange="this.form.submit()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all" {{ $category === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Categories Quick Links -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('forum.index', ['category' => 'all']) }}" 
           class="px-3 py-1 rounded-full text-sm {{ $category === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Semua
        </a>
        @foreach($categories as $key => $label)
            <a href="{{ route('forum.index', ['category' => $key]) }}" 
               class="px-3 py-1 rounded-full text-sm {{ $category === $key ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Thread List -->
    <div class="space-y-4">
        @forelse($threads as $thread)
            <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow {{ $thread->is_pinned ? 'border-l-4 border-yellow-500' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            @if($thread->is_pinned)
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">📌 Pinned</span>
                            @endif
                            @if($thread->is_locked)
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">🔒 Locked</span>
                            @endif
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                {{ $categories[$thread->category] ?? $thread->category }}
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">
                            <a href="{{ route('forum.threads.show', $thread) }}" class="hover:text-blue-600">
                                {{ $thread->title }}
                            </a>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ Str::limit(strip_tags($thread->content), 150) }}</p>
                        
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                👤 {{ $thread->author->full_name ?? $thread->author->email }}
                            </span>
                            <span>💬 {{ $thread->replies_count }} balasan</span>
                            <span>👁️ {{ $thread->view_count }} views</span>
                            <span>🕐 {{ $thread->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <p class="text-gray-500 text-lg">Belum ada thread di kategori ini.</p>
                @auth
                    <a href="{{ route('forum.threads.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">
                        Jadilah yang pertama membuat thread!
                    </a>
                @endauth
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $threads->links() }}
    </div>
</div>

<!-- Notification Polling Script -->
@auth
<script>
// Polling for new notifications every 30 seconds
setInterval(function() {
    fetch('{{ route("forum.notifications.count") }}')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                // Update notification badge
                const badge = document.querySelector('#notification-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.classList.remove('hidden');
                }
                // Show toast notification
                showToast(`Anda memiliki ${data.count} notifikasi baru`);
            }
        });
}, 30000);

function showToast(message) {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
</script>
@endauth
@endsection
