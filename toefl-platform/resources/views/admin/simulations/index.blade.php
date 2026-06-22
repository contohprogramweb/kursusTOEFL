@extends('layouts.app')

@section('title', 'Simulation Templates Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Simulation Templates</h1>
        <a href="{{ route('admin.simulations.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition">
            + Create New Template
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.simulations.index') }}" class="mb-6 flex gap-4">
        <input type="text" name="search" placeholder="Search templates..." 
               value="{{ request('search') }}"
               class="border rounded-lg px-4 py-2 flex-1">
        <select name="mode" class="border rounded-lg px-4 py-2">
            <option value="">All Modes</option>
            <option value="practice" {{ request('mode') == 'practice' ? 'selected' : '' }}>Practice</option>
            <option value="scheduled" {{ request('mode') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="realistic" {{ request('mode') == 'realistic' ? 'selected' : '' }}>Realistic</option>
            <option value="focus" {{ request('mode') == 'focus' ? 'selected' : '' }}>Focus</option>
        </select>
        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            Filter
        </button>
    </form>

    <!-- Templates Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sections</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institution</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($templates as $template)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $template->name }}</div>
                        @if($template->description)
                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ $template->description }}</div>
                        @endif
                        @if($template->is_default)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                Default
                            </span>
                        @endif
                        @if($template->is_locked)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1 ml-1">
                                Locked
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($template->mode === 'practice') bg-blue-100 text-blue-800
                            @elseif($template->mode === 'scheduled') bg-purple-100 text-purple-800
                            @elseif($template->mode === 'realistic') bg-green-100 text-green-800
                            @else bg-orange-100 text-orange-800
                            @endif">
                            {{ ucfirst($template->mode) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $template->total_duration }} min
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $template->sections_count }} sections
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $template->institution?->name ?? 'Global' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($template->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($template->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.simulations.show', $template) }}" 
                           class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        @if($template->canBeDeleted())
                            <a href="{{ route('admin.simulations.edit', $template) }}" 
                               class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <form action="{{ route('admin.simulations.destroy', $template) }}" 
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this template?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        @else
                            <span class="text-gray-400 cursor-not-allowed">Protected</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No simulation templates found. Create your first template to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $templates->links() }}
    </div>
</div>
@endsection
