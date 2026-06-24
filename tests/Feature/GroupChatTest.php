<?php

namespace Tests\Feature;

use App\Events\GroupMessageSent;
use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GroupChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_their_group_chats(): void
    {
        [$student, $group] = $this->seedGroupWithMember();

        $this->actingAs($student)
            ->get(route('student.chat.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Chat/Index')
                ->has('groups', 1)
                ->where('groups.0.name', $group->name));
    }

    public function test_student_can_post_message_to_their_group(): void
    {
        Event::fake([GroupMessageSent::class]);

        [$student, $group] = $this->seedGroupWithMember();

        $this->actingAs($student)
            ->post(route('student.chat.store', $group), ['body' => 'Bonjour équipe'])
            ->assertRedirect();

        $this->assertDatabaseHas('group_messages', [
            'group_id' => $group->id,
            'user_id' => $student->id,
            'body' => 'Bonjour équipe',
        ]);

        Event::assertDispatched(GroupMessageSent::class);
    }

    public function test_non_member_cannot_access_group_chat(): void
    {
        [, $group] = $this->seedGroupWithMember();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('student.chat.show', $group))
            ->assertForbidden();
    }

    public function test_student_can_post_message_when_broadcast_unavailable(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'queue.default' => 'sync',
        ]);

        [$student, $group] = $this->seedGroupWithMember();

        $this->actingAs($student)
            ->post(route('student.chat.store', $group), ['body' => 'Sans Reverb'])
            ->assertRedirect();

        $this->assertDatabaseHas('group_messages', [
            'group_id' => $group->id,
            'body' => 'Sans Reverb',
        ]);
    }

    /**
     * @return array{0: User, 1: Group}
     */
    private function seedGroupWithMember(): array
    {
        $professor = User::factory()->professor()->create();
        $student = User::factory()->create();

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);
        $course->students()->attach($student->id);

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet',
        ]);

        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $student->id,
            'name' => 'Groupe test',
            'invite_code' => 'TEST01',
        ]);
        $group->members()->attach($student->id, ['is_leader' => true]);

        return [$student, $group];
    }
}
