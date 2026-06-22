@extends('layouts.app')

@section('title', 'Buat Thread Baru')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <a href="{{ route('forum.index') }}" class="text-blue-600 hover:underline mb-4 inline-block">
        ← Kembali ke Forum
    </a>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Buat Thread Baru</h1>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('forum.threads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Category -->
            <div class="mb-4">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                    Kategori <span class="text-red-500">*</span>
                </label>
                <select name="category" id="category" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Judul <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="255"
                       placeholder="Contoh: Tips mengatasi nervous saat Speaking Test"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div class="mb-4">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                    Konten <span class="text-red-500">*</span>
                </label>
                <textarea name="content" id="content" rows="10" required
                          placeholder="Tulis konten thread Anda di sini..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('content') }}</textarea>
                
                <!-- Rich Text Toolbar -->
                <div class="mt-2 flex gap-2">
                    <button type="button" onclick="formatText('bold')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                        <b>B</b>
                    </button>
                    <button type="button" onclick="formatText('italic')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                        <i>I</i>
                    </button>
                    <button type="button" onclick="formatText('bullet')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                        • List
                    </button>
                    <button type="button" onclick="formatText('code')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                        {'</>'} Code
                    </button>
                </div>
                @error('content')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Image Upload -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Gambar (opsional, max 5MB per file, auto-resize 800px)
                </label>
                <input type="file" name="images[]" multiple accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Format yang didukung: JPEG, PNG, GIF, WebP</p>
            </div>

            <!-- Info Box -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-semibold text-blue-800 mb-2">📌 Panduan Posting:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Gunakan judul yang deskriptif dan relevan</li>
                    <li>• Hindari konten spam atau repetitif</li>
                    <li>• Dilarang memposting link pendek (bit.ly, tinyurl, dll)</li>
                    <li>• Hormati pengguna lain dan tetap sopan</li>
                    <li>• Konten yang melanggar aturan akan disembunyikan atau dihapus</li>
                </ul>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    Buat Thread
                </button>
                <a href="{{ route('forum.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function formatText(format) {
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    const selectedText = text.substring(start, end);
    
    let before = text.substring(0, start);
    let after = text.substring(end);
    let formatted = '';
    
    switch(format) {
        case 'bold':
            formatted = `**${selectedText}**`;
            break;
        case 'italic':
            formatted = `*${selectedText}*`;
            break;
        case 'bullet':
            formatted = `• ${selectedText}`;
            break;
        case 'code':
            formatted = `\`${selectedText}\``;
            break;
    }
    
    textarea.value = before + formatted + after;
    textarea.focus();
}
</script>
@endsection
