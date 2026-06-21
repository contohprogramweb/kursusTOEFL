@extends('layouts.app')

@section('title', 'Masukkan Kode Undangan')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
                Hubungkan dengan Anak Anda
            </h1>
            
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Masukkan kode undangan 6 digit yang diberikan oleh anak Anda untuk mulai memantau perkembangan belajar mereka.
            </p>

            <form id="submit-form" onsubmit="submitCode(event)">
                @csrf
                <div class="mb-6">
                    <label for="invite_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kode Undangan
                    </label>
                    <input 
                        type="text" 
                        id="invite_code" 
                        name="invite_code" 
                        maxlength="6"
                        placeholder="ABC123"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white uppercase tracking-wider text-center text-2xl font-mono"
                        required
                    >
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Hanya huruf besar dan angka (contoh: ABC123)
                    </p>
                </div>

                <button type="submit" id="submit-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                    Kirim Permintaan
                </button>
            </form>

            <div id="loading" class="hidden text-center py-4">
                <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Memproses...</p>
            </div>

            <div id="success-message" class="hidden mt-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                <p class="text-green-800 dark:text-green-200"></p>
            </div>

            <div id="error-message" class="hidden mt-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-200"></p>
            </div>
        </div>

        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Anak Saya</h2>
            <a href="{{ route('parent.children') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                Lihat daftar anak yang sudah terhubung →
            </a>
        </div>
    </div>
</div>

<script>
function submitCode(event) {
    event.preventDefault();
    
    const btn = document.getElementById('submit-btn');
    const loading = document.getElementById('loading');
    const successDiv = document.getElementById('success-message');
    const errorDiv = document.getElementById('error-message');
    const codeInput = document.getElementById('invite_code').value;
    
    btn.disabled = true;
    btn.classList.add('opacity-50');
    loading.classList.remove('hidden');
    successDiv.classList.add('hidden');
    errorDiv.classList.add('hidden');
    
    fetch('{{ route("parent.code.submit") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ invite_code: codeInput.toUpperCase() }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successDiv.querySelector('p').textContent = data.message;
            successDiv.classList.remove('hidden');
            document.getElementById('submit-form').reset();
        } else {
            errorDiv.querySelector('p').textContent = data.message;
            errorDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        errorDiv.querySelector('p').textContent = 'Terjadi kesalahan. Silakan coba lagi.';
        errorDiv.classList.remove('hidden');
    })
    .finally(() => {
        loading.classList.add('hidden');
        btn.disabled = false;
        btn.classList.remove('opacity-50');
    });
}

// Auto-uppercase input
document.getElementById('invite_code').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>
@endsection
