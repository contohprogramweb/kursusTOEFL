@props(['plan'])

<div class="bg-white rounded-xl shadow-md p-6 h-full flex flex-col justify-between" 
     role="article"
     aria-label="Study Plan: {{ $plan->name }}">
    <div>
        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">{{ $plan->name }}</h3>
                <p class="text-sm text-gray-500">
                    <span aria-hidden="true">📅</span>
                    {{ $plan->days_remaining }} hari tersisa
                </p>
            </div>
            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-semibold"
                  role="status"
                  aria-label="Status: Active">
                Active
            </span>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4" aria-label="Progress: {{ $plan->progress_percentage }}%">
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Progress</span>
                <span class="font-bold text-blue-600">{{ $plan->progress_percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5" role="progressbar" 
                 aria-valuenow="{{ $plan->progress_percentage }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" 
                     style="width: {{ $plan->progress_percentage }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                {{ $plan->completed_tasks }} dari {{ $plan->total_tasks }} tugas selesai
            </p>
        </div>

        <!-- Next Task -->
        @if($plan->next_task)
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100" 
                 role="region" 
                 aria-label="Tugas Selanjutnya">
                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Tugas Selanjutnya</p>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-400" aria-hidden="true"></span>
                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $plan->next_task->title }}">
                        {{ $plan->next_task->title }}
                    </p>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded uppercase">
                        {{ $plan->next_task->type }}
                    </span>
                </div>
            </div>
        @else
            <div class="text-center py-4 bg-green-50 rounded-lg" role="status" aria-label="Semua tugas selesai">
                <span class="text-2xl" aria-hidden="true">🎉</span>
                <p class="text-green-600 font-medium text-sm">Semua tugas selesai!</p>
            </div>
        @endif
    </div>

    <!-- Footer Link -->
    <a href="{{ route('study-plan.show', $plan->id) ?? '#' }}" 
       class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-800 font-medium transition"
       aria-label="Lihat detail study plan {{ $plan->name }}">
        Lihat Detail →
    </a>
</div>
