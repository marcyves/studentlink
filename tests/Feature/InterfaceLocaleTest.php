<?php

namespace Tests\Feature;

use App\Enums\InterfaceLocale;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InterfaceLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_and_admins_stay_in_french(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'fr')
                ->where('translations', [])
                ->where('locales', []));

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
    }

    public function test_professor_language_drives_their_interface_and_their_students(): void
    {
        $professor = User::factory()->professor()->create([
            'locale' => InterfaceLocale::Italian,
        ]);
        $student = User::factory()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Corso',
            'code' => 'IT-1',
            'join_code' => 'ITALIA',
        ]);
        $student->courses()->attach($course->id);

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="it"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'it')
                ->where('translations.Nouveau cours', 'Nuovo corso')
                ->has('locales', 4));

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'it')
                ->where('translations.Tableau de bord', 'Bacheca')
                ->where('locales', []));
    }

    public function test_student_without_a_course_stays_in_french(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'fr')
                ->where('translations', []));
    }

    public function test_latest_course_wins_when_professors_use_different_languages(): void
    {
        $italian = User::factory()->professor()->create([
            'locale' => InterfaceLocale::Italian,
        ]);
        $spanish = User::factory()->professor()->create([
            'locale' => InterfaceLocale::Spanish,
        ]);
        $student = User::factory()->create();

        $older = Course::create([
            'professor_id' => $italian->id,
            'title' => 'Vecchio',
            'code' => 'OLD',
            'join_code' => 'OLD1',
        ]);
        $newer = Course::create([
            'professor_id' => $spanish->id,
            'title' => 'Nuevo',
            'code' => 'NEW',
            'join_code' => 'NEW1',
        ]);

        $student->courses()->attach($older->id, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $student->courses()->attach($newer->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'es')
                ->where('translations.Tableau de bord', 'Panel'));
    }

    public function test_only_a_professor_can_change_the_interface_language(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();

        $this->actingAs($student)
            ->patch(route('professor.locale.update'), ['locale' => 'en'])
            ->assertForbidden();

        $this->actingAs($professor)
            ->patch(route('professor.locale.update'), ['locale' => 'xx'])
            ->assertSessionHasErrors('locale');

        $this->actingAs($professor)
            ->patch(route('professor.locale.update'), ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame(InterfaceLocale::English, $professor->fresh()->locale);

        $this->actingAs($professor)
            ->post(route('professor.courses.store'), [
                'title' => 'English course',
                'code' => 'EN-1',
                'join_code' => 'ENGLISH',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Course created.');
    }
}
