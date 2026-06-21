@extends('layouts.app')

@section('title', 'Permintaan Pending')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
            Permintaan Tautan Pending
        </h1>

        @if($pendingLinks->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Orang Tua
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tanggal Permintaan
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pendingLinks as $link)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            @if($link->parent->profile?->avatar_url)
                                                <img class="h-10 w-10 rounded-full" src="{{ $link->parent->profile->avatar_url }}" alt="">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                    <span class="text-gray-600 dark:text-gray-300 font-medium">{{ substr($link->parent->full_name ?? $link->parent->email, 0, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $link->parent->full_name ?? $link->parent->email }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $link->parent->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $link->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="approveLink({{ $link->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-4">
                                        Setujui
                                    </button>
                                    <button onclick="rejectLink({{ $link->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Tolak
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada permintaan pending</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ketika ada orang tua yang meminta tautan, permintaan akan muncul di sini.
                </p>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('parent.dashboard') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<script>
function approveLink(linkId) {
    if (!confirm('Apakah Anda yakin ingin menyetujui permintaan tautan ini?')) {
        return;
    }

    fetch(`/parent/links/${linkId}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Tautan berhasil disetujui!');
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}

function rejectLink(linkId) {
    if (!confirm('Apakah Anda yakin ingin menolak permintaan tautan ini?')) {
        return;
    }

    // Untuk saat ini, kita hanya refresh halaman
    // Implementasi reject bisa ditambahkan sesuai kebutuhan
    alert('Fitur penolakan akan segera tersedia.');
}
</script>
@endsection
