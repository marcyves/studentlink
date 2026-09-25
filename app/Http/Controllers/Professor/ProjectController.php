<?php

namespace App\Http\Controllers\Professor;

use App\Enums\DeliverableType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deliverable_type' => ['required', Rule::enum(DeliverableType::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ], [
            'deliverable_type.required' => 'Choisissez un type de livrable.',
        ]);

        $course->projects()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'deliverable_type' => $validated['deliverable_type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Projet créé.');
    }

    public function updateDeliverable(Request $request, Project $project): RedirectResponse
    {
        $project->loadMissing('course');

        if ($project->course->professor_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'deliverable_type' => ['required', Rule::enum(DeliverableType::class)],
        ], [
            'deliverable_type.required' => 'Choisissez un type de livrable.',
        ]);

        $project->update([
            'deliverable_type' => $validated['deliverable_type'],
        ]);

        return back()->with('success', 'Type de livrable mis à jour.');
    }
}
