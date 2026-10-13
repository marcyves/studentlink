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
use App\Support\PeerScoreSignal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PeerScoreSignalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_intra_reviewer_far_below_the_others_is_signalled(): void
    {
        [$project, $criterion, $alignedA, $alignedB, $outlier, $first, $second] = $this->intraProject();

        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 5, $second);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 5, $second);
        $this->score($project, $outlier, EvaluationType::Intra, $criterion, 3, $first);
        $this->score($project, $outlier, EvaluationType::Intra, $criterion, 2, $second);

        $signals = PeerScoreSignal::forProjects([$project->id])[$project->id];

        $this->assertCount(1, $signals);
        $this->assertSame($outlier->id, $signals[0]['reviewer_id']);
        $this->assertSame('Inès', $signals[0]['reviewer_name']);
        $this->assertSame('intra', $signals[0]['type']);
        $this->assertSame('low', $signals[0]['kind']);
        $this->assertSame(40, $signals[0]['gap']);
        $this->assertSame(2, $signals[0]['targets']);
    }

    public function test_inter_reviewer_who_reverses_the_order_is_signalled(): void
    {
        [$project, $criterion, $alignedA, $alignedB, $outlier, $groups] = $this->interProject();

        foreach ([[8, 5, 2], [8, 5, 2], [2, 5, 8]] as $index => $scores) {
            $reviewer = [$alignedA, $alignedB, $outlier][$index];

            foreach ($scores as $scoreIndex => $score) {
                $this->score($project, $reviewer, EvaluationType::Inter, $criterion, $score, group: $groups[$scoreIndex]);
            }
        }

        $signals = PeerScoreSignal::forProjects([$project->id])[$project->id];

        $this->assertCount(1, $signals);
        $this->assertSame($outlier->id, $signals[0]['reviewer_id']);
        $this->assertSame('inter', $signals[0]['type']);
        $this->assertSame('order', $signals[0]['kind']);
        $this->assertNull($signals[0]['gap']);
        $this->assertSame(3, $signals[0]['targets']);
    }

    public function test_agreement_a_single_target_and_pending_scores_stay_quiet(): void
    {
        [$project, $criterion, $alignedA, $alignedB, $outlier, $first, $second] = $this->intraProject();

        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $outlier, EvaluationType::Intra, $criterion, 1, $first);

        $this->assertSame([], PeerScoreSignal::forProjects([$project->id])[$project->id]);

        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 5, $second);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 5, $second);
        $pending = $this->score($project, $outlier, EvaluationType::Intra, $criterion, 1, $second);
        $pending->update(['status' => PeerEvaluationStatus::Pending, 'submitted_at' => null]);

        $this->assertSame([], PeerScoreSignal::forProjects([$project->id])[$project->id]);
    }

    public function test_professor_dashboard_shows_intra_and_inter_signals_for_their_project(): void
    {
        $professor = User::factory()->professor()->create();
        [$project, $criterion, $alignedA, $alignedB, $outlier, $first, $second] = $this->intraProject($professor);

        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $alignedA, EvaluationType::Intra, $criterion, 5, $second);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 8, $first);
        $this->score($project, $alignedB, EvaluationType::Intra, $criterion, 5, $second);
        $this->score($project, $outlier, EvaluationType::Intra, $criterion, 3, $first);
        $this->score($project, $outlier, EvaluationType::Intra, $criterion, 2, $second);

        $groups = [
            $this->group($project, $alignedA, 'Groupe A', 'GRPA'),
            $this->group($project, $alignedB, 'Groupe B', 'GRPB'),
            $this->group($project, $outlier, 'Groupe C', 'GRPC'),
        ];

        foreach ([[8, 5, 2], [8, 5, 2], [2, 5, 8]] as $index => $scores) {
            $reviewer = [$alignedA, $alignedB, $outlier][$index];

            foreach ($scores as $scoreIndex => $score) {
                $this->score($project, $reviewer, EvaluationType::Inter, $criterion, $score, group: $groups[$scoreIndex]);
            }
        }

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Professor/Dashboard')
                ->has('courses.0.projects.0.score_signals', 2)
                ->where('courses.0.projects.0.score_signals.0.reviewer_name', 'Inès')
                ->where('courses.0.projects.0.score_signals.0.type', 'intra')
                ->where('courses.0.projects.0.score_signals.0.kind', 'low')
                ->where('courses.0.projects.0.score_signals.1.reviewer_name', 'Inès')
                ->where('courses.0.projects.0.score_signals.1.type', 'inter')
                ->where('courses.0.projects.0.score_signals.1.kind', 'order'));

        $this->actingAs(User::factory()->professor()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Professor/Dashboard')
                ->has('courses', 0));
    }

    /**
     * @return array{0: Project, 1: RubricCriterion, 2: User, 3: User, 4: User, 5: User, 6: User}
     */
    private function intraProject(?User $professor = null): array
    {
        $professor ??= User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-SIG',
            'join_code' => 'SIG2026',
        ]);
        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);
        $rubric = Rubric::create(['project_id' => $project->id, 'name' => 'Grille']);
        $criterion = $rubric->criteria()->create([
            'label' => 'Qualité',
            'weight' => 100,
            'max_score' => 10,
            'sort_order' => 0,
        ]);

        return [
            $project,
            $criterion,
            User::factory()->create(['name' => 'Léa']),
            User::factory()->create(['name' => 'Marc']),
            User::factory()->create(['name' => 'Inès']),
            User::factory()->create(['name' => 'Cible 1']),
            User::factory()->create(['name' => 'Cible 2']),
        ];
    }

    /**
     * @return array{0: Project, 1: RubricCriterion, 2: User, 3: User, 4: User, 5: array<int, Group>}
     */
    private function interProject(): array
    {
        [$project, $criterion, $alignedA, $alignedB, $outlier] = $this->intraProject();

        return [
            $project,
            $criterion,
            $alignedA,
            $alignedB,
            $outlier,
            [
                $this->group($project, $alignedA, 'Groupe A', 'GRPA'),
                $this->group($project, $alignedB, 'Groupe B', 'GRPB'),
                $this->group($project, $outlier, 'Groupe C', 'GRPC'),
            ],
        ];
    }

    private function group(Project $project, User $owner, string $name, string $code): Group
    {
        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'name' => $name,
            'invite_code' => $code,
        ]);
        $group->members()->attach($owner->id, ['is_leader' => true]);

        return $group;
    }

    private function score(
        Project $project,
        User $reviewer,
        EvaluationType $type,
        RubricCriterion $criterion,
        int $score,
        ?User $reviewee = null,
        ?Group $group = null,
    ): PeerEvaluation {
        $evaluation = PeerEvaluation::create([
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'type' => $type,
            'reviewee_user_id' => $reviewee?->id,
            'reviewee_group_id' => $group?->id,
            'status' => PeerEvaluationStatus::Completed,
            'submitted_at' => now(),
        ]);
        $evaluation->scores()->create([
            'rubric_criterion_id' => $criterion->id,
            'score' => $score,
        ]);

        return $evaluation;
    }
}
