@extends('layouts.app')

@section('title', 'Rekomendasi Belajar')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rekomendasi Belajar</h1>
                <p class="text-gray-600 mt-2">Rekomendasi personal berdasarkan performa simulasi Anda</p>
            </div>
            <div class="flex gap-3">
                <button onclick="generateFromLatest()" 
                        class="btn-primary inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Generate dari Simulasi Terbaru
                </button>
                <button onclick="markAllAsRead()" 
                        class="btn-secondary inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tandai Semua Dibaca
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4">
            <select id="filterCategory" onchange="applyFilters()" class="form-select">
                <option value="">Semua Kategori</option>
                <option value="reading">Reading</option>
                <option value="listening">Listening</option>
                <option value="structure">Structure</option>
                <option value="writing">Writing</option>
                <option value="time_management">Manajemen Waktu</option>
                <option value="schedule">Jadwal</option>
                <option value="general">Umum</option>
            </select>
            
            <label class="flex items-center gap-2">
                <input type="checkbox" id="filterUnread" onchange="applyFilters()" class="form-checkbox">
                <span class="text-sm text-gray-700">Hanya yang belum dibaca</span>
            </label>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Rekomendasi</div>
            <div class="text-2xl font-bold text-gray-900">{{ $recommendations->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Belum Dibaca</div>
            <div class="text-2xl font-bold text-blue-600">{{ $recommendations->where('is_read', false)->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Sangat Mendesak</div>
            <div class="text-2xl font-bold text-red-600">{{ $recommendations->where('urgency_factor', 5)->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Impact Tinggi</div>
            <div class="text-2xl font-bold text-green-600">{{ $recommendations->where('impact_score', '>=', 70)->count() }}</div>
        </div>
    </div>

    <!-- Recommendations List -->
    @if($recommendations->count() > 0)
        <div class="space-y-4">
            @foreach($recommendations as $recommendation)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200 {{ $recommendation->is_read ? 'opacity-75' : '' }}">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">{{ $recommendation->icon }}</span>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $recommendation->title }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs px-2 py-1 rounded-full bg-{{ $recommendation->urgency_color }}-100 text-{{ $recommendation->urgency_color }}-800">
                                            {{ $recommendation->urgency_label }}
                                        </span>
                                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800 capitalize">
                                            {{ $recommendation->type }}
                                        </span>
                                        @if($recommendation->category)
                                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800 capitalize">
                                                {{ str_replace('_', ' ', $recommendation->category) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if(!$recommendation->is_read)
                                <button onclick="markAsRead({{ $recommendation->id }})" 
                                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    ✓ Tandai Dibaca
                                </button>
                            @endif
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <p class="text-gray-700">{{ $recommendation->reason }}</p>
                        </div>

                        <!-- Action Plan -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Rencana Aksi
                            </h4>
                            <pre class="whitespace-pre-wrap text-sm text-gray-700">{{ $recommendation->action_plan }}</pre>
                        </div>

                        <!-- Metadata -->
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <div class="flex items-center gap-4">
                                <span>Impact Score: <strong class="text-gray-900">{{ $recommendation->impact_score }}</strong></span>
                                @if($recommendation->micro_skill)
                                    <span>Micro-skill: <strong class="text-gray-900">{{ str_replace('_', ' ', $recommendation->micro_skill) }}</strong></span>
                                @endif
                            </div>
                            <span>Dibuat {{ $recommendation->generated_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada rekomendasi</h3>
            <p class="text-gray-600 mb-4">Kerjakan simulasi untuk mendapatkan rekomendasi personal.</p>
            <button onclick="generateFromLatest()" class="btn-primary">
                Generate dari Simulasi
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
function generateFromLatest() {
    fetch('{{ route("api.recommendations.generate-latest") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat generate rekomendasi.');
    });
}

function markAsRead(id) {
    fetch(`/api/recommendations/${id}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function markAllAsRead() {
    fetch('{{ route("api.recommendations.mark-all-read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function applyFilters() {
    const category = document.getElementById('filterCategory').value;
    const unread = document.getElementById('filterUnread').checked;
    
    let url = '{{ route("recommendations.index") }}?';
    if (category) url += `category=${category}&`;
    if (unread) url += `unread=true&`;
    
    window.location.href = url;
}
</script>
@endpush
@endsection
