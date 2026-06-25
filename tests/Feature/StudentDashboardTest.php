<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_join_course_and_create_group(): void
    {
        $professor = User::factory()->professor()->create([
            'email' => 'prof@school.test',
        ]);
        $student = User::factory()->create([
            'email' => 'alice@school.test',
        ]);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Test Course',
            'code' => 'TEST-101',
            'join_code' => 'ABC123',
        ]);

        $project = $course->projects()->create([
            'title' => 'Projet test',
        ]);

        $this->actingAs($student)
            ->post(route('student.courses.join'), ['join_code' => 'ABC123'])
            ->assertRedirect();

        $this->assertTrue($student->fresh()->courses()->where('courses.id', $course->id)->exists());

        $this->actingAs($student)
            ->post(route('student.groups.store'), [
                'project_id' => $project->id,
                'name' => 'Groupe Test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('groups', [
            'project_id' => $project->id,
            'name' => 'Groupe Test',
        ]);
    }
}
