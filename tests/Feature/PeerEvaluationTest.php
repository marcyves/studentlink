<?php

namespace Tests\Feature;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'scores' => [$criterion->id => 4],
            ])
            ->assertRedirect(route('student.evaluations.index'));

        $evaluation->refresh();
        $this->assertSame(PeerEvaluationStatus::Completed, $evaluation->status);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'peer_evaluation_id' => $evaluation->id,
            'rubric_criterion_id' => $criterion->id,
            'score' => 4,
        ]);
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
}
