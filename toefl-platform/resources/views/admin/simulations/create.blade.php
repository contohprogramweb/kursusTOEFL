@extends('layouts.app')

@section('title', 'Create Simulation Template')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Create New Simulation Template</h1>

    <form action="{{ route('admin.simulations.store') }}" method="POST" id="templateForm">
        @csrf
        
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                    <input type="text" name="name" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Full Test TOEFL iBT">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode *</label>
                    <select name="mode" required 
                            class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @foreach($modes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" 
                          class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Describe this simulation template..."></textarea>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Duration (minutes) *</label>
                    <input type="number" name="total_duration" required min="10" value="120"
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Institution</label>
                    <select name="institution_id" 
                            class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Global (All Users)</option>
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" value="1" 
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Set as Default</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Sections Configuration -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Sections Configuration</h2>
            <p class="text-sm text-gray-600 mb-4">Define the sections for this simulation. Order matters!</p>
            
            <div id="sectionsContainer">
                <!-- Section templates will be added here by JavaScript -->
            </div>
            
            <button type="button" onclick="addSection()" 
                    class="mt-4 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg border border-gray-300">
                + Add Section
            </button>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-between">
            <a href="{{ route('admin.simulations.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow transition">
                Create Template
            </button>
        </div>
    </form>
</div>

<script>
let sectionCount = 0;
const sectionTypes = ['reading', 'listening', 'speaking', 'writing'];

function addSection() {
    const container = document.getElementById('sectionsContainer');
    const sectionHtml = `
        <div class="section-item border rounded-lg p-4 mb-4 bg-gray-50 relative" data-index="${sectionCount}">
            <button type="button" onclick="removeSection(this)" 
                    class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-sm">
                ✕ Remove
            </button>
            
            <div class="grid grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Order</label>
                    <input type="number" name="sections[${sectionCount}][order_index]" 
                           value="${sectionCount}" min="0"
                           class="w-full border rounded px-3 py-1 text-sm" readonly>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Section Type *</label>
                    <select name="sections[${sectionCount}][section]" required 
                            class="w-full border rounded px-3 py-1 text-sm">
                        <option value="">Select...</option>
                        ${sectionTypes.map(type => `<option value="${type}">${type.charAt(0).toUpperCase() + type.slice(1)}</option>`).join('')}
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Duration (min) *</label>
                    <input type="number" name="sections[${sectionCount}][duration_minutes]" 
                           required min="1" value="30"
                           class="w-full border rounded px-3 py-1 text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Question Count</label>
                    <input type="number" name="sections[${sectionCount}][question_count]" 
                           min="0" value="0"
                           class="w-full border rounded px-3 py-1 text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Break After?</label>
                    <div class="flex items-center mt-1">
                        <input type="checkbox" name="sections[${sectionCount}][break_after]" 
                               onchange="toggleBreakDuration(this)"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <input type="number" name="sections[${sectionCount}][break_duration]" 
                               placeholder="min" min="0" value="10" disabled
                               class="ml-2 w-20 border rounded px-2 py-1 text-sm disabled:bg-gray-200">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', sectionHtml);
    sectionCount++;
}

function removeSection(button) {
    button.closest('.section-item').remove();
    // Re-index remaining sections
    document.querySelectorAll('.section-item').forEach((item, index) => {
        item.setAttribute('data-index', index);
        item.innerHTML = item.innerHTML.replace(/\[\d+\]/g, `[${index}]`);
        const orderInput = item.querySelector('input[name*="[order_index]"]');
        if (orderInput) orderInput.value = index;
    });
    sectionCount = document.querySelectorAll('.section-item').length;
}

function toggleBreakDuration(checkbox) {
    const input = checkbox.parentElement.querySelector('input[type="number"]');
    input.disabled = !checkbox.checked;
}

// Add default sections on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add Reading section
    addSection();
    const firstSection = document.querySelector('.section-item select[name*="[section]"]');
    if (firstSection) firstSection.value = 'reading';
    
    // Add Listening section
    addSection();
    const secondSection = document.querySelectorAll('.section-item select[name*="[section]"]')[1];
    if (secondSection) secondSection.value = 'listening';
});
</script>
@endsection
