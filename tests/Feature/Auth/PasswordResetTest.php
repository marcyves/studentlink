<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_authenticated_user_opening_a_reset_link_is_logged_out_and_sees_the_form(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $professor = User::factory()->professor()->create();

        $this->post('/forgot-password', ['email' => $professor->email]);

        Notification::assertSentTo($professor, ResetPassword::class, function ($notification) use ($admin, $professor) {
            $response = $this->actingAs($admin)->get(route('password.reset', [
                'token' => $notification->token,
                'email' => $professor->email,
            ]));

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', $professor->email)
                ->where('token', $notification->token));

            $this->assertGuest();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $professor->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $this->assertGuest();

            return true;
        });
    }
}
