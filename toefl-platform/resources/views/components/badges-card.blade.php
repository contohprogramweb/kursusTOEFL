@props(['streak', 'badges'])

<div class="bg-white rounded-xl shadow-md p-6 h-full" 
     role="article"
     aria-label="Pencapaian dan Lencana">
    <h3 class="text-lg font-semibold text-gray-700 mb-4" id="achievements-heading">
        Pencapaian 🏆
    </h3>

    <!-- Streak Counter -->
    <div class="flex items-center gap-2 mb-6 bg-orange-50 p-3 rounded-lg" 
         role="status"
         aria-labelledby="streak-label">
        <span class="text-2xl" aria-hidden="true">🔥</span>
        <div>
            <p class="text-xs text-orange-600 font-bold uppercase" id="streak-label">Streak</p>
            <p class="text-xl font-bold text-orange-700" aria-live="polite">{{ $streak }} Hari</p>
        </div>
    </div>

    <!-- Badges Grid -->
    <p class="text-xs text-gray-500 mb-3 font-semibold" id="badges-label">Lencana Terbaru</p>
    <div class="grid grid-cols-4 gap-2" role="list" aria-labelledby="badges-label">
        @forelse($badges as $badge)
            <div class="group relative flex flex-col items-center" 
                 role="listitem"
                 tabindex="0"
                 aria-label="{{ $badge->name }}: {{ $badge->description }}">
                <img src="{{ asset($badge->icon_path) }}" 
                     alt="{{ $badge->name }}" 
                     class="w-12 h-12 object-contain drop-shadow-sm"
                     loading="lazy">
                <span class="text-[10px] text-center text-gray-600 mt-1 truncate w-full">
                    {{ Str::limit($badge->name, 10) }}
                </span>
                
                <!-- Tooltip on Hover/Focus -->
                <div class="absolute bottom-full mb-2 hidden group-hover:block group-focus:block 
                            w-32 bg-gray-800 text-white text-xs rounded p-2 z-10 text-center shadow-lg
                            left-1/2 transform -translate-x-1/2"
                     role="tooltip"
                     aria-hidden="true">
                    <p class="font-semibold">{{ $badge->name }}</p>
                    <p class="text-[9px] text-gray-300 mt-1">{{ Str::limit($badge->description, 50) }}</p>
                    <p class="text-[8px] text-gray-400 mt-1">
                        Diperoleh: {{ \Carbon\Carbon::parse($badge->earned_at)->format('d M Y') }}
                    </p>
                </div>
            </div>
        @empty
            <div class="col-span-4 text-center py-4" role="status" aria-label="Belum ada lencana">
                <span class="text-2xl text-gray-300" aria-hidden="true">🔒</span>
                <p class="text-xs text-gray-400 mt-1">Belum ada lencana</p>
                <p class="text-[10px] text-gray-300">Selesaikan tantangan untuk mendapatkannya!</p>
            </div>
        @endforelse
    </div>

    <!-- View All Link (if needed) -->
    @if($badges->count() >= 4)
        <a href="{{ route('badges.index') ?? '#' }}" 
           class="mt-4 block text-center text-xs text-blue-600 hover:text-blue-800 font-medium transition"
           aria-label="Lihat semua lencana">
            Lihat Semua Lencana →
        </a>
    @endif
</div>
