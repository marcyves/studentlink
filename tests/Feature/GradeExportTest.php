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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_export_project_grades_csv(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);
        $course->students()->attach($student->id);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet export',
        ]);

        $this->actingAs($professor)
            ->get(route('professor.grades.export', $project))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_other_professor_cannot_export_project(): void
    {
        $owner = User::factory()->professor()->create();
        $intruder = User::factory()->professor()->create();

        $course = Course::create([
            'professor_id' => $owner->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);

        $this->actingAs($intruder)
            ->get(route('professor.grades.export', $project))
            ->assertForbidden();
    }

    public function test_student_cannot_export_grades(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);

        $this->actingAs($student)
            ->get(route('professor.grades.export', $project))
            ->assertForbidden();
    }

    public function test_export_includes_weighted_averages_by_student_and_by_group(): void
    {
        $professor = User::factory()->professor()->create();
        $alice = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $bob = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        $charlie = User::factory()->create(['name' => 'Charlie', 'email' => 'charlie@example.com']);
        $diane = User::factory()->create(['name' => 'Diane', 'email' => 'diane@example.com']);
        $pendingReviewer = User::factory()->create(['name' => 'Hors calcul', 'email' => 'pending@example.com']);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet export',
        ]);

        $rubric = Rubric::create(['project_id' => $project->id, 'name' => 'Grille']);
        $quality = $rubric->criteria()->create([
            'label' => 'Qualité',
            'weight' => 40,
            'max_score' => 8,
            'sort_order' => 0,
        ]);
        $collaboration = $rubric->criteria()->create([
            'label' => 'Collaboration',
            'weight' => 60,
            'max_score' => 4,
            'sort_order' => 1,
        ]);

        $alpha = Group::create([
            'project_id' => $project->id,
            'created_by' => $alice->id,
            'name' => 'Alpha',
            'invite_code' => 'ALPHA',
        ]);
        $alpha->members()->attach([
            $alice->id => ['is_leader' => true],
            $bob->id => ['is_leader' => false],
            $charlie->id => ['is_leader' => false],
            $diane->id => ['is_leader' => false],
        ]);

        $beta = Group::create([
            'project_id' => $project->id,
            'created_by' => $pendingReviewer->id,
            'name' => 'Beta',
            'invite_code' => 'BETA',
        ]);
        $beta->members()->attach($pendingReviewer->id, ['is_leader' => true]);

        $this->completeEvaluation($project, $bob, EvaluationType::Intra, $alice, [
            $quality->id => 6,
            $collaboration->id => 4,
        ]);
        $this->completeEvaluation($project, $charlie, EvaluationType::Intra, $alice, [
            $quality->id => 8,
            $collaboration->id => 2,
        ]);
        $this->completeEvaluation($project, $bob, EvaluationType::Intra, $diane, [
            $quality->id => 8,
        ]);
        $this->completeEvaluation($project, $alice, EvaluationType::Inter, $beta, [
            $quality->id => 4,
            $collaboration->id => 2,
        ]);
        $this->completeEvaluation($project, $bob, EvaluationType::Inter, $beta, [
            $quality->id => 8,
            $collaboration->id => 4,
        ]);

        $pending = PeerEvaluation::create([
            'project_id' => $project->id,
            'reviewer_id' => $pendingReviewer->id,
            'type' => EvaluationType::Inter,
            'reviewee_group_id' => $beta->id,
            'status' => PeerEvaluationStatus::Pending,
        ]);
        $pending->scores()->create(['rubric_criterion_id' => $quality->id, 'score' => 2]);
        $pending->scores()->create(['rubric_criterion_id' => $collaboration->id, 'score' => 1]);

        $csv = $this->actingAs($professor)
            ->get(route('professor.grades.export', $project))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $lines = preg_split("/\r\n|\n|\r/", trim($csv));

        $this->assertSame(
            'Groupe;Étudiant;Email;"Type évaluation";Cible;Critère;Score;Max;"Poids %";Évaluateur;Date',
            $lines[0],
        );
        $this->assertStringContainsString(
            'Alpha;Alice;alice@example.com;Intra-groupe;Alice;Qualité;6;8;40;Bob;',
            $csv,
        );
        $this->assertStringContainsString(
            'Beta;—;—;Inter-groupe;Beta;Qualité;4;8;40;Alice;',
            $csv,
        );
        $this->assertStringContainsString('Moyenne pondérée par étudiant (intra-groupe)', $csv);
        $this->assertStringContainsString('Alpha;Alice;alice@example.com;80,00;2', $csv);
        $this->assertStringContainsString('Alpha;Diane;diane@example.com;40,00;1', $csv);
        $this->assertStringContainsString('Moyenne pondérée par groupe (inter-groupe)', $csv);
        $this->assertStringContainsString('Beta;75,00;2', $csv);
        $this->assertStringNotContainsString('Hors calcul', $csv);
        $this->assertStringNotContainsString('58,33', $csv);
    }

    /**
     * @param  array<int, int>  $scores
     */
    private function completeEvaluation(
        Project $project,
        User $reviewer,
        EvaluationType $type,
        User|Group $target,
        array $scores,
    ): void {
        $evaluation = PeerEvaluation::create([
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'type' => $type,
            'reviewee_user_id' => $target instanceof User ? $target->id : null,
            'reviewee_group_id' => $target instanceof Group ? $target->id : null,
            'status' => PeerEvaluationStatus::Completed,
            'submitted_at' => now(),
        ]);

        foreach ($scores as $criterionId => $score) {
            $evaluation->scores()->create([
                'rubric_criterion_id' => $criterionId,
                'score' => $score,
            ]);
        }
    }
}
