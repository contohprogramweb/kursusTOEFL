@props(['icon', 'label', 'value', 'subtext'])

<div class="bg-white rounded-xl shadow-md p-6 flex items-center gap-4 hover:shadow-lg transition cursor-default" 
     role="article"
     aria-label="{{ $label }}: {{ $value }} {{ $subtext }}">
    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-2xl" 
         aria-hidden="true">
        {{ $icon }}
    </div>
    <div>
        <p class="text-sm text-gray-500 font-medium">{{ $label }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ $value }}</p>
        <p class="text-xs text-gray-400">{{ $subtext }}</p>
    </div>
</div>
