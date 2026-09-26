<?php

namespace App\Http\Controllers\Student;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Http\Controllers\Controller;
use App\Models\PeerEvaluation;
use App\Services\PeerEvaluationSyncService;
use App\Support\DeliverablePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PeerEvaluationController extends Controller
{
    public function __construct(
        private PeerEvaluationSyncService $syncService,
        private DeliverablePresenter $deliverables,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $this->syncService->syncForUserProjects($user->id);

        $evaluations = PeerEvaluation::query()
            ->where('reviewer_id', $user->id)
            ->with([
                'project.rubric.criteria',
                'revieweeGroup.submission',
                'revieweeUser',
            ])
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (PeerEvaluation $evaluation) => $this->formatEvaluation($evaluation));

        $pending = $evaluations->where('status', PeerEvaluationStatus::Pending->value);
        $completed = $evaluations->where('status', PeerEvaluationStatus::Completed->value);

        return Inertia::render('Student/Evaluations/Index', [
            'pending' => $pending->values(),
            'completed' => $completed->values(),
            'stats' => [
                'pending' => $pending->count(),
                'completed' => $completed->count(),
            ],
        ]);
    }

    public function show(PeerEvaluation $evaluation): Response
    {
        $this->authorizeEvaluation($evaluation);

        $evaluation->load([
            'project.rubric.criteria',
            'project.course',
            'revieweeGroup.submission',
            'revieweeUser',
            'scores',
        ]);

        return Inertia::render('Student/Evaluations/Show', [
            'evaluation' => $this->formatEvaluation($evaluation, includeCriteria: true),
        ]);
    }

    public function update(Request $request, PeerEvaluation $evaluation): RedirectResponse
    {
        $this->authorizeEvaluation($evaluation);

        if (! $evaluation->isPending()) {
            return back()->with('success', __('Cette évaluation est déjà enregistrée.'));
        }

        $evaluation->load('project.rubric.criteria');
        $criteria = $evaluation->project->rubric?->criteria ?? collect();

        if ($criteria->isEmpty()) {
            abort(422, __('Aucune grille de notation configurée pour ce projet.'));
        }

        $rules = [
            'scores' => ['required', 'array'],
        ];

        foreach ($criteria as $criterion) {
            $rules["scores.{$criterion->id}"] = [
                'required',
                'integer',
                'min:1',
                "max:{$criterion->max_score}",
            ];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($evaluation, $validated) {
            foreach ($validated['scores'] as $criterionId => $score) {
                $evaluation->scores()->updateOrCreate(
                    ['rubric_criterion_id' => $criterionId],
                    ['score' => $score],
                );
            }

            $evaluation->update([
                'status' => PeerEvaluationStatus::Completed,
                'submitted_at' => now(),
            ]);
        });

        return redirect()
            ->route('student.evaluations.index')
            ->with('success', __('Évaluation enregistrée.'));
    }

    private function authorizeEvaluation(PeerEvaluation $evaluation): void
    {
        if ($evaluation->reviewer_id !== auth()->id()) {
            abort(403);
        }
    }

    private function formatEvaluation(PeerEvaluation $evaluation, bool $includeCriteria = false): array
    {
        $targetLabel = $evaluation->type === EvaluationType::Inter
            ? $evaluation->revieweeGroup?->name
            : $evaluation->revieweeUser?->name;

        $data = [
            'id' => $evaluation->id,
            'type' => $evaluation->type->value,
            'type_label' => $evaluation->type->label(),
            'status' => $evaluation->status->value,
            'status_label' => $evaluation->status->label(),
            'target_label' => $targetLabel,
            'project' => [
                'id' => $evaluation->project->id,
                'title' => $evaluation->project->title,
                'ends_at' => $evaluation->project->ends_at?->format('d/m/Y H:i'),
            ],
            'submission_status' => $evaluation->revieweeGroup?->submission?->status->label(),
        ];

        if ($includeCriteria) {
            $criteria = $evaluation->project->rubric?->criteria ?? collect();
            $existingScores = $evaluation->scores->keyBy('rubric_criterion_id');

            $data['criteria'] = $criteria->map(fn ($criterion) => [
                'id' => $criterion->id,
                'label' => $criterion->label,
                'weight' => $criterion->weight,
                'max_score' => $criterion->max_score,
                'score' => $existingScores->get($criterion->id)?->score ?? $criterion->max_score,
            ])->values();

            if ($evaluation->type === EvaluationType::Inter) {
                $data['deliverable'] = $this->deliverables->present(
                    $evaluation->project,
                    $evaluation->revieweeGroup?->submission,
                );
            }
        }

        return $data;
    }
}
