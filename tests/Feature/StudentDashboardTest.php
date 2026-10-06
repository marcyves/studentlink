<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
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

    public function test_unknown_course_code_shows_a_dashboard_error(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.courses.join'), ['join_code' => 'INCONNU']);

        $this->assertNotSame(404, $response->status());
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'join_code' => 'Aucun cours ne correspond à ce code.',
            ])
            ->assertSessionMissing('success');

        $this->assertFalse($student->courses()->exists());
    }

    public function test_already_enrolled_student_sees_a_dashboard_error(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Algèbre',
            'code' => 'ALG-1',
            'join_code' => 'ABC123',
        ]);
        $student->courses()->attach($course->id);

        $response = $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.courses.join'), ['join_code' => 'abc123']);

        $this->assertNotSame(404, $response->status());
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'join_code' => 'Vous êtes déjà inscrit à ce cours.',
            ])
            ->assertSessionMissing('success');

        $this->assertSame(1, $student->courses()->where('courses.id', $course->id)->count());
    }

    public function test_unknown_group_code_shows_a_dashboard_error(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.groups.join'), ['invite_code' => 'NOPES']);

        $this->assertNotSame(404, $response->status());
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'invite_code' => 'Aucun groupe ne correspond à ce code.',
            ])
            ->assertSessionMissing('success');

        $this->assertFalse($student->groups()->exists());
        $this->assertFalse($student->courses()->exists());
    }

    public function test_student_already_in_group_sees_a_dashboard_error(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Histoire',
            'code' => 'HIS-1',
            'join_code' => 'HISTO',
        ]);
        $project = $course->projects()->create([
            'title' => 'Exposé',
        ]);
        $student->courses()->attach($course->id);
        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $student->id,
            'name' => 'Groupe Alpha',
            'invite_code' => 'ALPHA1',
        ]);
        $group->members()->attach($student->id, ['is_leader' => true]);

        $response = $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.groups.join'), ['invite_code' => 'alpha1']);

        $this->assertNotSame(404, $response->status());
        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'invite_code' => 'Vous faites déjà partie de ce groupe.',
            ])
            ->assertSessionMissing('success');

        $this->assertSame(1, $group->members()->where('users.id', $student->id)->count());
        $this->assertSame(1, $student->courses()->where('courses.id', $course->id)->count());
    }
}
