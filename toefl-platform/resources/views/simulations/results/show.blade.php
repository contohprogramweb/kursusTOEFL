<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Hasil Ujian') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Results Container -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Hasil Ujian: {{ $simulation->template->name }}</h3>
                
                <!-- Score Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Total Score</p>
                        <p id="result-score" class="text-3xl font-bold text-indigo-600">
                            {{ $simulation->overall_score ?? 'Pending' }}
                        </p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Reading</p>
                        <p id="result-reading" class="text-2xl font-bold text-green-600">
                            @php
                                $readingResult = $simulation->sectionResults->where('section', 'reading')->first();
                            @endphp
                            {{ $readingResult?->score ?? 'Pending' }}
                        </p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Listening</p>
                        <p id="result-listening" class="text-2xl font-bold text-blue-600">
                            @php
                                $listeningResult = $simulation->sectionResults->where('section', 'listening')->first();
                            @endphp
                            {{ $listeningResult?->score ?? 'Pending' }}
                        </p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Status</p>
                        <p id="result-status" class="text-lg font-bold text-purple-600">
                            {{ ucfirst($simulation->status) }}
                        </p>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Tanggal Ujian</p>
                        <p class="font-semibold">{{ $simulation->start_time->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Durasi Total</p>
                        <p class="font-semibold">{{ $simulation->template->total_duration }} menit</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Mode</p>
                        <p class="font-semibold">{{ ucfirst($simulation->mode) }}</p>
                    </div>
                </div>

                <!-- Section Details -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Detail per Section</h4>
                    
                    <div class="space-y-4">
                        @foreach($simulation->sectionResults as $sectionResult)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h5 class="font-semibold text-gray-900">{{ ucfirst($sectionResult->section) }}</h5>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Status: <span class="font-medium">{{ ucfirst($sectionResult->status) }}</span>
                                        </p>
                                        @if($sectionResult->score)
                                            <p class="text-sm text-gray-600">
                                                Score: <span class="font-medium text-indigo-600">{{ $sectionResult->score }}</span>
                                            </p>
                                        @endif
                                    </div>
                                    <a href="{{ route('simulations.results.section', [$simulation->id, 'section' => $sectionResult->section]) }}" 
                                       class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Lihat Detail
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex gap-4">
                    <a href="{{ route('simulations.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        ← Kembali ke Dashboard
                    </a>
                    
                    @if($simulation->status === 'completed')
                        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            📄 Print Results
                        </button>
                    @endif
                </div>
            </div>

            @if($simulation->status === 'grading')
                <!-- Grading Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Ujian Anda sedang dalam proses penilaian. Hasil akan tersedia setelah semua section dinilai.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
