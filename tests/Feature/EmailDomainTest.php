<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StudentLinkSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function enableDomainRestriction(): void
    {
        StudentLinkSetting::current()->update(['require_registration_domain' => true]);
    }

    private function disableDomainRestriction(): void
    {
        StudentLinkSetting::current()->update(['require_registration_domain' => false]);
    }

    public function test_student_registration_rejects_unauthorized_domain(): void
    {
        $this->enableDomainRestriction();

        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->post('/register', [
            'name' => 'Étudiant',
            'email' => 'etudiant@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_registration_accepts_authorized_domain(): void
    {
        $this->enableDomainRestriction();

        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->post('/register', [
            'name' => 'Étudiant',
            'email' => 'etudiant@ecole.fr',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_registration_allows_any_domain_when_restriction_disabled(): void
    {
        $this->disableDomainRestriction();

        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->post('/register', [
            'name' => 'Étudiant',
            'email' => 'etudiant@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_cannot_register_as_professor_via_public_form(): void
    {
        $this->disableDomainRestriction();

        $this->post('/register', [
            'name' => 'Prof',
            'email' => 'prof@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'professor',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'prof@test.com',
            'role' => 'student',
        ]);
    }

    public function test_student_cannot_join_course_with_wrong_email_domain(): void
    {
        $this->enableDomainRestriction();

        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        $student = User::factory()->create(['email' => 'alice@gmail.com']);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($student)
            ->post(route('student.courses.join'), ['join_code' => 'JOIN1'])
            ->assertSessionHasErrors('join_code');

        $this->assertFalse($student->courses()->where('courses.id', $course->id)->exists());
    }

    public function test_course_uses_professor_domain_when_no_explicit_domains(): void
    {
        $this->enableDomainRestriction();

        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $student = User::factory()->create(['email' => 'etudiant@ecole.fr']);

        $this->actingAs($student)
            ->post(route('student.courses.join'), ['join_code' => 'JOIN1'])
            ->assertRedirect();

        $this->assertTrue($student->courses()->where('courses.id', $course->id)->exists());
    }

    public function test_professor_can_update_course_email_domains(): void
    {
        $professor = User::factory()->professor()->create([
            'email' => 'prof@ecole.fr',
        ]);
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($professor)
            ->from(route('profile.edit'))
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => ['Ipag.fr', '@etu.ipag.fr'],
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $course->refresh();
        $this->assertSame(['ipag.fr', 'etu.ipag.fr'], $course->allowed_email_domains);
    }

    public function test_professor_can_clear_course_email_domains(): void
    {
        $this->enableDomainRestriction();

        $professor = User::factory()->professor()->create([
            'email' => 'prof@ecole.fr',
        ]);
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
            'allowed_email_domains' => ['autre.fr'],
        ]);

        $this->actingAs($professor)
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => [],
            ])
            ->assertRedirect();

        $this->assertNull($course->refresh()->allowed_email_domains);

        $student = User::factory()->create(['email' => 'etudiant@ecole.fr']);

        $this->actingAs($student)
            ->post(route('student.courses.join'), ['join_code' => 'JOIN1'])
            ->assertRedirect();

        $this->assertTrue($student->courses()->where('courses.id', $course->id)->exists());
    }

    public function test_another_professor_cannot_update_course_email_domains(): void
    {
        $owner = User::factory()->professor()->create();
        $intruder = User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $owner->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
            'allowed_email_domains' => ['ecole.fr'],
        ]);

        $this->actingAs($intruder)
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => ['intrus.fr'],
            ])
            ->assertForbidden();

        $this->assertSame(['ecole.fr'], $course->refresh()->allowed_email_domains);
    }

    public function test_student_cannot_update_course_email_domains(): void
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
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => ['ecole.fr'],
            ])
            ->assertForbidden();

        $this->assertNull($course->refresh()->allowed_email_domains);
    }

    public function test_admin_cannot_update_course_email_domains(): void
    {
        $professor = User::factory()->professor()->create();
        $admin = User::factory()->admin()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($admin)
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => ['ecole.fr'],
            ])
            ->assertForbidden();

        $this->assertNull($course->refresh()->allowed_email_domains);
    }

    public function test_guest_cannot_update_course_email_domains(): void
    {
        $professor = User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->put(route('professor.courses.domains.update', $course), [
            'allowed_email_domains' => ['ecole.fr'],
        ])->assertRedirect(route('login'));

        $this->assertNull($course->refresh()->allowed_email_domains);
    }

    public function test_invalid_email_domain_is_rejected(): void
    {
        $professor = User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
            'allowed_email_domains' => ['ecole.fr'],
        ]);

        $this->actingAs($professor)
            ->from(route('profile.edit'))
            ->put(route('professor.courses.domains.update', $course), [
                'allowed_email_domains' => ['pas un domaine'],
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('allowed_email_domains.0');

        $this->assertSame(['ecole.fr'], $course->refresh()->allowed_email_domains);
    }

    public function test_professor_profile_lists_owned_course_domains(): void
    {
        $professor = User::factory()->professor()->create([
            'email' => 'prof@ecole.fr',
        ]);
        $other = User::factory()->professor()->create();

        $owned = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours Alpha',
            'code' => 'ALPHA',
            'join_code' => 'ALPHA1',
            'allowed_email_domains' => ['ipag.fr'],
        ]);
        Course::create([
            'professor_id' => $other->id,
            'title' => 'Cours d\'un autre',
            'code' => 'OTHER',
            'join_code' => 'OTHER1',
            'allowed_email_domains' => ['secret.fr'],
        ]);

        $this->actingAs($professor)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('courses', 1)
                ->where('courses.0.id', $owned->id)
                ->where('courses.0.title', 'Cours Alpha')
                ->where('courses.0.code', 'ALPHA')
                ->where('courses.0.allowed_email_domains', ['ipag.fr'])
                ->where('courses.0.effective_email_domains', ['ipag.fr'])
                ->where('courses.0.default_professor_domain', 'ecole.fr'));
    }

    public function test_student_profile_does_not_expose_course_domains(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();
        Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
            'allowed_email_domains' => ['ecole.fr'],
        ]);

        $this->actingAs($student)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('courses', 0));
    }

    public function test_admin_can_view_dashboard_with_professors_and_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $professor = User::factory()->professor()->create(['email' => 'prof@ecole.fr']);
        $student = User::factory()->create(['email' => 'alice@ecole.fr']);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours test',
            'code' => 'C-ADM',
            'join_code' => 'ADM001',
        ]);
        $course->students()->attach($student->id);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('professors', 1)
                ->where('professors.0.students.0.email', 'alice@ecole.fr'));

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings')
                ->where('settings.require_registration_domain', true));

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), [
                'require_registration_domain' => false,
            ])
            ->assertRedirect();

        $this->assertFalse(StudentLinkSetting::current()->refresh()->require_registration_domain);
    }
}
