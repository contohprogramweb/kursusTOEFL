@extends('layouts.app')

@section('title', 'Dashboard Parent')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
            Dashboard Orang Tua
        </h1>

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
    </div>
</div>
@endsection
