<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" id="exam-title">
            {{ $simulation->template->name }}
        </h2>
    </x-slot>

    <div class="py-6" id="exam-container">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Camera Check-in Modal (Realistic Mode Only) -->
            <div id="camera-checkin-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Camera Check-in</h3>
                        <p class="text-sm text-gray-500 mb-4">Pastikan wajah Anda terlihat jelas di kamera</p>
                        <video id="checkin-video" autoplay playsinline class="w-full rounded-lg mb-4"></video>
                        <canvas id="checkin-canvas" class="hidden"></canvas>
                        <button id="btn-confirm-checkin" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full">
                            Konfirmasi & Mulai Ujian
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fullscreen Warning Modal -->
            <div id="fullscreen-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Fullscreen Required</h3>
                        <p class="text-sm text-gray-500 mb-4">Mode ini memerlukan fullscreen. Klik tombol di bawah untuk masuk ke mode fullscreen.</p>
                        <button id="btn-enter-fullscreen" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 w-full">
                            Enter Fullscreen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Break Screen -->
            <div id="break-screen" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Break Time!</h2>
                <p class="text-gray-600 mb-4">Anda memiliki waktu istirahat 10 menit sebelum section Speaking.</p>
                <div id="break-timer" class="text-4xl font-bold text-indigo-600 mb-6">10:00</div>
                <button id="btn-end-break" class="px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Lanjutkan ke Speaking Section
                </button>
                <p class="text-xs text-gray-500 mt-4">Ujian akan otomatis lanjut ketika break time habis</p>
            </div>

            <!-- Main Exam Interface -->
            <div id="exam-interface" class="hidden">
                <!-- Top Bar: Timer, Section Info, Controls -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <!-- Left: Section Info -->
                        <div class="flex items-center gap-4">
                            <div>
                                <span class="text-xs text-gray-500 uppercase">Section</span>
                                <p id="current-section-name" class="font-semibold text-gray-900">Loading...</p>
                            </div>
                            <span id="section-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Reading
                            </span>
                        </div>

                        <!-- Center: Timer -->
                        <div class="text-center">
                            <span class="text-xs text-gray-500 uppercase">Time Remaining</span>
                            <div id="timer-display" class="text-3xl font-bold text-indigo-600">00:00</div>
                        </div>

                        <!-- Right: Controls -->
                        <div class="flex items-center gap-2">
                            <!-- Pause Button (Practice Mode Only) -->
                            <button id="btn-pause" class="hidden px-3 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 text-sm" title="Pause">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            
                            <!-- Flag for Review -->
                            <button id="btn-flag" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm" title="Flag for Review">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 012-2h10a2 2 0 012 2v8m2-2a2 2 0 100-4m0 4a2 2 0 110 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110 4m0-4v2m0-6V4"></path>
                                </svg>
                            </button>

                            <!-- Review Flagged -->
                            <button id="btn-review-flagged" class="px-3 py-2 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-sm" title="Review Flagged Questions">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </button>

                            <!-- Exit Fullscreen -->
                            <button id="btn-exit-fullscreen" class="hidden px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm" title="Exit Fullscreen">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 1v8l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 1v8l5.25 5.25"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Progress</span>
                        <span id="progress-text" class="text-sm font-medium text-gray-700">1 / 10</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 10%"></div>
                    </div>
                </div>

                <!-- Question Navigation Grid (for Review) -->
                <div id="question-nav-grid" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-gray-900 mb-3">Question Navigator</h4>
                    <div id="question-numbers" class="grid grid-cols-10 gap-2">
                        <!-- Question numbers will be dynamically inserted -->
                    </div>
                    <div class="mt-3 flex gap-4 text-xs text-gray-600">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 bg-indigo-600 rounded"></div>
                            <span>Answered</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 bg-yellow-400 rounded"></div>
                            <span>Flagged</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 bg-gray-200 rounded"></div>
                            <span>Not Answered</span>
                        </div>
                    </div>
                </div>

                <!-- Question Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <!-- Passage/Context Area -->
                    <div id="passage-container" class="hidden mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-semibold text-gray-700">Passage / Context</h4>
                            <button id="btn-split-view" class="text-xs text-indigo-600 hover:text-indigo-800">Split View</button>
                        </div>
                        <div id="passage-content" class="p-4 bg-gray-50 rounded-lg border-l-4 border-indigo-500 max-h-96 overflow-y-auto">
                            <!-- Passage text will be inserted here -->
                        </div>
                    </div>

                    <!-- Audio Player (Listening Section) -->
                    <div id="audio-container" class="hidden mb-6">
                        <h4 class="font-semibold text-gray-700 mb-2">Audio</h4>
                        <audio id="audio-player" controls class="w-full">
                            <source id="audio-source" src="" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>

                    <!-- Image (if any) -->
                    <div id="image-container" class="hidden mb-6 text-center">
                        <img id="question-image" src="" alt="Question Image" class="max-w-full h-auto max-h-96 mx-auto rounded-lg">
                    </div>

                    <!-- Question Text -->
                    <div class="mb-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 id="question-number" class="text-lg font-semibold text-gray-800">Question 1</h3>
                            <button id="btn-flag-question" class="text-sm text-yellow-600 hover:text-yellow-800 hidden">
                                <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z"></path>
                                </svg>
                                Flagged
                            </button>
                        </div>
                        <p id="question-text" class="text-gray-800 text-lg"></p>
                    </div>

                    <!-- Answer Area -->
                    <div id="answer-area" class="mt-6">
                        <!-- Multiple Choice Options -->
                        <div id="multiple-choice-options" class="hidden space-y-3">
                            <!-- Options will be dynamically inserted here -->
                        </div>

                        <!-- Text Input (for Speaking/Writing) -->
                        <div id="text-input-container" class="hidden">
                            <textarea id="text-answer" rows="8" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ketik jawaban Anda di sini..."></textarea>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-sm text-gray-500">Jawaban disimpan otomatis setiap 30 detik</p>
                                <span id="char-count" class="text-sm text-gray-500">0 characters</span>
                            </div>
                        </div>

                        <!-- Recording Interface (Speaking) -->
                        <div id="recording-container" class="hidden text-center">
                            <div id="recording-status" class="mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <span class="w-2 h-2 bg-red-600 rounded-full mr-2 animate-pulse"></span>
                                    Recording...
                                </span>
                            </div>
                            <div id="recording-timer" class="text-2xl font-bold text-gray-900 mb-4">00:00</div>
                            <button id="btn-stop-recording" class="px-6 py-3 bg-red-600 text-white rounded-full hover:bg-red-700">
                                <svg class="w-6 h-6 inline" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path>
                                </svg>
                                Stop Recording
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <button id="btn-previous" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            ← Previous
                        </button>

                        <div class="flex gap-2">
                            <button id="btn-cancel" class="hidden px-4 py-2 bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </button>
                            
                            <button id="btn-next" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Next →
                            </button>

                            <button id="btn-submit" class="hidden px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Submit Exam
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pause Modal -->
            <div id="pause-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Exam Paused</h3>
                        <p class="text-sm text-gray-500 mb-4">Ujian Anda sedang dipause. Timer tidak berjalan.</p>
                        <button id="btn-resume" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full">
                            Resume Exam
                        </button>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div id="confirm-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 id="confirm-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Confirm</h3>
                        <p id="confirm-message" class="text-sm text-gray-500 mb-4"></p>
                        <div class="flex gap-2">
                            <button id="confirm-yes" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Yes</button>
                            <button id="confirm-no" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">No</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Container -->
            <div id="results-container" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Hasil Ujian</h3>
                
                <!-- Score Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Total Score</p>
                        <p id="result-score" class="text-3xl font-bold text-indigo-600">0</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Reading</p>
                        <p id="result-reading" class="text-2xl font-bold text-green-600">0</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Listening</p>
                        <p id="result-listening" class="text-2xl font-bold text-blue-600">0</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Status</p>
                        <p id="result-status" class="text-lg font-bold text-purple-600">Completed</p>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('simulations.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    /**
     * TOEFL Simulation Exam Interface
     * Features:
     * - 4 modes: Practice, Scheduled, Realistic, Focus
     * - Accurate timer per section with server sync
     * - Auto-save every 30 seconds
     * - Fullscreen API
     * - Wake Lock API
     * - WebRTC camera check-in
     * - Break management
     */
    
    document.addEventListener('DOMContentLoaded', function() {
        // ==================== STATE MANAGEMENT ====================
        const state = {
            simulationId: {{ $simulation->id }},
            mode: '{{ $simulation->mode }}',
            currentSection: '{{ $simulation->status }}',
            currentQuestionIndex: 0,
            totalQuestions: 0,
            timeRemaining: 0,
            isPaused: false,
            isFullscreen: false,
            flaggedQuestions: new Set(),
            answeredQuestions: new Set(),
            autoSaveInterval: null,
            timerInterval: null,
            breakTimerInterval: null,
            wakeLock: null,
            mediaStream: null,
            questions: [],
            sectionOrder: ['reading', 'listening', 'speaking', 'writing']
        };

        // ==================== DOM ELEMENTS ====================
        const elements = {
            examInterface: document.getElementById('exam-interface'),
            breakScreen: document.getElementById('break-screen'),
            resultsContainer: document.getElementById('results-container'),
            cameraCheckinModal: document.getElementById('camera-checkin-modal'),
            fullscreenModal: document.getElementById('fullscreen-modal'),
            pauseModal: document.getElementById('pause-modal'),
            confirmModal: document.getElementById('confirm-modal'),
            timerDisplay: document.getElementById('timer-display'),
            progressBar: document.getElementById('progress-bar'),
            progressText: document.getElementById('progress-text'),
            questionNavGrid: document.getElementById('question-nav-grid'),
            questionNumbers: document.getElementById('question-numbers'),
            btnPause: document.getElementById('btn-pause'),
            btnFlag: document.getElementById('btn-flag'),
            btnReviewFlagged: document.getElementById('btn-review-flagged'),
            btnPrevious: document.getElementById('btn-previous'),
            btnNext: document.getElementById('btn-next'),
            btnSubmit: document.getElementById('btn-submit'),
            btnCancel: document.getElementById('btn-cancel'),
            btnEnterFullscreen: document.getElementById('btn-enter-fullscreen'),
            btnExitFullscreen: document.getElementById('btn-exit-fullscreen'),
            btnConfirmCheckin: document.getElementById('btn-confirm-checkin'),
            btnEndBreak: document.getElementById('btn-end-break'),
            btnResume: document.getElementById('btn-resume')
        };

        // ==================== INITIALIZATION ====================
        async function init() {
            // Check mode requirements
            if (state.mode === 'realistic') {
                await showCameraCheckin();
            } else if (state.mode === 'realistic' || state.mode === 'focus') {
                await requestFullscreen();
            }

            // Request wake lock
            await requestWakeLock();

            // Load initial data
            await loadSimulationData();

            // Show exam interface
            elements.examInterface.classList.remove('hidden');

            // Start auto-save
            startAutoSave();

            // Start timer
            startTimer();

            // Setup event listeners
            setupEventListeners();

            // Handle visibility change (for Scheduled mode)
            document.addEventListener('visibilitychange', handleVisibilityChange);

            // Handle fullscreen change
            document.addEventListener('fullscreenchange', handleFullscreenChange);
        }

        // ==================== CAMERA CHECK-IN (Realistic Mode) ====================
        async function showCameraCheckin() {
            try {
                state.mediaStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 480 },
                    audio: false 
                });
                
                const video = document.getElementById('checkin-video');
                video.srcObject = state.mediaStream;
                
                elements.cameraCheckinModal.classList.remove('hidden');
            } catch (error) {
                console.error('Camera access denied:', error);
                alert('Camera access is required for Realistic mode. Please enable camera permissions.');
            }
        }

        // ==================== FULLSCREEN API ====================
        async function requestFullscreen() {
            const elem = document.documentElement;
            
            if (!document.fullscreenElement) {
                try {
                    await elem.requestFullscreen();
                    state.isFullscreen = true;
                } catch (error) {
                    console.error('Fullscreen request failed:', error);
                    elements.fullscreenModal.classList.remove('hidden');
                }
            }
        }

        function exitFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                state.isFullscreen = false;
            }
        }

        function handleFullscreenChange() {
            state.isFullscreen = !!document.fullscreenElement;
            elements.btnExitFullscreen.classList.toggle('hidden', !state.isFullscreen);
        }

        // ==================== WAKE LOCK API ====================
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    state.wakeLock = await navigator.wakeLock.request('screen');
                    console.log('Wake Lock active');
                    
                    state.wakeLock.addEventListener('release', () => {
                        console.log('Wake Lock released');
                    });
                }
            } catch (error) {
                console.error('Wake Lock error:', error);
            }
        }

        // ==================== TIMER (Vanilla JavaScript) ====================
        function startTimer() {
            // Sync with server first
            syncTimerWithServer();
            
            // Then start local countdown
            state.timerInterval = setInterval(() => {
                if (!state.isPaused && state.timeRemaining > 0) {
                    state.timeRemaining--;
                    updateTimerDisplay();
                    
                    // Record time spent on server periodically
                    recordTimeSpent();
                    
                    if (state.timeRemaining <= 0) {
                        handleTimeUp();
                    }
                }
            }, 1000);
        }

        async function syncTimerWithServer() {
            try {
                const response = await fetch(`/simulations/${state.simulationId}/status`);
                const data = await response.json();
                
                if (data.success) {
                    // Calculate remaining time based on section duration
                    const currentSection = getCurrentSectionConfig();
                    if (currentSection) {
                        state.timeRemaining = currentSection.duration_minutes * 60;
                        updateTimerDisplay();
                    }
                }
            } catch (error) {
                console.error('Failed to sync timer:', error);
            }
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(state.timeRemaining / 60);
            const seconds = state.timeRemaining % 60;
            elements.timerDisplay.textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color when time is low
            if (state.timeRemaining <= 60) {
                elements.timerDisplay.classList.remove('text-indigo-600');
                elements.timerDisplay.classList.add('text-red-600');
            }
        }

        function handleTimeUp() {
            clearInterval(state.timerInterval);
            
            if (state.currentSection === 'listening') {
                // Auto-transition to break
                showBreakScreen();
            } else if (state.currentSection === 'writing') {
                // Auto-submit
                submitExam();
            } else {
                // Move to next section
                nextSection();
            }
        }

        // ==================== BREAK MANAGEMENT ====================
        function showBreakScreen() {
            elements.examInterface.classList.add('hidden');
            elements.breakScreen.classList.remove('hidden');
            
            let breakTime = 10 * 60; // 10 minutes
            updateBreakTimerDisplay(breakTime);
            
            state.breakTimerInterval = setInterval(() => {
                breakTime--;
                updateBreakTimerDisplay(breakTime);
                
                if (breakTime <= 0) {
                    clearInterval(state.breakTimerInterval);
                    nextSection();
                }
            }, 1000);
        }

        function updateBreakTimerDisplay(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            document.getElementById('break-timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // ==================== AUTO-SAVE (AJAX) ====================
        function startAutoSave() {
            state.autoSaveInterval = setInterval(async () => {
                await saveCurrentAnswer();
            }, 30000); // Every 30 seconds
        }

        async function saveCurrentAnswer() {
            const answer = getCurrentAnswer();
            if (!answer) return;
            
            try {
                await fetch(`/simulations/${state.simulationId}/answer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        question_index: state.currentQuestionIndex,
                        answer: answer,
                        is_flagged: state.flaggedQuestions.has(state.currentQuestionIndex)
                    }),
                });
                
                console.log('Answer auto-saved');
            } catch (error) {
                console.error('Auto-save failed:', error);
            }
        }

        async function recordTimeSpent() {
            try {
                await fetch(`/simulations/${state.simulationId}/record-time`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ seconds: 1 }),
                });
            } catch (error) {
                console.error('Time recording failed:', error);
            }
        }

        // ==================== NAVIGATION ====================
        function goToPrevious() {
            if (state.currentQuestionIndex > 0) {
                state.currentQuestionIndex--;
                loadQuestion(state.currentQuestionIndex);
                updateNavigationButtons();
            }
        }

        function goToNext() {
            if (state.currentQuestionIndex < state.totalQuestions - 1) {
                state.currentQuestionIndex++;
                loadQuestion(state.currentQuestionIndex);
                updateNavigationButtons();
            } else {
                // Last question - show submit button
                elements.btnNext.classList.add('hidden');
                elements.btnSubmit.classList.remove('hidden');
            }
        }

        function goToQuestion(index) {
            state.currentQuestionIndex = index;
            loadQuestion(index);
            elements.questionNavGrid.classList.add('hidden');
        }

        function toggleFlag() {
            if (state.flaggedQuestions.has(state.currentQuestionIndex)) {
                state.flaggedQuestions.delete(state.currentQuestionIndex);
            } else {
                state.flaggedQuestions.add(state.currentQuestionIndex);
            }
            updateFlagButton();
            updateQuestionNavGrid();
        }

        function showReviewFlagged() {
            elements.questionNavGrid.classList.toggle('hidden');
            if (!elements.questionNavGrid.classList.contains('hidden')) {
                renderQuestionNavGrid();
            }
        }

        // ==================== VISIBILITY HANDLING (Scheduled Mode) ====================
        function handleVisibilityChange() {
            if (state.mode === 'scheduled' && document.visibilityState === 'hidden') {
                // User left the tab - mark as abandoned
                abandonExam();
            }
        }

        async function abandonExam() {
            try {
                await fetch(`/simulations/${state.simulationId}/abandon`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                
                alert('Exam has been marked as abandoned because you left the test window.');
                window.location.href = '/simulations';
            } catch (error) {
                console.error('Abandon failed:', error);
            }
        }

        // ==================== PAUSE/RESUME (Practice Mode) ====================
        function pauseExam() {
            state.isPaused = true;
            elements.pauseModal.classList.remove('hidden');
        }

        async function resumeExam() {
            try {
                await fetch(`/simulations/${state.simulationId}/resume-simulation`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                
                state.isPaused = false;
                elements.pauseModal.classList.add('hidden');
            } catch (error) {
                console.error('Resume failed:', error);
            }
        }

        // ==================== CANCEL (Practice Mode) ====================
        function cancelExam() {
            showConfirmModal(
                'Cancel Exam?',
                'Are you sure you want to cancel this exam? Your progress will be lost.',
                async () => {
                    try {
                        await fetch(`/simulations/${state.simulationId}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                        
                        window.location.href = '/simulations';
                    } catch (error) {
                        console.error('Cancel failed:', error);
                    }
                }
            );
        }

        // ==================== SUBMIT EXAM ====================
        function submitExam() {
            showConfirmModal(
                'Submit Exam?',
                'Are you sure you want to submit your exam? You cannot change answers after submission.',
                async () => {
                    try {
                        // Save final answer
                        await saveCurrentAnswer();
                        
                        const response = await fetch(`/simulations/${state.simulationId}/submit`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            // Clear intervals
                            clearInterval(state.timerInterval);
                            clearInterval(state.autoSaveInterval);
                            clearInterval(state.breakTimerInterval);
                            
                            // Release wake lock
                            if (state.wakeLock) {
                                state.wakeLock.release();
                            }
                            
                            // Release media stream
                            if (state.mediaStream) {
                                state.mediaStream.getTracks().forEach(track => track.stop());
                            }
                            
                            // Show results
                            elements.examInterface.classList.add('hidden');
                            elements.breakScreen.classList.add('hidden');
                            elements.resultsContainer.classList.remove('hidden');
                        }
                    } catch (error) {
                        console.error('Submit failed:', error);
                        alert('Failed to submit exam. Please try again.');
                    }
                }
            );
        }

        // ==================== EVENT LISTENERS ====================
        function setupEventListeners() {
            // Navigation
            elements.btnPrevious.addEventListener('click', goToPrevious);
            elements.btnNext.addEventListener('click', goToNext);
            
            // Flag
            elements.btnFlag.addEventListener('click', toggleFlag);
            elements.btnReviewFlagged.addEventListener('click', showReviewFlagged);
            
            // Mode-specific buttons
            if (state.mode === 'practice') {
                elements.btnPause.classList.remove('hidden');
                elements.btnCancel.classList.remove('hidden');
                elements.btnPause.addEventListener('click', pauseExam);
                elements.btnCancel.addEventListener('click', cancelExam);
            }
            
            // Fullscreen
            elements.btnEnterFullscreen.addEventListener('click', async () => {
                await requestFullscreen();
                elements.fullscreenModal.classList.add('hidden');
            });
            
            elements.btnExitFullscreen.addEventListener('click', exitFullscreen);
            
            // Camera check-in
            elements.btnConfirmCheckin.addEventListener('click', async () => {
                // Capture a frame from video
                const canvas = document.getElementById('checkin-canvas');
                const video = document.getElementById('checkin-video');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                // Stop media stream
                if (state.mediaStream) {
                    state.mediaStream.getTracks().forEach(track => track.stop());
                }
                
                elements.cameraCheckinModal.classList.add('hidden');
                await requestFullscreen();
            });
            
            // Break
            elements.btnEndBreak.addEventListener('click', () => {
                clearInterval(state.breakTimerInterval);
                nextSection();
            });
            
            // Pause modal
            elements.btnResume.addEventListener('click', resumeExam);
            
            // Confirm modal
            document.getElementById('confirm-yes').addEventListener('click', () => {
                if (state.confirmCallback) {
                    state.confirmCallback();
                }
                elements.confirmModal.classList.add('hidden');
            });
            
            document.getElementById('confirm-no').addEventListener('click', () => {
                elements.confirmModal.classList.add('hidden');
            });
            
            // Text answer character count
            const textAnswer = document.getElementById('text-answer');
            if (textAnswer) {
                textAnswer.addEventListener('input', () => {
                    document.getElementById('char-count').textContent = 
                        `${textAnswer.value.length} characters`;
                });
            }
        }

        // ==================== UTILITY FUNCTIONS ====================
        function showConfirmModal(title, message, callback) {
            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            state.confirmCallback = callback;
            elements.confirmModal.classList.remove('hidden');
        }

        function getCurrentSectionConfig() {
            // This should be populated from server data
            const sectionConfigs = {
                'reading': { duration_minutes: 54 },
                'listening': { duration_minutes: 41 },
                'speaking': { duration_minutes: 17 },
                'writing': { duration_minutes: 29 }
            };
            return sectionConfigs[state.currentSection];
        }

        function getCurrentAnswer() {
            // Get current answer from input fields
            const textAnswer = document.getElementById('text-answer');
            if (textAnswer && !textAnswer.parentElement.classList.contains('hidden')) {
                return textAnswer.value;
            }
            
            const selectedRadio = document.querySelector('input[name="answer-option"]:checked');
            if (selectedRadio) {
                return selectedRadio.value;
            }
            
            return null;
        }

        function updateNavigationButtons() {
            elements.btnPrevious.disabled = state.currentQuestionIndex === 0;
            
            if (state.currentQuestionIndex >= state.totalQuestions - 1) {
                elements.btnNext.classList.add('hidden');
                elements.btnSubmit.classList.remove('hidden');
            } else {
                elements.btnNext.classList.remove('hidden');
                elements.btnSubmit.classList.add('hidden');
            }
        }

        function updateFlagButton() {
            const btnFlagQuestion = document.getElementById('btn-flag-question');
            if (state.flaggedQuestions.has(state.currentQuestionIndex)) {
                btnFlagQuestion.classList.remove('hidden');
            } else {
                btnFlagQuestion.classList.add('hidden');
            }
        }

        function updateProgress() {
            const percentage = ((state.currentQuestionIndex + 1) / state.totalQuestions) * 100;
            elements.progressBar.style.width = `${percentage}%`;
            elements.progressText.textContent = `${state.currentQuestionIndex + 1} / ${state.totalQuestions}`;
        }

        function renderQuestionNavGrid() {
            elements.questionNumbers.innerHTML = '';
            
            for (let i = 0; i < state.totalQuestions; i++) {
                const btn = document.createElement('button');
                btn.className = 'w-8 h-8 rounded flex items-center justify-center text-sm font-medium transition';
                
                if (i === state.currentQuestionIndex) {
                    btn.classList.add('ring-2', 'ring-indigo-500');
                }
                
                if (state.answeredQuestions.has(i)) {
                    btn.classList.add('bg-indigo-600', 'text-white');
                } else if (state.flaggedQuestions.has(i)) {
                    btn.classList.add('bg-yellow-400', 'text-gray-900');
                } else {
                    btn.classList.add('bg-gray-200', 'text-gray-700');
                }
                
                btn.textContent = i + 1;
                btn.addEventListener('click', () => goToQuestion(i));
                elements.questionNumbers.appendChild(btn);
            }
        }

        function updateQuestionNavGrid() {
            if (!elements.questionNavGrid.classList.contains('hidden')) {
                renderQuestionNavGrid();
            }
        }

        function loadQuestion(index) {
            // This would load question data from server or local state
            // For now, just update UI
            document.getElementById('question-number').textContent = `Question ${index + 1}`;
            updateProgress();
            updateFlagButton();
        }

        async function loadSimulationData() {
            // Load simulation data from server
            try {
                const response = await fetch(`/simulations/${state.simulationId}/status`);
                const data = await response.json();
                
                if (data.success) {
                    // Update state with server data
                    state.currentSection = data.data.status;
                    state.totalQuestions = data.data.sections?.[0]?.question_count || 10;
                    
                    // Update section name display
                    document.getElementById('current-section-name').textContent = 
                        state.currentSection.charAt(0).toUpperCase() + state.currentSection.slice(1);
                    
                    // Update badge color based on section
                    const badgeColors = {
                        'reading': 'bg-blue-100 text-blue-800',
                        'listening': 'bg-green-100 text-green-800',
                        'speaking': 'bg-purple-100 text-purple-800',
                        'writing': 'bg-orange-100 text-orange-800'
                    };
                    
                    const badge = document.getElementById('section-badge');
                    badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeColors[state.currentSection] || 'bg-gray-100 text-gray-800'}`;
                    badge.textContent = state.currentSection.charAt(0).toUpperCase() + state.currentSection.slice(1);
                }
            } catch (error) {
                console.error('Failed to load simulation data:', error);
            }
        }

        async function nextSection() {
            try {
                const response = await fetch(`/simulations/${state.simulationId}/next-section`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success) {
                    state.currentSection = data.new_status;
                    
                    if (state.currentSection === 'break' || state.currentSection === 'speaking') {
                        showBreakScreen();
                    } else {
                        // Hide break screen and show exam interface
                        elements.breakScreen.classList.add('hidden');
                        elements.examInterface.classList.remove('hidden');
                        
                        // Update section display
                        document.getElementById('current-section-name').textContent = 
                            state.currentSection.charAt(0).toUpperCase() + state.currentSection.slice(1);
                        
                        // Reset timer for new section
                        syncTimerWithServer();
                    }
                }
            } catch (error) {
                console.error('Next section failed:', error);
            }
        }

        // ==================== START THE EXAM ====================
        init();
    });
    </script>
</x-app-layout>
