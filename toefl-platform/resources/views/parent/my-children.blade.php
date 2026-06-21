@extends('layouts.app')

@section('title', 'Anak Saya')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Anak Saya ({{ $childrenCount }}/5)
            </h1>
            <a href="{{ route('parent.code.submit') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                + Tambah Anak
            </a>
        </div>

        @if($activeLinks->count() > 0)
            <div class="grid gap-6 md:grid-cols-2">
                @foreach($activeLinks as $link)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 h-16 w-16">
                                @if($link->student->profile?->avatar_url)
                                    <img class="h-16 w-16 rounded-full" src="{{ $link->student->profile->avatar_url }}" alt="">
                                @else
                                    <div class="h-16 w-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-gray-600 dark:text-gray-300 font-medium text-xl">{{ substr($link->student->full_name ?? $link->student->email, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $link->student->full_name ?? $link->student->email }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $link->student->email }}
                                </p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                    Terhubung sejak {{ $link->created_at->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <a href="/parent/children/{{ $link->student->id }}/progress" class="block text-center bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-4 rounded-lg transition duration-200">
                                Lihat Perkembangan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum ada anak yang terhubung</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Minta anak Anda untuk generate kode undangan dan masukkan kode tersebut di sini.
                </p>
                <div class="mt-6">
                    <a href="{{ route('parent.code.submit') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Anak Pertama
                    </a>
                </div>
            </div>
        @endif

        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Cara Menghubungkan:</h2>
            <ol class="list-decimal list-inside space-y-2 text-gray-600 dark:text-gray-300">
                <li>Minta anak Anda untuk login ke akun mereka.</li>
                <li>Anak Anda generate kode undangan dari menu "Hubungkan dengan Orang Tua".</li>
                <li>Anak Anda membagikan kode 6 digit kepada Anda.</li>
                <li>Anda masukkan kode tersebut di halaman ini.</li>
                <li>Anak Anda menyetujui permintaan tautan.</li>
                <li>Anda dapat mulai memantau perkembangan belajar anak!</li>
            </ol>
        </div>

        <div class="mt-6">
            <a href="{{ route('parent.dashboard') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
