<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    /**
     * Display a listing of modules.
     */
    public function index(Request $request)
    {
        $query = Module::with('creator')->orderBy('order_index');

        // Filters
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        $modules = $query->paginate(20);

        return view('admin.modules.index', compact('modules'));
    }

    /**
     * Show the form for creating a new module.
     */
    public function create()
    {
        return view('admin.modules.create', [
            'sections' => ['reading', 'listening', 'speaking', 'writing'],
            'difficulties' => [1, 2, 3, 4, 5],
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    /**
     * Store a newly created module in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section' => ['required', Rule::in(['reading', 'listening', 'speaking', 'writing'])],
            'difficulty' => 'required|integer|min:1|max:5',
            'order_index' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $validated['created_by'] = Auth::id();

        $module = Module::create($validated);

        return redirect()->route('admin.modules.edit', $module)
            ->with('success', 'Module created successfully.');
    }

    /**
     * Display the specified module.
     */
    public function show(Module $module)
    {
        $module->load('contents');
        return view('admin.modules.show', compact('module'));
    }

    /**
     * Show the form for editing the specified module.
     */
    public function edit(Module $module)
    {
        return view('admin.modules.edit', [
            'module' => $module,
            'sections' => ['reading', 'listening', 'speaking', 'writing'],
            'difficulties' => [1, 2, 3, 4, 5],
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    /**
     * Update the specified module in storage.
     */
    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section' => ['required', Rule::in(['reading', 'listening', 'speaking', 'writing'])],
            'difficulty' => 'required|integer|min:1|max:5',
            'order_index' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $module->update($validated);

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module updated successfully.');
    }

    /**
     * Remove the specified module from storage.
     */
    public function destroy(Module $module)
    {
        $module->delete();

        return redirect()->route('admin.modules.index')
            ->with('success', 'Module deleted successfully.');
    }
}
