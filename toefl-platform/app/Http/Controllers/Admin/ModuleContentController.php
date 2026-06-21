<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ModuleContentController extends Controller
{
    /**
     * Store a newly created content in storage.
     */
    public function store(Request $request, Module $module)
    {
        $validated = $request->validate([
            'content_type' => ['required', Rule::in(['text', 'video', 'audio', 'infographic', 'quiz'])],
            'title' => 'required|string|max:255',
            'content_data' => 'required|array',
            'order_index' => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('modules/' . $module->id, 'public');
            $validated['content_data']['file_url'] = Storage::url($path);
            $validated['content_data']['file_name'] = $file->getClientOriginalName();
        }

        $validated['module_id'] = $module->id;

        $content = ModuleContent::create($validated);

        return redirect()->route('admin.modules.edit', $module)
            ->with('success', 'Content added successfully.');
    }

    /**
     * Update the specified content in storage.
     */
    public function update(Request $request, ModuleContent $content)
    {
        $validated = $request->validate([
            'content_type' => ['required', Rule::in(['text', 'video', 'audio', 'infographic', 'quiz'])],
            'title' => 'required|string|max:255',
            'content_data' => 'required|array',
            'order_index' => 'required|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'file' => 'nullable|file|max:10240',
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if (isset($content->content_data['file_url'])) {
                $oldPath = str_replace('/storage/', '', $content->content_data['file_url']);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('file');
            $path = $file->store('modules/' . $content->module_id, 'public');
            $validated['content_data']['file_url'] = Storage::url($path);
            $validated['content_data']['file_name'] = $file->getClientOriginalName();
        }

        $content->update($validated);

        return redirect()->route('admin.modules.edit', $content->module)
            ->with('success', 'Content updated successfully.');
    }

    /**
     * Remove the specified content from storage.
     */
    public function destroy(ModuleContent $content)
    {
        $module = $content->module;

        // Delete associated file if exists
        if (isset($content->content_data['file_url'])) {
            $path = str_replace('/storage/', '', $content->content_data['file_url']);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $content->delete();

        return redirect()->route('admin.modules.edit', $module)
            ->with('success', 'Content deleted successfully.');
    }

    /**
     * Reorder contents within a module.
     */
    public function reorder(Request $request, Module $module)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:module_contents,id',
            'orders.*.order_index' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            ModuleContent::where('id', $order['id'])->update(['order_index' => $order['order_index']]);
        }

        return response()->json(['success' => true]);
    }
}
