<?php

namespace App\Http\Controllers\Student;

use App\Enums\CourseActivityAction;
use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Http\Controllers\Controller;
use App\Models\PeerEvaluation;
use App\Services\CourseActivityRecorder;
use App\Services\PeerEvaluationSyncService;
use App\Support\DeliverablePresenter;
use App\Support\PeerScorePool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PeerEvaluationController extends Controller
{
    public function __construct(
        private PeerEvaluationSyncService $syncService,
        private DeliverablePresenter $deliverables,
        private CourseActivityRecorder $activities,
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
                'revieweeGroup.members',
                'revieweeUser',
            ])
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->get()
            ->reject(fn (PeerEvaluation $evaluation) => $this->reviewsSelfOrOwnGroup($evaluation))
            ->map(fn (PeerEvaluation $evaluation) => $this->formatEvaluation($evaluation));

        $sets = $this->groupIntoSets($evaluations);
        $pending = $sets->where('status', PeerEvaluationStatus::Pending->value);
        $completed = $sets->where('status', PeerEvaluationStatus::Completed->value);

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
        $this->assertNotReviewingSelfOrOwnGroup($evaluation);

        $evaluation->load([
            'project.rubric.criteria',
            'project.course',
            'revieweeGroup.submission',
            'revieweeGroup.members',
            'revieweeUser',
            'scores',
        ]);

        return Inertia::render('Student/Evaluations/Show', [
            'evaluation' => $this->formatEvaluation(
                $evaluation,
                includeCriteria: true,
                set: $this->evaluationSet($evaluation),
            ),
        ]);
    }

    public function update(Request $request, PeerEvaluation $evaluation): RedirectResponse
    {
        $this->authorizeEvaluation($evaluation);
        $this->assertNotReviewingSelfOrOwnGroup($evaluation);

        $set = $this->evaluationSet($evaluation);

        if ($set->isEmpty() || $set->every(fn (PeerEvaluation $item) => ! $item->isPending())) {
            return back()->with('success', __('Cette évaluation est déjà enregistrée.'));
        }

        $evaluation->loadMissing('project.rubric.criteria');
        $criteria = $evaluation->project->rubric?->criteria ?? collect();

        if ($criteria->isEmpty()) {
            abort(422, __('Aucune grille de notation configurée pour ce projet.'));
        }

        $pending = $set->filter(fn (PeerEvaluation $item) => $item->isPending())->values();
        $rules = ['scores' => ['required', 'array']];

        foreach ($pending as $item) {
            $rules["scores.{$item->id}"] = ['required', 'array'];

            foreach ($criteria as $criterion) {
                $rules["scores.{$item->id}.{$criterion->id}"] = [
                    'required',
                    'integer',
                    'min:0',
                    "max:{$criterion->max_score}",
                ];
            }
        }

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($pending, $set, $criteria): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $given = collect(array_keys($validator->getData()['scores'] ?? []))
                ->map(fn ($id) => (int) $id);
            $allowed = $pending->pluck('id');

            if ($given->diff($allowed)->isNotEmpty() || $allowed->diff($given)->isNotEmpty()) {
                $validator->errors()->add(
                    'scores',
                    __('Indiquez une note pour chaque cible de cette série.'),
                );

                return;
            }

            $completed = $set->reject(fn (PeerEvaluation $item) => $item->isPending());
            $scores = $validator->getData()['scores'];

            foreach ($criteria as $criterion) {
                $pool = PeerScorePool::size($set->count(), (int) $criterion->max_score);
                $sum = $completed->sum(function (PeerEvaluation $item) use ($criterion) {
                    return (int) ($item->scores->firstWhere('rubric_criterion_id', $criterion->id)?->score ?? 0);
                });

                foreach ($pending as $item) {
                    $sum += (int) $scores[$item->id][$criterion->id];
                }

                if ($sum > $pool) {
                    $validator->errors()->add(
                        'scores',
                        __('Le total des points dépasse le budget de :pool pour « :criterion ».', [
                            'pool' => $pool,
                            'criterion' => $criterion->label,
                        ]),
                    );
                }
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($pending, $criteria, $validated) {
            foreach ($pending as $item) {
                foreach ($criteria as $criterion) {
                    $item->scores()->updateOrCreate(
                        ['rubric_criterion_id' => $criterion->id],
                        ['score' => $validated['scores'][$item->id][$criterion->id]],
                    );
                }

                $item->update([
                    'status' => PeerEvaluationStatus::Completed,
                    'submitted_at' => now(),
                ]);
            }
        });

        $evaluation->loadMissing('project.course');
        $this->activities->record(
            $request->user(),
            $evaluation->project->course,
            CourseActivityAction::SentEvaluation,
            $evaluation->project->title,
        );

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

    private function assertNotReviewingSelfOrOwnGroup(PeerEvaluation $evaluation): void
    {
        if (! $this->reviewsSelfOrOwnGroup($evaluation)) {
            return;
        }

        abort(403, $evaluation->type === EvaluationType::Intra
            ? __('Vous ne pouvez pas vous évaluer vous-même.')
            : __('Vous ne pouvez pas évaluer votre propre groupe.'));
    }

    private function reviewsSelfOrOwnGroup(PeerEvaluation $evaluation): bool
    {
        if ($evaluation->type === EvaluationType::Intra) {
            return (int) $evaluation->reviewee_user_id === (int) $evaluation->reviewer_id;
        }

        $evaluation->loadMissing('revieweeGroup.members');

        return (bool) $evaluation->revieweeGroup?->members->contains('id', $evaluation->reviewer_id);
    }

    /**
     * @return Collection<int, PeerEvaluation>
     */
    private function evaluationSet(PeerEvaluation $evaluation): Collection
    {
        return PeerEvaluation::query()
            ->where('reviewer_id', $evaluation->reviewer_id)
            ->where('project_id', $evaluation->project_id)
            ->where('type', $evaluation->type)
            ->with([
                'revieweeGroup.members',
                'revieweeGroup.submission',
                'revieweeUser',
                'scores',
                'project',
            ])
            ->get()
            ->reject(fn (PeerEvaluation $item) => $this->reviewsSelfOrOwnGroup($item))
            ->sortBy(fn (PeerEvaluation $item) => mb_strtolower($this->targetLabel($item) ?? ''))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $evaluations
     * @return Collection<int, array<string, mixed>>
     */
    private function groupIntoSets(Collection $evaluations): Collection
    {
        return $evaluations
            ->groupBy(fn (array $evaluation) => $evaluation['project']['id'].'-'.$evaluation['type'])
            ->map(function (Collection $items) {
                $first = $items->first();
                $entry = $items->firstWhere('status', PeerEvaluationStatus::Pending->value) ?? $first;
                $completed = $items->every(
                    fn (array $evaluation) => $evaluation['status'] === PeerEvaluationStatus::Completed->value,
                );

                return [
                    'id' => $entry['id'],
                    'type' => $first['type'],
                    'type_label' => $first['type_label'],
                    'status' => $completed
                        ? PeerEvaluationStatus::Completed->value
                        : PeerEvaluationStatus::Pending->value,
                    'status_label' => $completed
                        ? PeerEvaluationStatus::Completed->label()
                        : PeerEvaluationStatus::Pending->label(),
                    'project' => $first['project'],
                    'targets' => $items->pluck('target_label')->filter()->values(),
                    'target_count' => $items->count(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, PeerEvaluation>|null  $set
     * @return array<string, mixed>
     */
    private function formatEvaluation(PeerEvaluation $evaluation, bool $includeCriteria = false, ?Collection $set = null): array
    {
        $data = [
            'id' => $evaluation->id,
            'type' => $evaluation->type->value,
            'type_label' => $evaluation->type->label(),
            'status' => $evaluation->status->value,
            'status_label' => $evaluation->status->label(),
            'target_label' => $this->targetLabel($evaluation),
            'project' => [
                'id' => $evaluation->project->id,
                'title' => $evaluation->project->title,
                'ends_at' => $evaluation->project->ends_at?->format('d/m/Y H:i'),
            ],
            'submission_status' => $evaluation->revieweeGroup?->submission?->status->label(),
        ];

        if ($includeCriteria) {
            $criteria = $evaluation->project->rubric?->criteria ?? collect();
            $set ??= collect([$evaluation]);
            $existingScores = $evaluation->scores->keyBy('rubric_criterion_id');
            $targetCount = $set->count();

            $data['criteria'] = $criteria->map(fn ($criterion) => [
                'id' => $criterion->id,
                'label' => $criterion->label,
                'weight' => $criterion->weight,
                'max_score' => $criterion->max_score,
                'pool' => PeerScorePool::size($targetCount, (int) $criterion->max_score),
                'score' => $existingScores->get($criterion->id)?->score ?? 0,
            ])->values();

            $data['targets'] = $set->map(fn (PeerEvaluation $item) => [
                'id' => $item->id,
                'label' => $this->targetLabel($item),
                'status' => $item->status->value,
                'read_only' => ! $item->isPending(),
                'scores' => $criteria->mapWithKeys(fn ($criterion) => [
                    $criterion->id => (int) ($item->scores->firstWhere('rubric_criterion_id', $criterion->id)?->score ?? 0),
                ])->all(),
                'deliverable' => $item->type === EvaluationType::Inter
                    ? $this->deliverables->present($item->project, $item->revieweeGroup?->submission)
                    : null,
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

    private function targetLabel(PeerEvaluation $evaluation): ?string
    {
        return $evaluation->type === EvaluationType::Inter
            ? $evaluation->revieweeGroup?->name
            : $evaluation->revieweeUser?->name;
    }
}
