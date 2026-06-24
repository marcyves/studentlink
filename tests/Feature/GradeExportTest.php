<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
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
}
