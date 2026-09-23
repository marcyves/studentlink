<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessorCourseProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_professor_can_create_a_course_they_own(): void
    {
        $professor = User::factory()->professor()->create();
        $other = User::factory()->professor()->create();

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Management de projet',
                'description' => 'Cours de groupe.',
                'code' => 'mpwd-2026',
                'join_code' => 'join2026',
                'professor_id' => $other->id,
                'allowed_email_domains' => ['Ipag.fr', '@etu.ipag.fr'],
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $course = Course::query()->where('join_code', 'JOIN2026')->first();

        $this->assertNotNull($course);
        $this->assertSame($professor->id, $course->professor_id);
        $this->assertSame('Management de projet', $course->title);
        $this->assertSame('Cours de groupe.', $course->description);
        $this->assertSame('MPWD-2026', $course->code);
        $this->assertSame(['ipag.fr', 'etu.ipag.fr'], $course->allowed_email_domains);
    }

    public function test_professor_can_create_a_course_without_email_domains(): void
    {
        $professor = User::factory()->professor()->create();

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Cours libre',
                'code' => 'LIBRE-1',
                'join_code' => 'LIBRE1',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('courses', [
            'professor_id' => $professor->id,
            'title' => 'Cours libre',
            'code' => 'LIBRE-1',
            'join_code' => 'LIBRE1',
            'description' => null,
            'allowed_email_domains' => null,
        ]);
    }

    public function test_created_course_and_project_appear_on_the_dashboard(): void
    {
        $professor = User::factory()->professor()->create();

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Management de projet',
                'description' => 'Parcours complet.',
                'code' => 'MPWD-2026',
                'join_code' => 'JOIN2026',
            ])
            ->assertRedirect();

        $course = Course::query()->where('code', 'MPWD-2026')->firstOrFail();

        $this->actingAs($professor)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => 'Plateforme collaborative',
                'description' => 'Évaluation entre pairs.',
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-15',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Professor/Dashboard')
                ->has('courses', 1)
                ->where('courses.0.title', 'Management de projet')
                ->where('courses.0.description', 'Parcours complet.')
                ->where('courses.0.code', 'MPWD-2026')
                ->where('courses.0.join_code', 'JOIN2026')
                ->has('courses.0.projects', 1)
                ->where('courses.0.projects.0.title', 'Plateforme collaborative')
                ->where('courses.0.projects.0.description', 'Évaluation entre pairs.')
                ->where('courses.0.projects.0.starts_at', '01/09/2026')
                ->where('courses.0.projects.0.ends_at', '15/10/2026'));
    }

    public function test_professor_without_courses_sees_an_empty_dashboard(): void
    {
        $professor = User::factory()->professor()->create();

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Professor/Dashboard')
                ->has('courses', 0));
    }

    public function test_student_cannot_create_a_course(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post(route('professor.courses.store'), [
                'title' => 'Cours',
                'code' => 'C-1',
                'join_code' => 'JOIN1',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_duplicate_course_codes_are_rejected(): void
    {
        $owner = User::factory()->professor()->create();
        $professor = User::factory()->professor()->create();

        Course::create([
            'professor_id' => $owner->id,
            'title' => 'Existant',
            'code' => 'MPWD-2026',
            'join_code' => 'JOIN2026',
        ]);

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Autre cours',
                'code' => 'mpwd-2026',
                'join_code' => 'AUTRE1',
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Autre cours',
                'code' => 'AUTRE-1',
                'join_code' => 'join2026',
            ])
            ->assertSessionHasErrors('join_code');

        $this->assertDatabaseCount('courses', 1);
    }

    public function test_invalid_email_domain_is_rejected(): void
    {
        $professor = User::factory()->professor()->create();

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'Cours',
                'code' => 'C-1',
                'join_code' => 'JOIN1',
                'allowed_email_domains' => ['pas un domaine'],
            ])
            ->assertSessionHasErrors('allowed_email_domains.0');

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_professor_cannot_create_a_project_on_another_professors_course(): void
    {
        $owner = User::factory()->professor()->create();
        $intruder = User::factory()->professor()->create();

        $course = Course::create([
            'professor_id' => $owner->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($intruder)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => 'Projet intrus',
                'description' => 'Ne doit pas être créé.',
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-01',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_student_cannot_create_a_project(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($student)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => 'Projet',
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-01',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_end_must_not_precede_the_start(): void
    {
        $professor = User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($professor)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => 'Projet',
                'starts_at' => '2026-10-15',
                'ends_at' => '2026-09-01',
            ])
            ->assertSessionHasErrors('ends_at');

        $this->actingAs($professor)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => '',
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-01',
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_guest_cannot_create_a_course(): void
    {
        $this->post(route('professor.courses.store'), [
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('courses', 0);
    }
}
