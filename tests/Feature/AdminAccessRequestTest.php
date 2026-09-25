<?php

namespace Tests\Feature;

use App\Enums\AccessRequestStatus;
use App\Enums\UserRole;
use App\Models\ProfessorAccessRequest;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAccessRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_accept_access_request_and_create_professor(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $request = ProfessorAccessRequest::create([
            'name' => 'Prof. Martin',
            'email' => 'prof.martin@ecole.fr',
            'institution' => 'IPAG',
            'status' => AccessRequestStatus::Pending->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.access-requests.accept', $request))
            ->assertRedirect()
            ->assertSessionHas('success');

        $request->refresh();
        $this->assertSame(AccessRequestStatus::Accepted->value, $request->status);

        $user = User::query()->where('email', 'prof.martin@ecole.fr')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Professor, $user->role);
        $this->assertNotNull($user->email_verified_at);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_accepting_an_existing_unverified_professor_marks_the_email_verified(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $professor = User::factory()->professor()->unverified()->create([
            'email' => 'deja@ecole.fr',
        ]);
        $request = ProfessorAccessRequest::create([
            'name' => $professor->name,
            'email' => 'deja@ecole.fr',
            'status' => AccessRequestStatus::Pending->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.access-requests.accept', $request))
            ->assertRedirect();

        $this->assertNotNull($professor->fresh()->email_verified_at);
        $this->assertSame(1, User::query()->where('email', 'deja@ecole.fr')->count());
    }

    public function test_admin_can_reject_access_request(): void
    {
        $admin = User::factory()->admin()->create();
        $request = ProfessorAccessRequest::create([
            'name' => 'Prof. Refus',
            'email' => 'refus@ecole.fr',
            'status' => AccessRequestStatus::Pending->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.access-requests.reject', $request))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            AccessRequestStatus::Rejected->value,
            $request->fresh()->status,
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'refus@ecole.fr',
        ]);
    }

    public function test_non_admin_cannot_accept_access_request(): void
    {
        $professor = User::factory()->professor()->create();
        $request = ProfessorAccessRequest::create([
            'name' => 'Prof. Test',
            'email' => 'test@ecole.fr',
            'status' => AccessRequestStatus::Pending->value,
        ]);

        $this->actingAs($professor)
            ->post(route('admin.access-requests.accept', $request))
            ->assertForbidden();
    }
}
