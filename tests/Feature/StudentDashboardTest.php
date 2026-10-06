<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

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

    public function test_student_without_a_course_only_sees_join_course(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = $this->makeCourse($professor, 'Algèbre', 'ALG-1', 'ALG001');
        $course->projects()->create(['title' => 'Devoir 1']);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('showJoinCourse', true)
                ->where('showCreateOrJoinGroup', false)
                ->has('enrolledCourses', 0)
                ->has('openProjects', 0));
    }

    public function test_enrolled_student_sees_the_course_and_the_group_box(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = $this->makeCourse($professor, 'Algèbre', 'ALG-1', 'ALG001');
        $course->projects()->create(['title' => 'Devoir 1']);
        $student->courses()->attach($course->id);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Dashboard')
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', true)
                ->has('enrolledCourses', 1)
                ->where('enrolledCourses.0.title', 'Algèbre')
                ->has('openProjects', 1)
                ->where('openProjects.0.title', 'Devoir 1')
                ->where('openProjects.0.course', 'Algèbre'));
    }

    public function test_group_box_hides_once_every_project_has_a_group(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = $this->makeCourse($professor, 'Algèbre', 'ALG-1', 'ALG001');
        $first = $course->projects()->create(['title' => 'Devoir 1']);
        $second = $course->projects()->create(['title' => 'Devoir 2']);
        $student->courses()->attach($course->id);
        $this->joinProjectGroup($student, $first, 'Groupe A');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', true)
                ->where('enrolledCourses.0.title', 'Algèbre')
                ->has('openProjects', 1)
                ->where('openProjects.0.title', 'Devoir 2'));

        $this->joinProjectGroup($student, $second, 'Groupe B');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', false)
                ->where('enrolledCourses.0.title', 'Algèbre')
                ->has('openProjects', 0));
    }

    public function test_student_in_several_courses_sees_each_name_until_every_project_has_a_group(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $algebra = $this->makeCourse($professor, 'Algèbre', 'ALG-1', 'ALG001');
        $history = $this->makeCourse($professor, 'Histoire', 'HIS-1', 'HIS001');
        $algebraProject = $algebra->projects()->create(['title' => 'Devoir 1']);
        $historyProject = $history->projects()->create(['title' => 'Exposé']);
        $student->courses()->attach([$algebra->id, $history->id]);
        $this->joinProjectGroup($student, $algebraProject, 'Groupe A');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', true)
                ->has('enrolledCourses', 2)
                ->where('enrolledCourses.0.title', 'Algèbre')
                ->where('enrolledCourses.1.title', 'Histoire')
                ->has('openProjects', 1)
                ->where('openProjects.0.title', 'Exposé')
                ->where('openProjects.0.course', 'Histoire'));

        $this->joinProjectGroup($student, $historyProject, 'Groupe B');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', false)
                ->has('enrolledCourses', 2)
                ->where('enrolledCourses.0.title', 'Algèbre')
                ->where('enrolledCourses.1.title', 'Histoire')
                ->has('openProjects', 0));
    }

    public function test_enrolled_course_without_projects_hides_the_group_box(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        $course = $this->makeCourse($professor, 'Philosophie', 'PHI-1', 'PHI001');
        $student->courses()->attach($course->id);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showJoinCourse', false)
                ->where('showCreateOrJoinGroup', false)
                ->where('enrolledCourses.0.title', 'Philosophie')
                ->has('openProjects', 0));
    }

    public function test_create_or_join_group_label_exists_in_every_locale(): void
    {
        foreach (['en', 'it', 'es'] as $locale) {
            $translations = json_decode((string) file_get_contents(lang_path($locale.'.json')), true);

            $this->assertIsArray($translations);
            $this->assertArrayHasKey('Créer ou rejoindre un groupe', $translations);
            $this->assertArrayHasKey('Mes cours', $translations);
            $this->assertNotSame('', $translations['Créer ou rejoindre un groupe']);
            $this->assertNotSame('', $translations['Mes cours']);
        }
    }

    private function makeCourse(User $professor, string $title, string $code, string $joinCode): Course
    {
        return Course::create([
            'professor_id' => $professor->id,
            'title' => $title,
            'code' => $code,
            'join_code' => $joinCode,
        ]);
    }

    private function joinProjectGroup(User $student, Project $project, string $name): Group
    {
        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $student->id,
            'name' => $name,
        ]);
        $group->members()->attach($student->id, ['is_leader' => true]);

        return $group;
    }
}
