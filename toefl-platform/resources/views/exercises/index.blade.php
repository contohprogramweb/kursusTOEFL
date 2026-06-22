<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Latihan Interaktif') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const exerciseForm = document.getElementById('exercise-form');
        
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
                const response = await fetch('/exercises/create', {
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
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Failed to start exercise.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    });
    </script>
</x-app-layout>
