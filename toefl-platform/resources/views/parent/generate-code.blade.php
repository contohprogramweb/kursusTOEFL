@extends('layouts.app')

@section('title', 'Generate Kode Undangan')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
                Generate Kode Undangan Orang Tua
            </h1>
            
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Bagikan kode ini dengan orang tua Anda agar mereka dapat memantau perkembangan belajar Anda.
                Kode ini berlaku selama 24 jam.
            </p>

            <div id="code-section" class="hidden">
                <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                    <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">Kode Undangan Anda:</p>
                    <p id="invite-code" class="text-4xl font-mono font-bold text-blue-600 dark:text-blue-300 tracking-wider"></p>
                </div>
                
                <button onclick="copyCode()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                    Salin Kode
                </button>
            </div>

            <button id="generate-btn" onclick="generateCode()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                Generate Kode Baru
            </button>

            <div id="loading" class="hidden text-center py-4">
                <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Memproses...</p>
            </div>

            <div id="error-message" class="hidden mt-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-200"></p>
            </div>
        </div>

        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Permintaan Pending</h2>
            <a href="{{ route('parent.pending') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                Lihat permintaan tautan yang menunggu persetujuan →
            </a>
        </div>
    </div>
</div>

<script>
function generateCode() {
    const btn = document.getElementById('generate-btn');
    const loading = document.getElementById('loading');
    const codeSection = document.getElementById('code-section');
    const errorDiv = document.getElementById('error-message');
    
    btn.classList.add('hidden');
    loading.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    
    fetch('{{ route("parent.code.generate") }}', {
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
            document.getElementById('invite-code').textContent = data.code;
            codeSection.classList.remove('hidden');
        } else {
            errorDiv.querySelector('p').textContent = data.message;
            errorDiv.classList.remove('hidden');
            btn.classList.remove('hidden');
        }
    })
    .catch(error => {
        errorDiv.querySelector('p').textContent = 'Terjadi kesalahan. Silakan coba lagi.';
        errorDiv.classList.remove('hidden');
        btn.classList.remove('hidden');
    })
    .finally(() => {
        loading.classList.add('hidden');
    });
}

function copyCode() {
    const code = document.getElementById('invite-code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('Kode berhasil disalin!');
    }).catch(err => {
        alert('Gagal menyalin kode.');
    });
}
</script>
@endsection
