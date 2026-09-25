<?php

namespace Tests\Feature;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Models\Course;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_is_rejected_when_weights_do_not_sum_to_100(): void
    {
        [$professor, $project, $quality, $collaboration] = $this->rubricWithScores();

        $this->actingAs($professor)
            ->from(route('professor.rubrics.edit', $project))
            ->put(route('professor.rubrics.update', $project), [
                'name' => 'Grille modifiée',
                'criteria' => [
                    [
                        'id' => $quality->id,
                        'label' => 'Qualité',
                        'weight' => 70,
                        'max_score' => 10,
                    ],
                    [
                        'id' => $collaboration->id,
                        'label' => 'Collaboration',
                        'weight' => 70,
                        'max_score' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('professor.rubrics.edit', $project, absolute: false))
            ->assertSessionHasErrors([
                'criteria' => 'La somme des poids doit être égale à 100.',
            ]);

        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $quality->id,
            'label' => 'Qualité',
            'weight' => 40,
        ]);
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $collaboration->id,
            'label' => 'Collaboration',
            'weight' => 60,
        ]);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'rubric_criterion_id' => $quality->id,
            'score' => 8,
        ]);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'rubric_criterion_id' => $collaboration->id,
            'score' => 4,
        ]);
        $this->assertDatabaseMissing('rubrics', [
            'project_id' => $project->id,
            'name' => 'Grille modifiée',
        ]);
    }

    public function test_relabeling_or_reordering_criteria_preserves_scores(): void
    {
        [$professor, $project, $quality, $collaboration] = $this->rubricWithScores();

        $this->actingAs($professor)
            ->put(route('professor.rubrics.update', $project), [
                'name' => 'Grille révisée',
                'criteria' => [
                    [
                        'id' => $collaboration->id,
                        'label' => 'Esprit d\'équipe',
                        'weight' => 50,
                        'max_score' => 5,
                    ],
                    [
                        'label' => 'Présentation',
                        'weight' => 20,
                        'max_score' => 5,
                    ],
                    [
                        'id' => $quality->id,
                        'label' => 'Qualité du livrable',
                        'weight' => 30,
                        'max_score' => 10,
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('success', 'Grille enregistrée.');

        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $collaboration->id,
            'label' => 'Esprit d\'équipe',
            'weight' => 50,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $quality->id,
            'label' => 'Qualité du livrable',
            'weight' => 30,
            'sort_order' => 2,
        ]);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'rubric_criterion_id' => $quality->id,
            'score' => 8,
        ]);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'rubric_criterion_id' => $collaboration->id,
            'score' => 4,
        ]);

        $presentation = RubricCriterion::query()->where('label', 'Présentation')->first();
        $this->assertNotNull($presentation);
        $this->assertSame(1, $presentation->sort_order);
        $this->assertDatabaseMissing('peer_evaluation_scores', [
            'rubric_criterion_id' => $presentation->id,
        ]);
        $this->assertSame(3, RubricCriterion::query()->where('rubric_id', $quality->rubric_id)->count());
    }

    public function test_removing_a_criterion_drops_only_its_scores(): void
    {
        [$professor, $project, $quality, $collaboration] = $this->rubricWithScores();

        $this->actingAs($professor)
            ->put(route('professor.rubrics.update', $project), [
                'name' => 'Grille',
                'criteria' => [
                    [
                        'id' => $collaboration->id,
                        'label' => 'Collaboration',
                        'weight' => 100,
                        'max_score' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseMissing('rubric_criteria', ['id' => $quality->id]);
        $this->assertDatabaseMissing('peer_evaluation_scores', [
            'rubric_criterion_id' => $quality->id,
        ]);
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $collaboration->id,
            'label' => 'Collaboration',
            'weight' => 100,
        ]);
        $this->assertDatabaseHas('peer_evaluation_scores', [
            'rubric_criterion_id' => $collaboration->id,
            'score' => 4,
        ]);
        $this->assertSame(1, RubricCriterion::query()->count());
    }

    /**
     * @return array{0: User, 1: Project, 2: RubricCriterion, 3: RubricCriterion}
     */
    private function rubricWithScores(): array
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $reviewer = User::factory()->create();

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

        $rubric = Rubric::create([
            'project_id' => $project->id,
            'name' => 'Grille',
        ]);

        $quality = $rubric->criteria()->create([
            'label' => 'Qualité',
            'weight' => 40,
            'max_score' => 10,
            'sort_order' => 0,
        ]);

        $collaboration = $rubric->criteria()->create([
            'label' => 'Collaboration',
            'weight' => 60,
            'max_score' => 5,
            'sort_order' => 1,
        ]);

        $evaluation = PeerEvaluation::create([
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'type' => EvaluationType::Intra,
            'reviewee_user_id' => $student->id,
            'status' => PeerEvaluationStatus::Completed,
            'submitted_at' => now(),
        ]);

        $evaluation->scores()->create([
            'rubric_criterion_id' => $quality->id,
            'score' => 8,
        ]);
        $evaluation->scores()->create([
            'rubric_criterion_id' => $collaboration->id,
            'score' => 4,
        ]);

        return [$professor, $project, $quality, $collaboration];
    }
}
