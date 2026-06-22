<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Latihan Interaktif') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Selection Form -->
            <div id="selection-form" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Section & Jumlah Soal</h3>
                
                <form id="exercise-form" class="space-y-4">
                    @csrf
                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700">Section</label>
                        <select id="section" name="section" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Section --</option>
                            <option value="reading">Reading</option>
                            <option value="listening">Listening</option>
                            <option value="speaking">Speaking</option>
                            <option value="writing">Writing</option>
                        </select>
                    </div>

                    <div>
                        <label for="total_questions" class="block text-sm font-medium text-gray-700">Jumlah Soal</label>
                        <input type="number" id="total_questions" name="total_questions" min="1" max="50" value="10" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="enable_timer" name="enable_timer" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="enable_timer" class="text-sm text-gray-700">Aktifkan Timer per Soal (Opsional)</label>
                    </div>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Mulai Latihan
                    </button>
                </form>
            </div>

            <!-- Exercise Session Container (hidden initially) -->
            <div id="exercise-container" class="hidden">
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

                <!-- Timer Display -->
                <div id="timer-container" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 mb-6 text-center">
                    <span class="text-sm font-medium text-gray-700">Waktu Tersisa:</span>
                    <span id="timer-display" class="ml-2 text-2xl font-bold text-indigo-600">00:00</span>
                </div>

                <!-- Question Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div id="question-content">
                        <!-- Loading State -->
                        <div id="loading-question" class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                            <p class="mt-2 text-gray-600">Memuat soal...</p>
                        </div>

                        <!-- Question Content (dynamically loaded) -->
                        <div id="question-display" class="hidden">
                            <!-- Passage Text (for Reading/Listening) -->
                            <div id="passage-container" class="hidden mb-6 p-4 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                                <h4 class="font-semibold text-gray-700 mb-2">Passage:</h4>
                                <div id="passage-text" class="text-gray-800 whitespace-pre-line"></div>
                            </div>

                            <!-- Audio Player (for Listening) -->
                            <div id="audio-container" class="hidden mb-6">
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
                                <h3 id="question-number" class="text-lg font-semibold text-gray-800 mb-2"></h3>
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
                                    <textarea id="text-answer" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ketik jawaban Anda di sini..."></textarea>
                                    <p class="mt-2 text-sm text-gray-500">Tips: Jawaban akan disimpan otomatis.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <button id="btn-previous" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            ← Previous
                        </button>

                        <button id="btn-next" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Next →
                        </button>

                        <button id="btn-submit" class="hidden px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Submit Answers
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Container (hidden initially) -->
            <div id="results-container" class="hidden bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Hasil Latihan</h3>
                
                <!-- Score Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-indigo-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Score</p>
                        <p id="result-score" class="text-3xl font-bold text-indigo-600">0</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Benar</p>
                        <p id="result-correct" class="text-3xl font-bold text-green-600">0</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-600">Total Soal</p>
                        <p id="result-total" class="text-3xl font-bold text-blue-600">0</p>
                    </div>
                </div>

                <!-- Detailed Results -->
                <div id="detailed-results" class="space-y-4">
                    <!-- Results will be dynamically inserted here -->
                </div>

                <div class="mt-6">
                    <a href="{{ route('exercises.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Latihan Lagi
                    </a>
                    <a href="{{ route('exercises.history') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 ml-2">
                        Lihat Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // State management
        let currentSession = null;
        let currentIndex = 0;
        let totalQuestions = 0;
        let timerInterval = null;
        let timeRemaining = 0;
        const enableTimer = document.getElementById('enable_timer').checked;

        // DOM Elements
        const selectionForm = document.getElementById('selection-form');
        const exerciseContainer = document.getElementById('exercise-container');
        const resultsContainer = document.getElementById('results-container');
        const exerciseForm = document.getElementById('exercise-form');
        
        // Start new exercise session
        exerciseForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const section = formData.get('section');
            const totalQuestions = formData.get('total_questions');
            
            if (!section || !totalQuestions) {
                alert('Please select a section and number of questions.');
                return;
            }

            try {
                const response = await fetch('{{ route("exercises.create") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        section: section,
                        total_questions: parseInt(totalQuestions),
                    }),
                });

                const data = await response.json();
                
                if (data.success) {
                    currentSession = data.session;
                    currentIndex = 0;
                    totalQuestions = parseInt(totalQuestions);
                    
                    // Show exercise container
                    selectionForm.classList.add('hidden');
                    exerciseContainer.classList.remove('hidden');
                    
                    // Load first question
                    loadQuestion(0);
                } else {
                    alert(data.message || 'Failed to start exercise.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });

        // Load question by index
        async function loadQuestion(index) {
            const loadingDiv = document.getElementById('loading-question');
            const questionDisplay = document.getElementById('question-display');
            
            loadingDiv.classList.remove('hidden');
            questionDisplay.classList.add('hidden');

            try {
                const response = await fetch(`/exercises/${currentSession.id}/question`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();
                
                if (data.success) {
                    displayQuestion(data);
                    updateNavigation(data);
                    updateProgress(data.current_index + 1, data.total_questions);
                    
                    // Initialize timer if enabled
                    if (enableTimer && data.question.preparation_time) {
                        startTimer(data.question.preparation_time);
                    }
                } else {
                    alert(data.message || 'Failed to load question.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while loading the question.');
            } finally {
                loadingDiv.classList.add('hidden');
                questionDisplay.classList.remove('hidden');
            }
        }

        // Display question content
        function displayQuestion(data) {
            const question = data.question;
            
            // Update progress text
            document.getElementById('progress-text').textContent = `${data.current_index + 1} / ${data.total_questions}`;
            
            // Show/hide passage
            const passageContainer = document.getElementById('passage-container');
            if (question.passage_text) {
                passageContainer.classList.remove('hidden');
                document.getElementById('passage-text').textContent = question.passage_text;
            } else {
                passageContainer.classList.add('hidden');
            }
            
            // Show/hide audio
            const audioContainer = document.getElementById('audio-container');
            if (question.audio_url) {
                audioContainer.classList.remove('hidden');
                document.getElementById('audio-source').src = question.audio_url;
                document.getElementById('audio-player').load();
            } else {
                audioContainer.classList.add('hidden');
            }
            
            // Show/hide image
            const imageContainer = document.getElementById('image-container');
            if (question.image_url) {
                imageContainer.classList.remove('hidden');
                document.getElementById('question-image').src = question.image_url;
            } else {
                imageContainer.classList.add('hidden');
            }
            
            // Update question text
            document.getElementById('question-number').textContent = `Question ${data.current_index + 1}`;
            document.getElementById('question-text').textContent = question.question_text;
            
            // Handle answer area based on question type
            const multipleChoiceOptions = document.getElementById('multiple-choice-options');
            const textInputContainer = document.getElementById('text-input-container');
            
            if (question.question_type === 'multiple_choice' && question.options) {
                multipleChoiceOptions.classList.remove('hidden');
                textInputContainer.classList.add('hidden');
                
                // Render options
                multipleChoiceOptions.innerHTML = '';
                question.options.forEach(option => {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer';
                    optionDiv.innerHTML = `
                        <input type="radio" name="answer" value="${option.id}" id="option_${option.id}" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                               ${data.current_answer == option.id ? 'checked' : ''}>
                        <label for="option_${option.id}" class="ml-3 block text-sm font-medium text-gray-900 cursor-pointer">
                            ${option.option_text}
                        </label>
                    `;
                    multipleChoiceOptions.appendChild(optionDiv);
                });
                
                // Add event listeners to save answer on selection
                multipleChoiceOptions.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        saveAnswer(question.id, this.value);
                    });
                });
            } else {
                multipleChoiceOptions.classList.add('hidden');
                textInputContainer.classList.remove('hidden');
                
                const textAnswer = document.getElementById('text-answer');
                textAnswer.value = data.current_answer || '';
                
                // Auto-save on input
                textAnswer.addEventListener('input', function() {
                    clearTimeout(window.saveTimeout);
                    window.saveTimeout = setTimeout(() => {
                        saveAnswer(question.id, this.value);
                    }, 1000);
                });
            }
        }

        // Update navigation buttons
        function updateNavigation(data) {
            const btnPrevious = document.getElementById('btn-previous');
            const btnNext = document.getElementById('btn-next');
            const btnSubmit = document.getElementById('btn-submit');
            
            btnPrevious.disabled = data.current_index === 0;
            
            const isLastQuestion = data.current_index >= data.total_questions - 1;
            
            if (isLastQuestion) {
                btnNext.classList.add('hidden');
                btnSubmit.classList.remove('hidden');
            } else {
                btnNext.classList.remove('hidden');
                btnSubmit.classList.add('hidden');
            }
        }

        // Update progress bar
        function updateProgress(current, total) {
            const percentage = (current / total) * 100;
            document.getElementById('progress-bar').style.width = `${percentage}%`;
            document.getElementById('progress-text').textContent = `${current} / ${total}`;
        }

        // Save answer
        async function saveAnswer(questionId, answer) {
            try {
                await fetch(`/exercises/${currentSession.id}/answer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        question_id: questionId,
                        answer: answer,
                    }),
                });
            } catch (error) {
                console.error('Error saving answer:', error);
            }
        }

        // Navigate to next question
        document.getElementById('btn-next').addEventListener('click', async function() {
            try {
                const response = await fetch(`/exercises/${currentSession.id}/next`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                
                if (data.success) {
                    currentIndex = data.current_index;
                    displayQuestion(data);
                    updateNavigation(data);
                    updateProgress(data.current_index + 1, data.total_questions);
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred.');
            }
        });

        // Navigate to previous question
        document.getElementById('btn-previous').addEventListener('click', async function() {
            try {
                const response = await fetch(`/exercises/${currentSession.id}/previous`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                
                if (data.success) {
                    currentIndex = data.current_index;
                    displayQuestion(data);
                    updateNavigation(data);
                    updateProgress(data.current_index + 1, data.total_questions);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Submit exercise
        document.getElementById('btn-submit').addEventListener('click', async function() {
            if (!confirm('Are you sure you want to submit your answers?')) {
                return;
            }

            try {
                const response = await fetch(`/exercises/${currentSession.id}/submit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                
                if (data.success) {
                    displayResults(data);
                } else {
                    alert(data.message || 'Failed to submit.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred.');
            }
        });

        // Display results
        function displayResults(data) {
            exerciseContainer.classList.add('hidden');
            resultsContainer.classList.remove('hidden');
            
            document.getElementById('result-score').textContent = data.score;
            document.getElementById('result-correct').textContent = `${data.correct_count} / ${data.total_questions}`;
            document.getElementById('result-total').textContent = data.total_questions;
            
            // Display detailed results
            const detailedResults = document.getElementById('detailed-results');
            detailedResults.innerHTML = '';
            
            data.results.forEach((result, index) => {
                const resultDiv = document.createElement('div');
                resultDiv.className = `p-4 rounded-lg border-l-4 ${result.is_correct ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'}`;
                
                resultDiv.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-semibold text-gray-800">Soal ${index + 1}</h4>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${result.is_correct ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">
                            ${result.is_correct ? 'BENAR' : 'SALAH'}
                        </span>
                    </div>
                    <p class="text-gray-700 mb-2">${result.question_text}</p>
                    <div class="text-sm">
                        <p class="text-gray-600">Jawaban Anda: <span class="font-medium">${result.user_answer || '-'}</span></p>
                        ${!result.is_correct ? `<p class="text-gray-600">Jawaban Benar: <span class="font-medium text-green-600">${result.correct_answer || '-'}</span></p>` : ''}
                    </div>
                    ${result.explanation ? `
                        <div class="mt-3 p-3 bg-white rounded border">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Penjelasan:</p>
                            <p class="text-sm text-gray-600">${result.explanation}</p>
                        </div>
                    ` : ''}
                `;
                
                detailedResults.appendChild(resultDiv);
            });
        }

        // Timer functionality
        function startTimer(seconds) {
            clearInterval(timerInterval);
            timeRemaining = seconds;
            updateTimerDisplay();
            
            document.getElementById('timer-container').classList.remove('hidden');
            
            timerInterval = setInterval(() => {
                timeRemaining--;
                updateTimerDisplay();
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    alert('Time\'s up! Moving to next question.');
                    document.getElementById('btn-next').click();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('timer-display').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Change color when time is running low
            if (timeRemaining <= 10) {
                document.getElementById('timer-display').classList.add('text-red-600');
                document.getElementById('timer-display').classList.remove('text-indigo-600');
            }
        }

        // Stop timer when leaving page
        window.addEventListener('beforeunload', function() {
            clearInterval(timerInterval);
        });
    });
    </script>
</x-app-layout>
