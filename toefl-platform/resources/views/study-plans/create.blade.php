@extends('layouts.app')

@section('title', 'Buat Study Plan Baru')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📚 Buat Study Plan Baru</h1>
        
        <form action="{{ route('study-plan.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Plan Name -->
            <div>
                <label for="plan_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Plan (Opsional)
                </label>
                <input 
                    type="text" 
                    name="plan_name" 
                    id="plan_name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Persiapan TOEFL Juli 2024"
                    value="{{ old('plan_name') }}"
                >
            </div>

            <!-- Target Score -->
            <div>
                <label for="target_score" class="block text-sm font-medium text-gray-700 mb-2">
                    🎯 Target Skor TOEFL
                </label>
                <input 
                    type="number" 
                    name="target_score" 
                    id="target_score"
                    min="0" 
                    max="677"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="{{ old('target_score', 500) }}"
                >
                <p class="mt-1 text-sm text-gray-500">Skala 0-677 (ITP) atau 0-120 (iBT)</p>
            </div>

            <!-- Test Date -->
            <div>
                <label for="test_date" class="block text-sm font-medium text-gray-700 mb-2">
                    📅 Tanggal Test
                </label>
                <input 
                    type="date" 
                    name="test_date" 
                    id="test_date"
                    required
                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="{{ old('test_date', $defaultTestDate->format('Y-m-d')) }}"
                >
            </div>

            <!-- Daily Hours -->
            <div>
                <label for="daily_hours" class="block text-sm font-medium text-gray-700 mb-2">
                    ⏰ Jam Belajar per Hari
                </label>
                <input 
                    type="number" 
                    name="daily_hours" 
                    id="daily_hours"
                    step="0.5"
                    min="0.5" 
                    max="12"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="{{ old('daily_hours', $defaultDailyHours) }}"
                >
                <p class="mt-1 text-sm text-gray-500">Rekomendasi: 2-3 jam/hari untuk hasil optimal</p>
            </div>

            <!-- Available Days -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    📆 Hari Tersedia untuk Belajar
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($availableDaysOptions as $option)
                        <label class="flex items-center space-x-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ in_array($option['value'], old('available_days', [1,2,3,4,5])) ? 'bg-blue-50 border-blue-300' : '' }}">
                            <input 
                                type="checkbox" 
                                name="available_days[]" 
                                value="{{ $option['value'] }}"
                                {{ in_array($option['value'], old('available_days', [1,2,3,4,5])) ? 'checked' : '' }}
                                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            >
                            <span class="text-sm">{{ $option['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-sm text-gray-500">Pilih minimal 1 hari</p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <a href="{{ route('student.dashboard') }}" class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Batal
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                    🚀 Generate Study Plan
                </button>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-800 mb-2">💡 Bagaimana ini bekerja?</h3>
        <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
            <li>Sistem akan membuat jadwal belajar otomatis berdasarkan target dan waktu yang tersedia</li>
            <li>Section dengan skor lebih rendah akan mendapat prioritas lebih tinggi</li>
            <li>Simulasi full test akan dijadwalkan setiap minggu</li>
            <li>Anda dapat menyesuaikan jadwal secara manual nanti</li>
        </ul>
    </div>
</div>
@endsection
