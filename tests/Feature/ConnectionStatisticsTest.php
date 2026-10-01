<?php

namespace Tests\Feature;

use App\Enums\EvaluationType;
use App\Enums\LoginOutcome;
use App\Events\GroupMessageSent;
use App\Models\Course;
use App\Models\Group;
use App\Models\LoginAttempt;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConnectionStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_stored(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();

        $attempt = LoginAttempt::query()->first();
        $this->assertNotNull($attempt);
        $this->assertSame($user->email, $attempt->identifier);
        $this->assertSame(LoginOutcome::Blocked, $attempt->outcome);
        $this->assertNull($attempt->user_id);
        $this->assertNotNull($attempt->ip_address);
        $this->assertNull($attempt->country);
        $this->assertNull($attempt->city);
    }

    public function test_successful_login_is_stored(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $attempt = LoginAttempt::query()->first();
        $this->assertNotNull($attempt);
        $this->assertSame($user->email, $attempt->identifier);
        $this->assertSame(LoginOutcome::Success, $attempt->outcome);
        $this->assertSame($user->id, $attempt->user_id);
        $this->assertNotNull($attempt->ip_address);
        $this->assertNotNull($attempt->created_at);
    }

    public function test_rate_limited_login_is_stored_as_blocked(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->assertSame(6, LoginAttempt::query()->where('outcome', LoginOutcome::Blocked)->count());
        $this->assertSame(0, LoginAttempt::query()->where('outcome', LoginOutcome::Success)->count());
    }

    public function test_geolocation_is_stored_when_the_ip_resolves(): void
    {
        config(['studentlink.geolocate_logins' => true]);

        Http::fake([
            'ipwho.is/*' => Http::response([
                'success' => true,
                'country' => 'France',
                'city' => 'Lyon',
            ]),
        ]);

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('login_attempts', [
            'user_id' => $user->id,
            'ip_address' => '8.8.8.8',
            'country' => 'France',
            'city' => 'Lyon',
            'outcome' => LoginOutcome::Success->value,
        ]);
    }

    public function test_geolocation_stays_empty_when_the_ip_cannot_be_resolved(): void
    {
        config(['studentlink.geolocate_logins' => true]);

        Http::fake([
            'ipwho.is/*' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $unresolved = LoginAttempt::query()->where('ip_address', '1.1.1.1')->first();
        $this->assertNotNull($unresolved);
        $this->assertNull($unresolved->country);
        $this->assertNull($unresolved->city);
        $this->assertSame(LoginOutcome::Blocked, $unresolved->outcome);
    }

    public function test_geolocation_failure_still_stores_the_login(): void
    {
        config(['studentlink.geolocate_logins' => true]);

        Http::fake(function () {
            throw new ConnectionException('offline');
        });

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertAuthenticated();

        $offline = LoginAttempt::query()->where('ip_address', '9.9.9.9')->first();
        $this->assertNotNull($offline);
        $this->assertNull($offline->country);
        $this->assertNull($offline->city);
        $this->assertSame(LoginOutcome::Success, $offline->outcome);
    }

    public function test_private_ip_is_not_sent_to_the_geolocation_service(): void
    {
        config(['studentlink.geolocate_logins' => true]);
        Http::fake();

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        Http::assertNothingSent();

        $local = LoginAttempt::query()->where('ip_address', '127.0.0.1')->first();
        $this->assertNotNull($local);
        $this->assertNull($local->country);
        $this->assertNull($local->city);
    }

    public function test_admin_can_open_connection_statistics(): void
    {
        $student = User::factory()->create();

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ]);
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'intrus@example.test',
            'password' => 'wrong-password',
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.connections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Connections')
                ->where('chart.success', 1)
                ->where('chart.blocked', 1)
                ->has('attempts.data', 2)
                ->where('attempts.data.0.identifier', 'intrus@example.test')
                ->where('attempts.data.0.outcome', 'blocked')
                ->where('attempts.data.0.outcome_label', 'Bloqué')
                ->where('attempts.data.1.identifier', $student->email)
                ->where('attempts.data.1.outcome', 'success')
                ->where('attempts.data.1.outcome_label', 'Réussi'));
    }

    public function test_student_and_professor_cannot_open_connection_statistics(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.connections.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->professor()->create())
            ->get(route('admin.connections.index'))
            ->assertForbidden();
    }

    public function test_professor_sees_only_their_class(): void
    {
        $professorA = User::factory()->professor()->create(['email' => 'ada@alpha.test']);
        $professorB = User::factory()->professor()->create(['email' => 'bob@beta.test']);
        $alice = User::factory()->create([
            'name' => 'Alice Martin',
            'email' => 'alice@alpha.test',
        ]);
        $bruno = User::factory()->create([
            'name' => 'Bruno Bernard',
            'email' => 'bruno@beta.test',
        ]);

        $courseA = Course::create([
            'professor_id' => $professorA->id,
            'title' => 'Algèbre',
            'code' => 'ALG-1',
            'join_code' => 'ALGEBRA',
        ]);
        $courseB = Course::create([
            'professor_id' => $professorB->id,
            'title' => 'Histoire',
            'code' => 'HIS-1',
            'join_code' => 'HISTOIRE',
        ]);

        $this->actingAs($alice)
            ->post(route('student.courses.join'), ['join_code' => 'ALGEBRA'])
            ->assertRedirect();
        $this->actingAs($bruno)
            ->post(route('student.courses.join'), ['join_code' => 'HISTOIRE'])
            ->assertRedirect();

        $this->actingAs($professorA)
            ->get(route('professor.courses.activity', $courseA))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Professor/CourseActivity')
                ->where('course.title', 'Algèbre')
                ->has('students', 1)
                ->where('students.0.name', 'Alice Martin')
                ->where('students.0.has_logged_in', false)
                ->where('students.0.last_login_at', null)
                ->missing('chart')
                ->missing('attempts')
                ->where('students', fn ($students) => collect($students)->pluck('name')->all() === ['Alice Martin']));

        $this->actingAs($professorA)
            ->get(route('professor.courses.activity', $courseB))
            ->assertForbidden();

        $this->actingAs($professorB)
            ->get(route('professor.courses.activity', $courseA))
            ->assertForbidden();

        $this->actingAs($alice)
            ->get(route('professor.courses.activity', $courseA))
            ->assertForbidden();

        $this->post('/logout');
        $this->post('/login', [
            'email' => $alice->email,
            'password' => 'password',
        ])->assertRedirect();
        $this->post('/logout');
        $this->post('/login', [
            'email' => $alice->email,
            'password' => 'wrong-password',
        ]);

        $this->actingAs($professorA)
            ->get(route('professor.courses.activity', $courseA))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('students', 1)
                ->where('students.0.has_logged_in', true)
                ->where('students.0.last_login_at', fn ($value) => is_string($value) && $value !== '')
                ->has('activities.data', 1)
                ->where('activities.data.0.student', 'Alice Martin')
                ->missing('attempts'));
    }

    public function test_student_action_in_the_class_shows_up(): void
    {
        Event::fake([GroupMessageSent::class]);

        $professor = User::factory()->professor()->create(['email' => 'prof@school.test']);
        $student = User::factory()->create([
            'name' => 'Alice Martin',
            'email' => 'alice@school.test',
        ]);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Algèbre linéaire',
            'code' => 'ALG-2',
            'join_code' => 'JOIN2026',
        ]);
        $project = $course->projects()->create([
            'title' => 'Projet oral',
        ]);

        $this->actingAs($student)
            ->post(route('student.courses.join'), ['join_code' => 'JOIN2026'])
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('student.groups.store'), [
                'project_id' => $project->id,
                'name' => 'Groupe Alpha',
            ])
            ->assertRedirect();

        $group = Group::query()->where('name', 'Groupe Alpha')->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.chat.store', $group), ['body' => 'Bonjour équipe'])
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('student.groups.submission.store', $group))
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('student.groups.leave', $group))
            ->assertRedirect();

        $this->actingAs($student)
            ->post(route('student.groups.leave', $group))
            ->assertForbidden();

        $this->actingAs($professor)
            ->get(route('professor.courses.activity', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('course.title', 'Algèbre linéaire')
                ->has('activities.data', 5)
                ->where('activities.data.0.action', 'left_group')
                ->where('activities.data.0.action_label', 'A quitté le groupe « Groupe Alpha »')
                ->where('activities.data.0.student', 'Alice Martin')
                ->where('activities.data.1.action', 'submitted_deliverable')
                ->where('activities.data.1.action_label', 'A déposé un livrable pour « Projet oral »')
                ->where('activities.data.2.action', 'posted_message')
                ->where('activities.data.2.action_label', 'A publié un message dans « Groupe Alpha »')
                ->where('activities.data.3.action', 'joined_group')
                ->where('activities.data.3.action_label', 'A rejoint le groupe « Groupe Alpha »')
                ->where('activities.data.4.action', 'joined_course')
                ->where('activities.data.4.action_label', 'A rejoint le cours'));
    }

    public function test_sending_an_evaluation_shows_up_for_the_professor(): void
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create(['name' => 'Alice Martin']);
        $other = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours de chimie',
            'code' => 'CH-1',
            'join_code' => 'CHIMIE',
        ]);
        $course->students()->attach([$student->id, $other->id]);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet oral',
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
            'created_by' => $other->id,
            'name' => 'Groupe B',
            'invite_code' => 'GRPB',
        ]);
        $groupB->members()->attach($other->id, ['is_leader' => true]);

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
            ->assertRedirect();

        $this->actingAs($professor)
            ->get(route('professor.courses.activity', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.action', 'sent_evaluation')
                ->where('activities.data.0.student', 'Alice Martin')
                ->where('activities.data.0.action_label', 'A envoyé une évaluation pour « Projet oral »'));
    }
}
