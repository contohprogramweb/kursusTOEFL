<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimulationTemplate;
use App\Models\SimulationTemplateSection;
use App\Models\Institution;
use App\Models\InstitutionSimulationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimulationTemplateController extends Controller
{
    /**
     * Display a listing of simulation templates.
     */
    public function index(Request $request)
    {
        $query = SimulationTemplate::with(['creator', 'institution'])
            ->withCount('sections');

        // Filter by mode if provided
        if ($request->has('mode')) {
            $query->where('mode', $request->mode);
        }

        // Filter by institution if provided
        if ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.simulations.index', compact('templates'));
    }

    /**
     * Show the form for creating a new simulation template.
     */
    public function create()
    {
        $institutions = Institution::active()->get();
        $modes = [
            SimulationTemplate::MODE_PRACTICE => 'Practice (Flexible timing)',
            SimulationTemplate::MODE_SCHEDULED => 'Scheduled (Fixed date/time)',
            SimulationTemplate::MODE_REALISTIC => 'Realistic (Full test conditions)',
            SimulationTemplate::MODE_FOCUS => 'Focus (Single section practice)',
        ];

        return view('admin.simulations.create', compact('institutions', 'modes'));
    }

    /**
     * Store a newly created simulation template in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mode' => ['required', Rule::in([
                SimulationTemplate::MODE_PRACTICE,
                SimulationTemplate::MODE_SCHEDULED,
                SimulationTemplate::MODE_REALISTIC,
                SimulationTemplate::MODE_FOCUS,
            ])],
            'total_duration' => 'required|integer|min:10',
            'institution_id' => 'nullable|exists:institutions,id',
            'is_default' => 'boolean',
            'sections' => 'nullable|array',
            'sections.*.section' => 'required_with:sections|in:reading,listening,speaking,writing',
            'sections.*.order_index' => 'required_with:sections|integer|min:0',
            'sections.*.duration_minutes' => 'required_with:sections|integer|min:1',
            'sections.*.question_count' => 'nullable|integer|min:0',
            'sections.*.break_after' => 'boolean',
            'sections.*.break_duration' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Create template
            $template = SimulationTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'mode' => $validated['mode'],
                'total_duration' => $validated['total_duration'],
                'institution_id' => $validated['institution_id'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Create sections if provided
            if (!empty($validated['sections'])) {
                foreach ($validated['sections'] as $sectionData) {
                    SimulationTemplateSection::create([
                        'template_id' => $template->id,
                        'section' => $sectionData['section'],
                        'order_index' => $sectionData['order_index'],
                        'duration_minutes' => $sectionData['duration_minutes'],
                        'question_count' => $sectionData['question_count'] ?? 0,
                        'break_after' => $sectionData['break_after'] ?? false,
                        'break_duration' => $sectionData['break_duration'] ?? 0,
                    ]);
                }
            }

            return $template;
        });

        return redirect()->route('admin.simulations.index')
            ->with('success', 'Simulation template created successfully.');
    }

    /**
     * Display the specified simulation template.
     */
    public function show(SimulationTemplate $template)
    {
        $template->load(['sections', 'creator', 'institution']);
        
        return view('admin.simulations.show', compact('template'));
    }

    /**
     * Show the form for editing the specified simulation template.
     */
    public function edit(SimulationTemplate $template)
    {
        if (!$template->canBeDeleted()) {
            return redirect()->route('admin.simulations.index')
                ->with('error', 'Default templates cannot be edited.');
        }

        $institutions = Institution::active()->get();
        $modes = [
            SimulationTemplate::MODE_PRACTICE => 'Practice (Flexible timing)',
            SimulationTemplate::MODE_SCHEDULED => 'Scheduled (Fixed date/time)',
            SimulationTemplate::MODE_REALISTIC => 'Realistic (Full test conditions)',
            SimulationTemplate::MODE_FOCUS => 'Focus (Single section practice)',
        ];

        $template->load('sections');

        return view('admin.simulations.edit', compact('template', 'institutions', 'modes'));
    }

    /**
     * Update the specified simulation template in storage.
     */
    public function update(Request $request, SimulationTemplate $template)
    {
        if (!$template->canBeDeleted()) {
            return redirect()->route('admin.simulations.index')
                ->with('error', 'Default templates cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mode' => ['required', Rule::in([
                SimulationTemplate::MODE_PRACTICE,
                SimulationTemplate::MODE_SCHEDULED,
                SimulationTemplate::MODE_REALISTIC,
                SimulationTemplate::MODE_FOCUS,
            ])],
            'total_duration' => 'required|integer|min:10',
            'institution_id' => 'nullable|exists:institutions,id',
            'is_default' => 'boolean',
            'sections' => 'nullable|array',
            'sections.*.id' => 'nullable|exists:simulation_template_sections,id',
            'sections.*.section' => 'required_with:sections|in:reading,listening,speaking,writing',
            'sections.*.order_index' => 'required_with:sections|integer|min:0',
            'sections.*.duration_minutes' => 'required_with:sections|integer|min:1',
            'sections.*.question_count' => 'nullable|integer|min:0',
            'sections.*.break_after' => 'boolean',
            'sections.*.break_duration' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $template) {
            // Update template
            $template->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'mode' => $validated['mode'],
                'total_duration' => $validated['total_duration'],
                'institution_id' => $validated['institution_id'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
            ]);

            // Update sections if provided
            if (!empty($validated['sections'])) {
                // Get existing section IDs
                $existingIds = $template->sections->pluck('id')->toArray();
                $updatedIds = [];

                foreach ($validated['sections'] as $sectionData) {
                    if (!empty($sectionData['id'])) {
                        // Update existing section
                        $section = SimulationTemplateSection::find($sectionData['id']);
                        if ($section && $section->template_id === $template->id) {
                            $section->update([
                                'section' => $sectionData['section'],
                                'order_index' => $sectionData['order_index'],
                                'duration_minutes' => $sectionData['duration_minutes'],
                                'question_count' => $sectionData['question_count'] ?? 0,
                                'break_after' => $sectionData['break_after'] ?? false,
                                'break_duration' => $sectionData['break_duration'] ?? 0,
                            ]);
                            $updatedIds[] = $section->id;
                        }
                    } else {
                        // Create new section
                        $newSection = SimulationTemplateSection::create([
                            'template_id' => $template->id,
                            'section' => $sectionData['section'],
                            'order_index' => $sectionData['order_index'],
                            'duration_minutes' => $sectionData['duration_minutes'],
                            'question_count' => $sectionData['question_count'] ?? 0,
                            'break_after' => $sectionData['break_after'] ?? false,
                            'break_duration' => $sectionData['break_duration'] ?? 0,
                        ]);
                        $updatedIds[] = $newSection->id;
                    }
                }

                // Delete sections that are no longer present
                $toDelete = array_diff($existingIds, $updatedIds);
                SimulationTemplateSection::destroy($toDelete);
            }
        });

        return redirect()->route('admin.simulations.index')
            ->with('success', 'Simulation template updated successfully.');
    }

    /**
     * Remove the specified simulation template from storage.
     */
    public function destroy(SimulationTemplate $template)
    {
        if (!$template->canBeDeleted()) {
            return redirect()->route('admin.simulations.index')
                ->with('error', 'Default templates cannot be deleted.');
        }

        $template->delete();

        return redirect()->route('admin.simulations.index')
            ->with('success', 'Simulation template deleted successfully.');
    }

    /**
     * Assign template to an institution (B2B).
     */
    public function assignToInstitution(Request $request, SimulationTemplate $template)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'is_required' => 'boolean',
        ]);

        InstitutionSimulationTemplate::updateOrCreate(
            [
                'institution_id' => $validated['institution_id'],
                'template_id' => $template->id,
            ],
            [
                'is_required' => $validated['is_required'] ?? false,
                'assigned_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Template assigned to institution successfully.');
    }

    /**
     * Remove template assignment from an institution.
     */
    public function removeFromInstitution(SimulationTemplate $template, $institutionId)
    {
        $assignment = InstitutionSimulationTemplate::where('template_id', $template->id)
            ->where('institution_id', $institutionId)
            ->first();

        if ($assignment) {
            $assignment->delete();
        }

        return back()->with('success', 'Template removed from institution successfully.');
    }

    /**
     * API: Get available templates for a user/institution.
     */
    public function apiAvailableTemplates(Request $request): JsonResponse
    {
        $user = auth()->user();
        $institutionId = $user->profile?->institution_id ?? null;

        $query = SimulationTemplate::with('sections')
            ->active();

        if ($institutionId) {
            $query->scopeForInstitution($institutionId);
        }

        $templates = $query->get();

        return response()->json([
            'success' => true,
            'data' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'mode' => $template->mode,
                    'total_duration' => $template->total_duration,
                    'sections_count' => $template->sections->count(),
                    'sections' => $template->sections->map(fn($s) => [
                        'section' => $s->section,
                        'duration_minutes' => $s->duration_minutes,
                        'question_count' => $s->question_count,
                        'has_break' => $s->hasBreak(),
                        'break_duration' => $s->break_duration,
                    ]),
                ];
            }),
        ]);
    }
}
