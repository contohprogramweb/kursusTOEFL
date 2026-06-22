<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Simulasi Ujian TOEFL') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Mode Selection -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Mode Ujian</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Practice Mode -->
                    <div class="border rounded-lg p-4 hover:bg-blue-50 cursor-pointer transition" 
                         onclick="selectMode('practice')">
                        <div class="flex items-center mb-2">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Practice Mode</h4>
                        <p class="text-sm text-gray-600 mt-1">Pause, resume, cancel kapan saja</p>
                        <ul class="text-xs text-gray-500 mt-2 space-y-1">
                            <li>✓ Bisa pause & resume</li>
                            <li>✓ Timer fleksibel</li>
                            <li>✓ Cocok untuk latihan</li>
                        </ul>
                    </div>

                    <!-- Scheduled Mode -->
                    <div class="border rounded-lg p-4 hover:bg-green-50 cursor-pointer transition" 
                         onclick="selectMode('scheduled')">
                        <div class="flex items-center mb-2">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Scheduled Mode</h4>
                        <p class="text-sm text-gray-600 mt-1">Timer tetap berjalan</p>
                        <ul class="text-xs text-gray-500 mt-2 space-y-1">
                            <li>✓ Timer terus berjalan</li>
                            <li>✓ Auto-abandon jika ditinggal</li>
                            <li>✓ Simulasi ujian terjadwal</li>
                        </ul>
                    </div>

                    <!-- Realistic Mode -->
                    <div class="border rounded-lg p-4 hover:bg-purple-50 cursor-pointer transition" 
                         onclick="selectMode('realistic')">
                        <div class="flex items-center mb-2">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Realistic Test Day</h4>
                        <p class="text-sm text-gray-600 mt-1">Full screen + kamera check-in</p>
                        <ul class="text-xs text-gray-500 mt-2 space-y-1">
                            <li>✓ Fullscreen wajib</li>
                            <li>✓ Kamera check-in (WebRTC)</li>
                            <li>✓ No distraction mode</li>
                        </ul>
                    </div>

                    <!-- Focus Mode -->
                    <div class="border rounded-lg p-4 hover:bg-orange-50 cursor-pointer transition" 
                         onclick="selectMode('focus')">
                        <div class="flex items-center mb-2">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Focus Mode</h4>
                        <p class="text-sm text-gray-600 mt-1">Fullscreen, minimal distraction</p>
                        <ul class="text-xs text-gray-500 mt-2 space-y-1">
                            <li>✓ Fullscreen otomatis</li>
                            <li>✓ Sembunyikan UI non-esensial</li>
                            <li>✓ Fokus maksimal</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Available Templates -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Template Ujian Tersedia</h3>
                
                @if($templates->count() > 0)
                    <div class="space-y-4">
                        @foreach($templates as $template)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $template->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                                        <div class="flex items-center gap-4 mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ ucfirst($template->mode) }}
                                            </span>
                                            <span class="text-sm text-gray-500">{{ $template->total_duration }} menit</span>
                                            <span class="text-sm text-gray-500">{{ $template->sections->count() }} sections</span>
                                        </div>
                                    </div>
                                    <form action="{{ route('simulations.start', $template->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="{{ $template->mode }}">
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Mulai
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Sections Preview -->
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($template->sections as $section)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($section->section) }} ({{ $section->duration_minutes }} min)
                                            @if($section->hasBreak())
                                                <span class="ml-1 text-gray-500">+ break</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">Belum ada template ujian tersedia.</p>
                @endif
            </div>

            <!-- In Progress Simulations -->
            @if($inProgress->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ujian Sedang Berlangsung</h3>
                    
                    <div class="space-y-4">
                        @foreach($inProgress as $simulation)
                            <div class="border rounded-lg p-4 bg-yellow-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $simulation->template->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">Status: <span class="font-medium">{{ ucfirst($simulation->status) }}</span></p>
                                        <p class="text-sm text-gray-500 mt-1">Dimulai: {{ $simulation->start_time->format('d M Y, H:i') }}</p>
                                    </div>
                                    <a href="{{ route('simulations.resume', $simulation->id) }}" class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Lanjutkan
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Completed Simulations -->
            @if($completed->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Riwayat Ujian</h3>
                    
                    <div class="space-y-4">
                        @foreach($completed as $simulation)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $simulation->template->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">Selesai: {{ $simulation->end_time->format('d M Y, H:i') }}</p>
                                    </div>
                                    <a href="{{ route('simulations.results.show', $simulation->id) }}" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Lihat Hasil
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
    function selectMode(mode) {
        // Scroll to templates section
        const templatesSection = document.querySelector('.bg-white.overflow-hidden.shadow-sm.sm\\:rounded-lg:nth-child(2)');
        if (templatesSection) {
            templatesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Highlight selected mode
        document.querySelectorAll('[onclick^="selectMode"]').forEach(el => {
            el.classList.remove('ring-2', 'ring-indigo-500');
        });
        event.currentTarget.classList.add('ring-2', 'ring-indigo-500');
        
        // Store selected mode in sessionStorage
        sessionStorage.setItem('selectedMode', mode);
    }

    // Check if a mode was previously selected
    document.addEventListener('DOMContentLoaded', function() {
        const savedMode = sessionStorage.getItem('selectedMode');
        if (savedMode) {
            const modeElement = document.querySelector(`[onclick="selectMode('${savedMode}')"]`);
            if (modeElement) {
                modeElement.classList.add('ring-2', 'ring-indigo-500');
            }
        }
    });
    </script>
</x-app-layout>
