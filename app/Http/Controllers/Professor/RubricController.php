<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RubricController extends Controller
{
    public function edit(Project $project): Response
    {
        $this->authorizeProject($project);

        $project->load(['course', 'rubric.criteria']);

        $rubric = $project->rubric ?? $project->rubric()->create([
            'name' => __('Grille d\'évaluation'),
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

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.id' => ['nullable', 'integer'],
            'criteria.*.label' => ['required', 'string', 'max:255'],
            'criteria.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
            'criteria.*.max_score' => ['required', 'integer', 'min:1', 'max:10'],
        ], [
            'name.required' => __('Le nom de la grille est obligatoire.'),
            'criteria.required' => __('Ajoutez au moins un critère.'),
            'criteria.min' => __('Ajoutez au moins un critère.'),
            'criteria.*.label.required' => __('Chaque critère doit avoir un libellé.'),
            'criteria.*.weight.required' => __('Chaque critère doit avoir un poids.'),
            'criteria.*.weight.integer' => __('Chaque poids doit être un nombre entier.'),
            'criteria.*.weight.min' => __('Chaque poids doit être compris entre 1 et 100.'),
            'criteria.*.weight.max' => __('Chaque poids doit être compris entre 1 et 100.'),
            'criteria.*.max_score.required' => __('Chaque critère doit avoir une note maximale.'),
            'criteria.*.max_score.integer' => __('Chaque note maximale doit être un nombre entier.'),
            'criteria.*.max_score.min' => __('Chaque note maximale doit être comprise entre 1 et 10.'),
            'criteria.*.max_score.max' => __('Chaque note maximale doit être comprise entre 1 et 10.'),
        ]);

        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $criteria = $validator->getData()['criteria'] ?? [];
            $sum = collect($criteria)->sum(fn (array $criterion) => (int) $criterion['weight']);

            if ($sum !== 100) {
                $validator->errors()->add(
                    'criteria',
                    __('La somme des poids doit être égale à 100.'),
                );
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($project, $validated): void {
            $rubric = $project->rubric ?? $project->rubric()->create([
                'name' => $validated['name'],
            ]);

            $rubric->update(['name' => $validated['name']]);
            $this->syncCriteria($rubric, $validated['criteria']);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', __('Grille enregistrée.'));
    }

    /**
     * Update criteria in place so existing scores stay attached to their criterion.
     * A criterion omitted from the payload is deleted, and only its scores go with it.
     *
     * @param  array<int, array{id?: int|null, label: string, weight: int, max_score: int}>  $criteria
     */
    private function syncCriteria(Rubric $rubric, array $criteria): void
    {
        $existing = $rubric->criteria()->get()->keyBy('id');
        $keptIds = [];

        foreach ($criteria as $criterion) {
            $id = $criterion['id'] ?? null;

            if ($id !== null && ! $existing->has($id)) {
                throw ValidationException::withMessages([
                    'criteria' => __('Un critère indiqué n\'appartient pas à cette grille.'),
                ]);
            }
        }

        foreach ($criteria as $index => $criterion) {
            $attributes = [
                'label' => $criterion['label'],
                'weight' => $criterion['weight'],
                'max_score' => $criterion['max_score'],
                'sort_order' => $index,
            ];

            $id = $criterion['id'] ?? null;

            if ($id !== null) {
                $model = $existing->get($id);
                $model->update($attributes);
                $keptIds[] = $model->id;

                continue;
            }

            $keptIds[] = $rubric->criteria()->create($attributes)->id;
        }

        $rubric->criteria()->whereNotIn('id', $keptIds)->delete();
    }

    private function authorizeProject(Project $project): void
    {
        if ($project->course->professor_id !== auth()->id()) {
            abort(403);
        }
    }
}
