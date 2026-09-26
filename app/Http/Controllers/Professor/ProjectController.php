<?php

namespace App\Http\Controllers\Professor;

use App\Enums\DeliverableType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Project;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate(
            $this->projectRules(),
            $this->projectMessages(),
        );

        $course->projects()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'deliverable_type' => $validated['deliverable_type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', __('Projet créé.'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->ensureOwner($request, $project);

        $validated = $request->validate(
            $this->projectRules(),
            $this->projectMessages(),
        );

        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'deliverable_type' => $validated['deliverable_type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        return back()->with('success', __('Projet mis à jour.'));
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->ensureOwner($request, $project);

        $hasGroups = $project->groups()->exists();

        if ($hasGroups) {
            $request->validate([
                'purge' => ['accepted'],
            ], [
                'purge.accepted' => __('Confirmez la suppression du projet et de ses livrables.'),
            ]);
        }

        DB::transaction(function () use ($project) {
            $this->deleteDeliverableFiles($project);
            $project->delete();
        });

        return back()->with(
            'success',
            $hasGroups ? __('Projet et livrables effacés.') : __('Projet effacé.'),
        );
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
            'deliverable_type.required' => __('Choisissez un type de livrable.'),
        ]);

        $project->update([
            'deliverable_type' => $validated['deliverable_type'],
        ]);

        return back()->with('success', __('Type de livrable mis à jour.'));
    }

    private function ensureOwner(Request $request, Project $project): void
    {
        $project->loadMissing('course');

        if ($project->course->professor_id !== $request->user()->id) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function projectRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deliverable_type' => ['required', Rule::enum(DeliverableType::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function projectMessages(): array
    {
        return [
            'deliverable_type.required' => __('Choisissez un type de livrable.'),
        ];
    }

    private function deleteDeliverableFiles(Project $project): void
    {
        $paths = Submission::query()
            ->whereHas('group', fn ($query) => $query->where('project_id', $project->id))
            ->whereNotNull('file_path')
            ->pluck('file_path');

        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
