<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RubricController extends Controller
{
    public function edit(Project $project): Response
    {
        $this->authorizeProject($project);

        $project->load(['course', 'rubric.criteria']);

        $rubric = $project->rubric ?? $project->rubric()->create([
            'name' => 'Grille d\'évaluation',
        ]);

        $rubric->load('criteria');

        return Inertia::render('Professor/Rubrics/Edit', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'course' => $project->course->title,
            ],
            'rubric' => [
                'id' => $rubric->id,
                'name' => $rubric->name,
                'criteria' => $rubric->criteria->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->label,
                    'weight' => $c->weight,
                    'max_score' => $c->max_score,
                    'sort_order' => $c->sort_order,
                ]),
            ],
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.label' => ['required', 'string', 'max:255'],
            'criteria.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'criteria.*.max_score' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $rubric = $project->rubric ?? $project->rubric()->create(['name' => $validated['name']]);
        $rubric->update(['name' => $validated['name']]);

        $rubric->criteria()->delete();

        foreach ($validated['criteria'] as $index => $criterion) {
            $rubric->criteria()->create([
                'label' => $criterion['label'],
                'weight' => $criterion['weight'],
                'max_score' => $criterion['max_score'],
                'sort_order' => $index,
            ]);
        }

        return redirect()
            ->route('professor.dashboard')
            ->with('success', 'Grille enregistrée.');
    }

    private function authorizeProject(Project $project): void
    {
        if ($project->course->professor_id !== auth()->id()) {
            abort(403);
        }
    }
}
