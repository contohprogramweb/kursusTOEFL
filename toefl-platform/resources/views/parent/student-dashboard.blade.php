@extends('layouts.app')

@section('title', 'Dashboard Parent-Student')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
            Dashboard Parent-Student
        </h1>

        @if(auth()->user()->isStudent())
            <!-- Student Dashboard -->
            <div class="grid gap-6 md:grid-cols-2 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Generate Kode Undangan</h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Bagikan kode ini dengan orang tua Anda agar mereka dapat memantau perkembangan belajar Anda.
                    </p>
                    <a href="{{ route('parent.code.generate.page') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Generate Kode
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Permintaan Pending</h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Anda memiliki {{ $pendingLinks->count() }} permintaan tautan yang menunggu persetujuan.
                    </p>
                    <a href="{{ route('parent.pending') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Lihat Permintaan
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Orang Tua Terhubung</h2>
                @if($activeLinks->count() > 0)
                    <div class="space-y-4">
                        @foreach($activeLinks as $link)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-gray-600 dark:text-gray-300 font-medium">{{ substr($link->parent->full_name ?? $link->parent->email, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $link->parent->full_name ?? $link->parent->email }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link->parent->email }}</p>
                                    </div>
                                </div>
                                <button onclick="revokeLink({{ $link->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 text-sm font-medium">
                                    Cabut Akses
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">Belum ada orang tua yang terhubung.</p>
                @endif
            </div>
        @elseif(auth()->user()->isParent())
            <!-- Parent Dashboard -->
            <div class="grid gap-6 md:grid-cols-2 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Tambah Anak</h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Masukkan kode undangan dari anak Anda untuk mulai memantau perkembangan mereka.
                    </p>
                    <a href="{{ route('parent.code.submit') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Masukkan Kode
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Anak Saya</h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Anda terhubung dengan {{ $childrenCount }}/5 anak.
                    </p>
                    <a href="{{ route('parent.children') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                        Lihat Daftar Anak
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Anak Terhubung</h2>
                @if($activeLinks->count() > 0)
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($activeLinks as $link)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center mb-3">
                                    <div class="h-12 w-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-gray-600 dark:text-gray-300 font-medium text-lg">{{ substr($link->student->full_name ?? $link->student->email, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $link->student->full_name ?? $link->student->email }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $link->student->email }}</p>
                                    </div>
                                </div>
                                <a href="/parent/children/{{ $link->student->id }}/progress" class="block text-center bg-blue-100 dark:bg-blue-900 hover:bg-blue-200 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 text-sm font-medium py-2 px-4 rounded transition duration-200">
                                    Lihat Perkembangan
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">Belum ada anak yang terhubung.</p>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
function revokeLink(linkId) {
    if (!confirm('Apakah Anda yakin ingin mencabut akses orang tua ini? Mereka tidak akan bisa lagi melihat perkembangan belajar Anda.')) {
        return;
    }

    fetch(`/parent/links/${linkId}/revoke`, {
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
            alert('Akses berhasil dicabut!');
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>
@endsection
