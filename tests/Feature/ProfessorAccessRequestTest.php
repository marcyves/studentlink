<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ProfessorAccessRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfessorAccessRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_professor_access_request(): void
    {
        Mail::fake();

        $this->post(route('professor-access.store'), [
            'name' => 'Prof. Dupont',
            'email' => 'prof.dupont@ecole.fr',
            'institution' => 'IPAG',
            'message' => 'Cours de management',
        ])->assertRedirect();

        $this->assertDatabaseHas('professor_access_requests', [
            'email' => 'prof.dupont@ecole.fr',
            'status' => 'pending',
        ]);

        Mail::assertSent(\App\Mail\ProfessorAccessRequestMail::class);
    }
}
