@props(['item'])

<div class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-blue-50 hover:border-blue-200 transition cursor-pointer group"
     role="article"
     tabindex="0"
     aria-label="Rekomendasi: {{ $item->title }}">
    
    <!-- Icon -->
    <div class="mt-1 text-xl" aria-hidden="true">
        @switch($item->type)
            @case('module')
                <span>📚</span>
                @break
            @case('practice')
                <span>✍️</span>
                @break
            @case('simulation')
                <span>🎓</span>
                @break
            @default
                <span>⭐</span>
        @endswitch
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
        <h4 class="text-sm font-bold text-gray-800 group-hover:text-blue-700 truncate" 
            title="{{ $item->title }}">
            {{ $item->title }}
        </h4>
        <p class="text-xs text-gray-500 mt-1 line-clamp-2" 
           title="{{ $item->reason }}">
            {{ $item->reason }}
        </p>
        
        <!-- Tags -->
        <div class="mt-2 flex items-center gap-2 flex-wrap">
            <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded uppercase font-medium">
                {{ $item->type }}
            </span>
            @if($item->priority <= 3)
                <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-medium">
                    Prioritas Tinggi
                </span>
            @endif
            <span class="text-[10px] text-blue-600 font-medium group-hover:underline">
                Mulai Sekarang →
            </span>
        </div>
    </div>
</div>
