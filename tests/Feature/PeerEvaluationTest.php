<?php

namespace Tests\Feature;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PeerEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_inter_group_evaluation(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create(['email' => 'reviewer@test.com']);
        $otherStudent = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);
        $course->students()->attach([$student->id, $otherStudent->id]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);

        $rubric = Rubric::create(['project_id' => $project->id, 'name' => 'Grille']);
        $criterion = $rubric->criteria()->create([
            'label' => 'Qualité',
            'weight' => 100,
            'max_score' => 5,
            'sort_order' => 0,
        ]);

        $groupA = Group::create([
            'project_id' => $project->id,
            'created_by' => $student->id,
            'name' => 'Groupe A',
            'invite_code' => 'GRPA',
        ]);
        $groupA->members()->attach($student->id, ['is_leader' => true]);

        $groupB = Group::create([
            'project_id' => $project->id,
            'created_by' => $otherStudent->id,
            'name' => 'Groupe B',
            'invite_code' => 'GRPB',
        ]);
        $groupB->members()->attach($otherStudent->id, ['is_leader' => true]);

        app(PeerEvaluationSyncService::class)->syncForProject($project);

        $evaluation = PeerEvaluation::query()
            ->where('reviewer_id', $student->id)
            ->where('type', EvaluationType::Inter)
            ->where('reviewee_group_id', $groupB->id)
            ->firstOrFail();

        $this->actingAs($student)
            ->put(route('student.evaluations.update', $evaluation), [
                'scores' => [
                    $evaluation->id => [$criterion->id => 2],
                ],
            ])
            ->assertRedirect(route('student.evaluations.index'));

        $evaluation->refresh();
        $this->assertSame(PeerEvaluationStatus::Completed, $evaluation->status);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'peer_evaluation_id' => $evaluation->id,
            'rubric_criterion_id' => $criterion->id,
            'score' => 2,
        ]);
    }

    public function test_over_budget_intra_save_is_rejected(): void
    {
        [$reviewer, $criterion, $evaluations] = $this->intraSet(teammates: 4, maxScore: 10);

        $this->actingAs($reviewer)
            ->from(route('student.evaluations.show', $evaluations->first()))
            ->put(route('student.evaluations.update', $evaluations->first()), [
                'scores' => $this->allocation($evaluations, $criterion->id, [10, 10, 10, 10]),
            ])
            ->assertSessionHasErrors([
                'scores' => 'Le total des points dépasse le budget de 20 pour « Qualité ».',
            ]);

        foreach ($evaluations as $evaluation) {
            $this->assertSame(PeerEvaluationStatus::Pending, $evaluation->fresh()->status);
            $this->assertDatabaseMissing('peer_evaluation_scores', [
                'peer_evaluation_id' => $evaluation->id,
            ]);
        }
    }

    public function test_over_budget_inter_save_is_rejected(): void
    {
        [$reviewer, $criterion, $evaluations] = $this->interSet(otherGroups: 4, maxScore: 10);

        $this->actingAs($reviewer)
            ->from(route('student.evaluations.show', $evaluations->first()))
            ->put(route('student.evaluations.update', $evaluations->first()), [
                'scores' => $this->allocation($evaluations, $criterion->id, [10, 10, 10, 10]),
            ])
            ->assertSessionHasErrors([
                'scores' => 'Le total des points dépasse le budget de 20 pour « Qualité ».',
            ]);

        foreach ($evaluations as $evaluation) {
            $this->assertSame(PeerEvaluationStatus::Pending, $evaluation->fresh()->status);
            $this->assertDatabaseMissing('peer_evaluation_scores', [
                'peer_evaluation_id' => $evaluation->id,
            ]);
        }
    }

    public function test_distribution_inside_the_pool_is_stored(): void
    {
        [$reviewer, $criterion, $evaluations] = $this->intraSet(teammates: 4, maxScore: 10);

        $this->actingAs($reviewer)
            ->get(route('student.evaluations.show', $evaluations->first()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Evaluations/Show')
                ->where('evaluation.criteria.0.score', 0)
                ->where('evaluation.criteria.0.pool', 20)
                ->where('evaluation.criteria.0.max_score', 10)
                ->has('evaluation.targets', 4));

        $this->actingAs($reviewer)
            ->get(route('student.evaluations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('pending', 1)
                ->where('pending.0.type', 'intra')
                ->where('pending.0.target_count', 4)
                ->where('stats.pending', 1));

        $scores = [10, 6, 4, 0];

        $this->actingAs($reviewer)
            ->put(route('student.evaluations.update', $evaluations->first()), [
                'scores' => $this->allocation($evaluations, $criterion->id, $scores),
            ])
            ->assertRedirect(route('student.evaluations.index'));

        foreach ($evaluations->values() as $index => $evaluation) {
            $this->assertSame(PeerEvaluationStatus::Completed, $evaluation->fresh()->status);
            $this->assertDatabaseHas('peer_evaluation_scores', [
                'peer_evaluation_id' => $evaluation->id,
                'rubric_criterion_id' => $criterion->id,
                'score' => $scores[$index],
            ]);
        }

        $this->actingAs($reviewer)
            ->from(route('student.evaluations.show', $evaluations->first()))
            ->put(route('student.evaluations.update', $evaluations->first()), [
                'scores' => $this->allocation($evaluations, $criterion->id, [10, 10, 10, 10]),
            ])
            ->assertSessionHas('success', 'Cette évaluation est déjà enregistrée.');

        $this->assertDatabaseHas('peer_evaluation_scores', [
            'peer_evaluation_id' => $evaluations->last()->id,
            'score' => 0,
        ]);
    }

    public function test_student_cannot_rate_themselves(): void
    {
        [$reviewer, $criterion] = $this->intraSet(teammates: 2, maxScore: 10);

        $this->assertDatabaseMissing('peer_evaluations', [
            'reviewer_id' => $reviewer->id,
            'type' => EvaluationType::Intra->value,
            'reviewee_user_id' => $reviewer->id,
        ]);

        $evaluation = PeerEvaluation::create([
            'project_id' => $criterion->rubric->project_id,
            'reviewer_id' => $reviewer->id,
            'type' => EvaluationType::Intra,
            'reviewee_user_id' => $reviewer->id,
            'status' => PeerEvaluationStatus::Pending,
        ]);

        $this->actingAs($reviewer)
            ->get(route('student.evaluations.show', $evaluation))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->put(route('student.evaluations.update', $evaluation), [
                'scores' => [
                    $evaluation->id => [$criterion->id => 5],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('peer_evaluation_scores', [
            'peer_evaluation_id' => $evaluation->id,
        ]);
    }

    public function test_student_cannot_rate_their_own_group(): void
    {
        [$reviewer, $criterion, $evaluations, $ownGroup] = $this->interSet(
            otherGroups: 1,
            maxScore: 10,
        );

        $evaluation = PeerEvaluation::create([
            'project_id' => $criterion->rubric->project_id,
            'reviewer_id' => $reviewer->id,
            'type' => EvaluationType::Inter,
            'reviewee_group_id' => $ownGroup->id,
            'status' => PeerEvaluationStatus::Pending,
        ]);

        $this->actingAs($reviewer)
            ->put(route('student.evaluations.update', $evaluation), [
                'scores' => [
                    $evaluation->id => [$criterion->id => 5],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('peer_evaluation_scores', [
            'peer_evaluation_id' => $evaluation->id,
        ]);
        $this->assertCount(1, $evaluations);
    }

    public function test_student_cannot_submit_someone_elses_evaluation(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();

        $evaluation = PeerEvaluation::create([
            'project_id' => Project::create([
                'course_id' => Course::create([
                    'professor_id' => User::factory()->professor()->create()->id,
                    'title' => 'C',
                    'code' => 'X',
                    'join_code' => 'J',
                ])->id,
                'title' => 'P',
            ])->id,
            'reviewer_id' => $other->id,
            'type' => EvaluationType::Intra,
            'reviewee_user_id' => $student->id,
            'status' => PeerEvaluationStatus::Pending,
        ]);

        $this->actingAs($student)
            ->put(route('student.evaluations.update', $evaluation), ['scores' => []])
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: RubricCriterion, 2: Collection<int, PeerEvaluation>}
     */
    private function intraSet(int $teammates, int $maxScore): array
    {
        [$course, $project] = $this->courseAndProject();
        $reviewer = User::factory()->create(['name' => 'Revue']);
        $members = collect(range(1, $teammates))
            ->map(fn (int $index) => User::factory()->create(['name' => 'Coequipier '.$index]));

        $course->students()->attach(
            collect([$reviewer->id])->merge($members->pluck('id'))->all(),
        );

        $criterion = $this->criterion($project, $maxScore);
        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $reviewer->id,
            'name' => 'Equipe',
        ]);
        $group->members()->attach($reviewer->id, ['is_leader' => true]);

        foreach ($members as $member) {
            $group->members()->attach($member->id);
        }

        app(PeerEvaluationSyncService::class)->syncForProject($project);

        return [$reviewer, $criterion, $this->evaluationsFor($reviewer, EvaluationType::Intra)];
    }

    /**
     * @return array{0: User, 1: RubricCriterion, 2: Collection<int, PeerEvaluation>, 3: Group}
     */
    private function interSet(int $otherGroups, int $maxScore): array
    {
        [$course, $project] = $this->courseAndProject();
        $reviewer = User::factory()->create(['name' => 'Revue']);
        $others = collect(range(1, $otherGroups))
            ->map(fn (int $index) => User::factory()->create(['name' => 'Autre '.$index]));

        $course->students()->attach(
            collect([$reviewer->id])->merge($others->pluck('id'))->all(),
        );

        $criterion = $this->criterion($project, $maxScore);
        $ownGroup = Group::create([
            'project_id' => $project->id,
            'created_by' => $reviewer->id,
            'name' => 'Mon groupe',
        ]);
        $ownGroup->members()->attach($reviewer->id, ['is_leader' => true]);

        foreach ($others as $index => $other) {
            $group = Group::create([
                'project_id' => $project->id,
                'created_by' => $other->id,
                'name' => 'Groupe '.($index + 1),
            ]);
            $group->members()->attach($other->id, ['is_leader' => true]);
        }

        app(PeerEvaluationSyncService::class)->syncForProject($project);

        return [
            $reviewer,
            $criterion,
            $this->evaluationsFor($reviewer, EvaluationType::Inter),
            $ownGroup,
        ];
    }

    /**
     * @return array{0: Course, 1: Project}
     */
    private function courseAndProject(): array
    {
        $course = Course::create([
            'professor_id' => User::factory()->professor()->create()->id,
            'title' => 'Cours',
            'code' => 'C-'.str()->upper(str()->random(6)),
            'join_code' => str()->upper(str()->random(8)),
        ]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);

        return [$course, $project];
    }

    private function criterion(Project $project, int $maxScore): RubricCriterion
    {
        $rubric = Rubric::create([
            'project_id' => $project->id,
            'name' => 'Grille',
        ]);

        return $rubric->criteria()->create([
            'label' => 'Qualité',
            'weight' => 100,
            'max_score' => $maxScore,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return Collection<int, PeerEvaluation>
     */
    private function evaluationsFor(User $reviewer, EvaluationType $type): Collection
    {
        return PeerEvaluation::query()
            ->where('reviewer_id', $reviewer->id)
            ->where('type', $type)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, PeerEvaluation>  $evaluations
     * @param  array<int, int>  $scores
     * @return array<int, array<int, int>>
     */
    private function allocation(Collection $evaluations, int $criterionId, array $scores): array
    {
        $payload = [];

        foreach ($evaluations->values() as $index => $evaluation) {
            $payload[$evaluation->id] = [$criterionId => $scores[$index]];
        }

        return $payload;
    }
}
